<?php

namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use AppBundle\Form as FormDir;
use AppBundle\Model\SelfRegisterData;
use AppBundle\Service\Audit\AuditEvents;
use AppBundle\Service\Client\Internal\ClientApi;
use AppBundle\Service\Client\Internal\UserApi;
use AppBundle\Service\Client\RestClient;
use AppBundle\Service\Redirector;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CoDeputyController extends AbstractController
{
    private ClientApi $clientApi;
    private UserApi $userApi;
    private RestClient $restClient;
    private TranslatorInterface $translator;
    private LoggerInterface $logger;

    public function __construct(
        ClientApi $clientApi,
        UserApi $userApi,
        RestClient $restClient,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->clientApi = $clientApi;
        $this->userApi = $userApi;
        $this->restClient = $restClient;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @Route("/codeputy/verification", name="codep_verification")
     * @Template("AppBundle:CoDeputy:verification.html.twig")
     */
    public function verificationAction(Request $request, Redirector $redirector, ValidatorInterface $validator)
    {
        $user = $this->userApi->getUserWithData(['user', 'user-clients', 'client']);

        // redirect if user has missing details or is on wrong page
        if ($route = $redirector->getCorrectRouteIfDifferent($user, 'codep_verification')) {
            return $this->redirectToRoute($route);
        }

        $form = $this->createForm(FormDir\CoDeputyVerificationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // get client validation errors, if any, and add to the form
            $client = new EntityDir\Client();
            $client->setLastName($form['clientLastname']->getData());
            $client->setCaseNumber($form['clientCaseNumber']->getData());

            $errors = $validator->validate($client, null, ['verify-codeputy']);

            foreach ($errors as $error) {
                $clientProperty = $error->getPropertyPath();
                $form->get('client' . ucfirst($clientProperty))->addError(new FormError($error->getMessage()));
            }

            if ($form->isSubmitted() && $form->isValid()) {
                $selfRegisterData = new SelfRegisterData();
                $selfRegisterData->setFirstname($form['firstname']->getData());
                $selfRegisterData->setLastname($form['lastname']->getData());
                $selfRegisterData->setEmail($form['email']->getData());
                $selfRegisterData->setPostcode($form['addressPostcode']->getData());
                $selfRegisterData->setClientLastname($form['clientLastname']->getData());
                $selfRegisterData->setCaseNumber($form['clientCaseNumber']->getData());

                // validate against casRec
                try {
                    $this->restClient->apiCall('post', 'selfregister/verifycodeputy', $selfRegisterData, 'array', [], false);
                    $user->setCoDeputyClientConfirmed(true);
                    $this->restClient->put('user/' . $user->getId(), $user);
                    return $this->redirect($this->generateUrl('homepage'));
                } catch (\Throwable $e) {
                    $translator = $this->translator;

                    switch ((int) $e->getCode()) {
                        case 422:
                            $form->addError(new FormError(
                                $translator->trans('email.first.existingError', [
                                    '%login%' => $this->generateUrl('login'),
                                    '%passwordForgotten%' => $this->generateUrl('password_forgotten')
                                ], 'register')
                            ));
                            break;

                        case 421:
                            $form->addError(new FormError($translator->trans('formErrors.matching', [], 'register')));
                            break;

                        case 424:
                            $form->get('addressPostcode')->addError(new FormError($translator->trans('postcode.matchingError', [], 'register')));
                            break;

                        case 425:
                            $form->addError(new FormError($translator->trans('formErrors.caseNumberAlreadyUsed', [], 'register')));
                            break;

                        default:
                            $form->addError(new FormError($translator->trans('formErrors.generic', [], 'register')));
                    }

                    $this->logger->error(__METHOD__ . ': ' . $e->getMessage() . ', code: ' . $e->getCode());
                }
            }
        }

        return [
            'form' => $form->createView(),
            'user' => $user,
            'client_validated' => false
        ];
    }

    /**
     * @Route("/codeputy/{clientId}/add", name="add_co_deputy")
     * @Template("AppBundle:CoDeputy:add.html.twig")
     *
     * @param Request $request
     * @param Redirector $redirector
     *
     * @return array|RedirectResponse
     * @throws \Throwable
     */
    public function addAction(Request $request, Redirector $redirector)
    {
        $loggedInUser = $this->userApi->getUserWithData(['user-clients', 'client']);

        // redirect if user has missing details or is on wrong page
        if ($route = $redirector->getCorrectRouteIfDifferent($loggedInUser, 'add_co_deputy')) {
            return $this->redirectToRoute($route);
        }

        $invitedUser = new EntityDir\User();
        $form = $this->createForm(FormDir\CoDeputyInviteType::class, $invitedUser);

        $backLink = $loggedInUser->isNdrEnabled() ?
            $this->generateUrl('ndr_index')
            :$this->generateUrl('lay_home');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userApi->createCoDeputy($invitedUser, $loggedInUser);

                $this->userApi->update(
                    $loggedInUser,
                    $loggedInUser->setCoDeputyClientConfirmed(true),
                    AuditEvents::TRIGGER_CODEPUTY_CREATED
                );

                $request->getSession()->getFlashBag()->add('notice', 'Deputy invitation has been sent');

                return $this->redirect($backLink);
            } catch (\Throwable $e) {
                switch ((int) $e->getCode()) {
                    case 422:
                        $form->get('email')->addError(new FormError($this->translator->trans('form.email.existingError', [], 'co-deputy')));
                        break;
                    default:
                        $this->logger->error(__METHOD__ . ': ' . $e->getMessage() . ', code: ' . $e->getCode());
                        throw $e;
                }
                $this->logger->error(__METHOD__ . ': ' . $e->getMessage() . ', code: ' . $e->getCode());
            }
        }

        return [
            'form' => $form->createView(),
            'backLink' => $backLink,
            'client' => $this->clientApi->getFirstClient()
        ];
    }

    /**
     * @Route("/codeputy/re-invite/{email}", name="codep_resend_activation")
     * @Template("AppBundle:CoDeputy:resendActivation.html.twig")
     *
     * @param Request $request
     * @param $email
     *
     * @return array|RedirectResponse
     * @throws \Throwable
     */
    public function resendActivationAction(Request $request, string $email)
    {
        $loggedInUser = $this->userApi->getUserWithData(['user-clients', 'client']);
        $existingCoDeputy = $this->userApi->getByEmail($email);

        $form = $this->createForm(FormDir\CoDeputyInviteType::class, $existingCoDeputy);

        $backLink = $loggedInUser->isNdrEnabled() ?
            $this->generateUrl('ndr_index')
            :$this->generateUrl('lay_home');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formEmail = $form->getData()->getEmail();

                //email was updated on the fly
                if ($formEmail != $email) {
                    $this->restClient->put('codeputy/' . $existingCoDeputy->getId(), $form->getData(), []);
                }

                $this->userApi->reInviteCoDeputy($formEmail, $loggedInUser);

                $request->getSession()->getFlashBag()->add('notice', 'Deputy invitation was re-sent');

                return $this->redirect($backLink);
            } catch (\Throwable $e) {
                switch ((int) $e->getCode()) {
                    case 422:
                        $form->get('email')->addError(new FormError($this->translator->trans('form.email.existingError', [], 'co-deputy')));
                        break;
                    default:
                        $this->logger->error(__METHOD__ . ': ' . $e->getMessage() . ', code: ' . $e->getCode());
                        throw $e;
                }
                $this->logger->error(__METHOD__ . ': ' . $e->getMessage() . ', code: ' . $e->getCode());
            }
        }

        return [
            'form' => $form->createView(),
            'backLink' => $backLink
        ];
    }
}
