<?php

namespace App\Controller;

use App\Entity as EntityDir;
use App\Event\UserUpdatedEvent;
use App\Form as FormDir;
use App\Service\Audit\AuditEvents;
use App\Service\Client\Internal\UserApi;
use App\Service\Client\RestClient;
use App\Service\Logger;
use App\Service\Mailer\MailFactory;
use App\Service\Mailer\MailSender;
use App\Service\Redirector;
use App\Service\Time\DateTimeProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class SettingsController extends AbstractController
{
    /** @var MailFactory */
    private $mailFactory;

    /** @var MailSender */
    private $mailSender;

    /** @var TranslatorInterface */
    private $translator;

    /** @var Logger */
    private $logger;

    /** @var DateTimeProvider */
    private $dateTimeProvider;

    /**
     * @var UserApi
     */
    private $userApi;

    /**
     * @var RestClient
     */
    private $restClient;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        MailFactory $mailFactory,
        MailSender $mailSender,
        TranslatorInterface $translator,
        Logger $logger,
        DateTimeProvider $dateTimeProvider,
        UserApi $userApi,
        RestClient $restClient,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->mailFactory = $mailFactory;
        $this->mailSender = $mailSender;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->dateTimeProvider = $dateTimeProvider;
        $this->userApi = $userApi;
        $this->restClient = $restClient;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/deputyship-details", name="account_settings")
     * @Route("/org/settings", name="org_settings")
     * @Template("@App/Settings/index.html.twig")
     **/
    public function indexAction(Redirector $redirector)
    {
        if ($this->getUser()->isDeputyOrg()) {
            $user = $this->userApi->getUserWithData(['user-organisations', 'organisation']);

            return [
                'hasOrganisations' => count($user->getOrganisations()),
            ];
        }

        // redirect if user has missing details or is on wrong page
        $user = $this->userApi->getUserWithData(['user-clients', 'client', 'client-reports', 'report']);
        if ($route = $redirector->getCorrectRouteIfDifferent($user, 'account_settings')) {
            return $this->redirectToRoute($route);
        }

        $clients = $user->getClients();
        $client = !empty($clients) ? $clients[0] : null;
        return [
            'client' => $client,
        ];
    }

    /**
     * @Route("/deputyship-details/your-details/change-password", name="user_password_edit")
     * @Route("/org/settings/your-details/change-password", name="org_profile_password_edit")
     * @Template("@App/Settings/passwordEdit.html.twig")
     */
    public function passwordEditAction(Request $request)
    {
        $user = $this->userApi->getUserWithData();

        $form = $this->createForm(FormDir\ChangePasswordType::class, $user, [
            'mapped' => true,
            'error_bubbling' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $request->request->get('change_password')['password']['first'];
            $this->restClient->put('user/' . $user->getId() . '/set-password', json_encode([
                'password_plain' => $plainPassword,
            ]));
            $request->getSession()->set('login-context', 'password-update');

            $successRoute = $this->getUser()->isDeputyOrg() ? 'org_settings' : 'account_settings';
            return $this->redirect($this->generateUrl($successRoute));
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * - display the Your details page
     *
     * @Route("/deputyship-details/your-details", name="user_show")
     * @Route("/org/settings/your-details", name="org_profile_show")
     * @Template("@App/Settings/profile.html.twig")
     **/
    public function profileAction()
    {
        return [
            'user' => $this->getUser()
        ];
    }

    /**
     * Change your own detials
     *
     * @Route("/deputyship-details/your-details/edit", name="user_edit")
     * @Route("/org/settings/your-details/edit", name="org_profile_edit")
     * @Template("@App/Settings/profileEdit.html.twig")
     * @throw AccessDeniedException
     **/
    public function profileEditAction(Request $request)
    {
        $preUpdateDeputy = $this->userApi->getUserWithData(['user-clients', 'client']);

        if ($this->isGranted(EntityDir\User::ROLE_ADMIN) || $this->isGranted(EntityDir\User::ROLE_AD)) {
            $form = $this->createForm(FormDir\User\UserDetailsBasicType::class, clone $preUpdateDeputy, []);
            $jmsPutGroups = ['user_details_basic'];
        } elseif ($this->isGranted(EntityDir\User::ROLE_LAY_DEPUTY)) {
            $form = $this->createForm(FormDir\Settings\ProfileType::class, clone $preUpdateDeputy, ['validation_groups' => ['user_details_full']]);
            $jmsPutGroups = ['user_details_full'];
        } elseif ($this->isGranted(EntityDir\User::ROLE_ORG)) {
            $form = $this->createForm(FormDir\Settings\ProfileType::class, clone $preUpdateDeputy, ['validation_groups' => ['user_details_org', 'profile_org']]);
            $jmsPutGroups = ['user_details_org', 'profile_org'];
        } else {
            throw $this->createAccessDeniedException('User role not recognised');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postUpdateDeputy = $form->getData();
            $newRole = $this->determineNoAdminRole();

            if ($form->has('removeAdmin') && !empty($form->get('removeAdmin')->getData())) {
                $postUpdateDeputy->setRoleName($newRole);

                $this->addFlash('notice', 'For security reasons you have been logged out because you have changed your admin rights. Please log in again below');

                $redirectRoute = $this->generateUrl('logout');
            } else {
                $this->addFlash('notice', 'Your account details have been updated');

                if ('declaration' === $request->get('from') && null !== $request->get('rid')) {
                    $redirectRoute = $this->generateUrl('report_declaration', ['reportId' => $request->get('rid')]);
                } elseif ($postUpdateDeputy->isDeputyPA() || $postUpdateDeputy->isDeputyProf()) {
                    $redirectRoute = $this->generateUrl('org_profile_show');
                } else {
                    $redirectRoute = $this->generateUrl('user_show');
                }
            }

            try {
                $this->userApi->update($preUpdateDeputy, $postUpdateDeputy, AuditEvents::TRIGGER_DEPUTY_USER_EDIT_SELF, $jmsPutGroups);
                return $this->redirect($redirectRoute);
            } catch (\Throwable $e) {
                if ($e->getCode() == 422 && $form->get('email')) {
                    $form->get('email')->addError(new FormError($this->translator->trans('user.email.alreadyUsed', [], 'validators')));
                }
            }
        }

        return [
            'user'   => $preUpdateDeputy,
            'form'   => $form->createView(),
            'client_validated' => false // to allow change of name/postcode/email
        ];
    }

    /**
     * If remove admin permission, return the new role for the user. Specifically added to prevent named PA deputies
     * becoming Professional team members.
     *
     * @throws AccessDeniedException
     * @return string
     */
    private function determineNoAdminRole()
    {
        if ($this->isGranted(EntityDir\User::ROLE_PA_ADMIN)) {
            return EntityDir\User::ROLE_PA_TEAM_MEMBER;
        } elseif ($this->isGranted(EntityDir\User::ROLE_PROF_ADMIN)) {
            return EntityDir\User::ROLE_PROF_TEAM_MEMBER;
        }

        return $this->createAccessDeniedException('User role not recognised');
    }
}
