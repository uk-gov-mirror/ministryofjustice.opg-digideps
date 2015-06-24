<?php
namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use AppBundle\Form as FormDir;
use AppBundle\Model\Email;
use AppBundle\Service\ApiClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
* @Route("user")
*/
class UserController extends Controller
{
    /**
     * Used for both user activation (Step1) or password reset. The controller logic is very similar
     * 
     * @Route("/{action}/{token}", name="user_activate", defaults={ "action" = "activate"}, requirements={
     *   "action" = "(activate|password-reset)"
     * })
     * @Template()
     */
    public function setPasswordAndLoginAction(Request $request, $action, $token)
    {
        $apiClient = $this->get('apiclient'); /* @var $apiClient ApiClient */
        $translator = $this->get('translator');
        $oauth2Enabled = $this->container->getParameter('oauth2_enabled');
        
        // check $token is correct
        $user = $apiClient->getEntity('User', 'find_user_by_token', [ 'parameters' => [ 'token' => $token ] ]); /* @var $user EntityDir\User*/
        
        if (!$user->isTokenSentInTheLastHours(EntityDir\User::TOKEN_EXPIRE_HOURS)) {
            throw new \RuntimeException("token expired, require new link");
        }
        
        // define form and template that differs depending on the action (activate or password-reset)
        if ($action == 'activate') {
            $formType = new FormDir\SetPasswordType([
                'passwordMismatchMessage' => $translator->trans('password.validation.passwordMismatch', [], 'user-activate')
            ]);
            $template = 'AppBundle:User:activate.html.twig';
        } else if ($action === 'password-reset') {
            $formType = new FormDir\ResetPasswordType([
                'passwordMismatchMessage' => $this->get('translator')->trans('password.validation.passwordMismatch', [], 'password-reset')
            ]);
            $template = 'AppBundle:User:passwordReset.html.twig';
        } else {
            return $this->createNotFoundException("action $action not defined ");
        }
        
        $form = $this->createForm($formType, $user);
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                
                // calculated hashed password
                $encodedPassword = $this->get('security.encoder_factory')->getEncoder($user)
                        ->encodePassword($user->getPassword(), $user->getSalt());
                $apiClient->putC('user/' . $user->getId(), json_encode([
                    'password' => $encodedPassword,
                    'active' => true
                ]));
                
                // log in user
                $token = new UsernamePasswordToken($user, null, "secured_area", $user->getRoles());
                $this->get("security.context")->setToken($token); //now the user is logged in
                
                $session = $this->get('session');
                $session->set('_security_secured_area', serialize($token));
                 
                if($oauth2Enabled){
                    //cache hashed password to use in oauth2 calls
                   $redis = $this->get('snc_redis.default');
                   $redis->hset($session->getId().'_user_credentials','email',$user->getEmail());
                   $redis->hset($session->getId().'_user_credentials','password', $encodedPassword);
                   $redis->expire($session->getId().'_user_credentials',3600);
                }
                 
                 $request = $this->get("request");
                 $event = new InteractiveLoginEvent($request, $token);
                 $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
                 
                 // the following should not be triggered
                 return $this->redirect($this->generateUrl('user_details'));
            }
        } 

        return $this->render($template, [
            'token'=>$token, 
            'form' => $form->createView(),
            'isAdmin' => $user->getRole()['role'] === 'ROLE_ADMIN'
        ]);
    }
    
    
    /**
     * Registration steps
     *
     * @Route("/details", name="user_details")
     * @Template()
     */
    public function detailsAction(Request $request)
    {
        $apiClient = $this->get('apiclient'); /* @var $apiClient ApiClient */
        $userId = $this->get('security.context')->getToken()->getUser()->getId();
        $user = $apiClient->getEntity('User', 'user/' . $userId); /* @var $user EntityDir\User*/
        $basicFormOnly = $this->get('security.context')->isGranted('ROLE_ADMIN');
        $notification = $request->query->has('notification')? $request->query->get('notification'): null;

        $formType = $basicFormOnly ? new FormDir\UserDetailsBasicType() : new FormDir\UserDetailsFullType([
            'addressCountryEmptyValue' => $this->get('translator')->trans('addressCountry.defaultOption', [], 'user-activate'),
        ]);
        $form = $this->createForm($formType, $user);
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $apiClient->putC('user/' . $user->getId(), $form->getData(), [
                    'deserialise_group' => $basicFormOnly ? 'user_details_basic' : 'user_details_full'
                ]);
                
                // after details are added, admin users to go their homepage, deputies go to next step
                return $this->redirect($this->generateUrl($basicFormOnly ? 'admin_homepage' : 'client_add'));
            }
        } else {
            // fill the form in (edit mode)
            $form->setData($user);
        }
        
        return [
            'form' => $form->createView()
        ];
        
    }
    
    /**
     * @Route("/{action}", name="user_view", defaults={ "action" = ""})
     * @Template()
     **/
    public function indexAction($action)
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $oauth2Enabled = $this->container->getParameter('oauth2_enabled');
        $useMemcached = $this->container->getParameter('use_memcached');
        $useRedis = $this->container->getParameter('use_redis');
        
        $formEditDetails = $this->createForm(new FormDir\UserDetailsFullType([
            'addressCountryEmptyValue' => 'Please select...', [], 'user_view'
        ]), $user);
        
        $formEditDetails->add('password', new FormDir\ChangePasswordType($request), [ 'error_bubbling' => false, 'mapped' => false ]);
        
        if($request->getMethod() == 'POST'){
            $formEditDetails->handleRequest($request);
            $apiClient = $this->get('apiclient');
           
            if($formEditDetails->isValid()){
                $formData = $formEditDetails->getData();
                $formRawData = $request->request->get('user_details');
                
                /**
                 * if new password has been set then we need to encode this using the encoder and pass it to
                 * the api
                 */
                if(!empty($formRawData['password']['plain_password']['first'])){
                    $encodedPassword = $this->get('security.encoder_factory')->getEncoder($user)
                        ->encodePassword($formRawData['password']['plain_password']['first'], $user->getSalt());
                    $formData->setPassword($encodedPassword);
                    
                    //lets send an email to confirm password change
                    $emailConfig = $this->container->getParameter('email_send');
                    $translator = $this->get('translator');
                    
                    $email = new Email();
                    $email->setFromEmail($emailConfig['from_email'])
                        ->setFromName($translator->trans('changePassword.fromName',[], 'email'))
                        ->setToEmail($user->getEmail())
                        ->setToName($user->getFirstname())
                        ->setSubject($translator->trans('changePassword.subject',[], 'email'))
                        ->setBodyHtml($this->renderView('AppBundle:Email:change-password.html.twig'));
                    
                    $this->get('mailSender')->send($email,[ 'html']);
                    
                    //reset user api key
                    $session = $this->get('session');
                    
                    if($oauth2Enabled){
                        if($useRedis){
                            $redis = $this->get('snc_redis.default');
                            $redis->hset($session->getId().'_user_credentials','email',$user->getEmail());
                            $redis->hset($session->getId().'_user_credentials','password', $user->getPassword());
                            $redis->expire($session->getId().'_user_credentials',3600);
                        }elseif($useMemcached){
                             //cache hashed password to use in oauth2 calls
                            $memcached = $this->get('oauth.memcached');
                            $userApiKey = $memcached->get($session->getId().'_user_credentials');
                            if(!$userApiKey){
                                $memcached->add($session->getId().'_user_credentials',[ 'email' => $user->getEmail(), 'password' => $encodedPassword],3600);
                            }else{
                                $memcached->replace($session->getId().'_user_credentials', [ 'email' => $user->getEmail(), 'password' => $encodedPassword],3600);
                            }
                        }
                    }
                    
                    $request->getSession()->getFlashBag()->add(
                                'notification',
                                'page.passwordChangedNotification'
                            );
                    
                }
                $apiClient->putC('edit_user',$formData, [ 'parameters' => [ 'id' => $user->getId() ]]);

                return $this->redirect($this->generateUrl('user_view'));
            }
            
        }

        return [
            'action' => $action,
            'user' => $user,
            'formEditDetails' => $formEditDetails->createView()
        ];
    }
    
     /**
     * @Route("/password/forgotten", name="password_forgotten")
     * @Template()
     **/
    public function passwordForgottenAction(Request $request)
    {
        $user = new EntityDir\User;
        $form = $this->createForm(new FormDir\PasswordForgottenType(), $user);
        
        $form->handleRequest($request);
        if ($form->isValid()) {
            try {
                $apiClient = $this->get('apiclient');
                /* @var $user EntityDir\User */
                $user = $apiClient->getEntity('User', 'find_user_by_email', [ 
                    'parameters' => [ 'email' => $user->getEmail() ] 
                ]);
                $user->setRecreateRegistrationToken(true);
                $apiClient->putC('user/' .  $user->getId(), $user, [
                    'deserialise_group' => 'recreateRegistrationToken',
                ]);
                $user = $apiClient->getEntity('User', 'user/' . $user->getId());
                
                // send email !
                $this->sendResetPasswordEmail($user);
                
            } catch (\Exception $e) {
                // if the user it not found, the user must not be told, 
                $this->get('logger')->debug($e->getMessage());
            }

            // after details are added, admin users to go their homepage, deputies go to next step
            return $this->redirect($this->generateUrl('password_sent'));
        }
        
        return [
            'form' => $form->createView()
        ];
    }
    
    /**
     * @Route("/password/sent", name="password_sent")
     * @Template()
     **/
    public function passwordSentAction()
    {
        return [];
    }
    
    /**
     * @param EntityDir\User $user
     */
    private function sendResetPasswordEmail(EntityDir\User $user)
    {
        // send activation link
        $emailConfig = $this->container->getParameter('email_send');
        $translator = $this->get('translator');
        $router = $this->get('router');

        $email = new Email();
        $viewParams = [
            'name' => $user->getFullName(),
            'domain' => $router->generate('homepage', [], true),
            'link' => $router->generate('user_activate', [
                'action'=>'password-reset', 
                'token'=> $user->getRegistrationToken()
                ], true)
        ];
        
        $email->setFromEmail($emailConfig['from_email'])
            ->setFromName($translator->trans('resetPassword.fromName',[], 'email'))
            ->setToEmail($user->getEmail())
            ->setToName($user->getFullName())
            ->setSubject($translator->trans('resetPassword.subject',[], 'email'))
            ->setBodyHtml($this->renderView('AppBundle:Email:password-forgotten.html.twig', $viewParams))
            ->setBodyText($this->renderView('AppBundle:Email:password-forgotten.text.twig', $viewParams));

        $mailSender = $this->get('mailSender'); /* @var $mailSender \AppBundle\Service\MailSender */
        $mailSender->send($email,[ 'text', 'html']);
    }
    
}