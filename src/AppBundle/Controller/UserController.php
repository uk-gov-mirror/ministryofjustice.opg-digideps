<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use AppBundle\Service\ApiClient;
use AppBundle\Form\SetPasswordType;

/**
* @Route("user")
*/
class UserController extends Controller
{
    /**
     * @Route("/activate/{token}", name="user_activate")
     */
    public function activateAction(Request $request, $token)
    {
        $apiClient = $this->get('apiclient'); /* @var $apiClient ApiClient */
        $translator = $this->get('translator');
        
        // check $token is correct
        $user = $apiClient->getEntity('User', 'find_user_by_token', [ 'query' => [ 'token' => $token ] ]); /* @var $user User*/

        if (!$user->isTokenSentInTheLastHours(48)) {
            throw new \RuntimeException("token expired, require new link");
        }
        
        $formType = new SetPasswordType([
            'passwordMismatchMessage' => $translator->trans('password.validation.passwordMismatch', [], 'user-activate')
        ]);
        $form = $this->createForm($formType, $user);
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                
                // calculated hashed password
                $encodedPassword = $this->get('security.encoder_factory')->getEncoder($user)
                        ->encodePassword($user->getPassword(), $user->getSalt());
                $apiClient->putC('user/'.$user->getId().'/set-password', json_encode([
                    'id' => $user->getId(),
                    'password' => $encodedPassword
                ]));
                
                //TODO log user in ?
                
                // redirect to step 2
                return $this->redirect($this->generateUrl('user_details'));
            }
        } 
        
        return $this->render('AppBundle:User:activate.html.twig', [
            'token'=>$token, 
            'form' => $form->createView()
        ]);
    }
    
    
    /**
     * @Route("/details", name="user_details")
     */
    public function detailsAction(Request $request)
    {
        $form = null;
        
        return $this->render('AppBundle:User:details.html.twig', [
             'form' => $form
        ]);
    }
}