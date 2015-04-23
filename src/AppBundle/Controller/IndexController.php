<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use AppBundle\Form\LoginType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Form\FormError;
use AppBundle\EventListener\SessionListener;

class IndexController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return new RedirectResponse($this->get('redirectorService')->getUserFirstPage());
    }
    
    /**
     * @Route("login", name="login")
     * @Template()
     */
    public function loginAction()
    {
        $request = $this->getRequest();

        $form = $this->createForm(new LoginType(), null, [
            'action' => $this->generateUrl('login'),
        ]);
        $form->handleRequest($request);
        $vars = [
            'form' => $form->createView(),
        ];
        
        if ($form->isValid()){
            $deputyProvider = $this->get('deputyprovider');
            $data = $form->getData();

            try{
                $user = $deputyProvider->loadUserByUsername($data['email']);
               
                $encoder = $this->get('security.encoder_factory')->getEncoder($user);

                // exception if credentials not valid
                if(!$encoder->isPasswordValid($user->getPassword(), $data['password'], $user->getSalt())){
                    $message = $this->get('translator')->trans('login.invalidMessage', [], 'login');
                    throw new \Exception($message);
                }
            } catch(\Exception $e){

                return $this->render('AppBundle:Index:login.html.twig', $vars + ['error' => $e->getMessage()]);
            }
            // manually set session token into security context (manual login)
            $token = new UsernamePasswordToken($user,null, "secured_area", $user->getRoles());
            $this->get("security.context")->setToken($token);
            
            $session = $request->getSession();
            $session->set('_security_secured_area', serialize($token));
            $session->set('loggedOutFrom', null);   
            // regenerate cookie, otherwise gc_* timeouts might logout out after successful login
            $session->migrate();
            
            $request = $this->get("request");
            $event = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
        }
        
        // different page version for timeout and manual logout
        $session = $this->getRequest()->getSession();
        if ($session->get('loggedOutFrom') === 'logoutPage') {
            $session->set('loggedOutFrom', null); //avoid display the message at next page reload
            return $this->render('AppBundle:Index:login-from-logout.html.twig', $vars);
        } else if ($session->get('loggedOutFrom') === 'timeout') {
            $session->set('loggedOutFrom', null); //avoid display the message at next page reload
            $vars['error'] = $this->get('translator')->trans('sessionTimeoutOutWarning', [], 'login');
        }
            
        
        return $this->render('AppBundle:Index:login.html.twig', $vars);
    }
    
    
    /**
     * @Route("login_check", name="login_check")
     */
    public function loginCheckAction()
    {
    }

    /**
     * @Route("/access-denied", name="access_denied")
     */
    public function accessDeniedAction()
    {
        throw new AccessDeniedException();
    }

    private function initProgressIndicator($array, $currentStep)
    {
        $currentStep = $currentStep - 1;
        $progressSteps_arr = array();
        if (is_array($array)) {
            $soa = count($array);

            for ($i = 0; $i < $soa; $i++) {
                $item = $array[$i];
                $classes = [];
                if ($i == $currentStep) {
                    $classes[] = 'progress--active';
                }
                if ($i < $currentStep) {
                    $classes[] = 'progress--completed';
                }
                if ($i == ($currentStep - 1)) {
                    $classes[] = 'progress--previous';
                }
                $item['class'] = implode(' ', $classes);

                $progressSteps_arr[] = $item;
            }

        }

        return $progressSteps_arr;
    }

}
