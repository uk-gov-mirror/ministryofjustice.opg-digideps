<?php

namespace App\Controller\Report;

use App\Controller\AbstractController;
use App\Entity\Client;
use App\Entity\DeputyInterface;
use App\Entity\Report\Report;
use App\Entity\User;
use App\Exception\DisplayableException;
use App\Exception\ReportNotSubmittableException;
use App\Exception\ReportNotSubmittedException;
use App\Form\FeedbackReportType;
use App\Form\Report\ReportDeclarationType;
use App\Form\Report\ReportType;
use App\Form\UserResearchResponseType;
use App\Model\FeedbackReport;
use App\Service\Client\Internal\CasrecApi;
use App\Service\Client\Internal\ClientApi;
use App\Service\Client\Internal\ReportApi;
use App\Service\Client\Internal\SatisfactionApi;
use App\Service\Client\Internal\UserApi;
use App\Service\Client\Internal\UserResearchApi;
use App\Service\Client\RestClient;
use App\Service\Csv\TransactionsCsvGenerator;
use App\Service\Redirector;
use App\Service\ReportSubmissionService;
use DateTime;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportController extends AbstractController
{
    /**
     * JMS groups used for report preview and PDF
     *
     * @var array
     */
    private static $reportGroupsAll = [
        'report',
        'client',
        'account',
        'expenses',
        'fee',
        'gifts',
        'prof-deputy-other-costs',
        'prof-deputy-costs-how-charged',
        'report-prof-deputy-costs',
        'report-prof-deputy-costs-prev', 'prof-deputy-costs-prev',
        'report-prof-deputy-costs-interim', 'prof-deputy-costs-interim',
        'report-prof-deputy-costs-scco',
        'report-prof-deputy-fixed-cost',
        'prof-deputy-costs-estimate-how-charged',
        'prof-deputy-estimate-costs',
        'prof-deputy-costs-estimate-more-info',
        'prof-deputy-estimate-management-costs',
        'action',
        'action-more-info',
        'asset',
        'debt',
        'debt-management',
        'fee',
        'balance',
        'client',
        'contact',
        'debts',
        'decision',
        'visits-care',
        'lifestyle',
        'mental-capacity',
        'money-transfer',
        'transaction',
        'transactionsIn',
        'transactionsOut',
        'moneyShortCategoriesIn',
        'moneyShortCategoriesOut',
        'moneyTransactionsShortIn',
        'moneyTransactionsShortOut',
        'status',
        'report-submitted-by',
        'client-named-deputy',
        'wish-to-provide-documentation',
        'report-documents',
        'balance-state',
        'documents',
        'report-prof-service-fees',
        'prof-service-fees',
        'client-named-deputy',
        'unsubmitted-reports-count'
    ];

    private RestClient $restClient;
    private ReportApi$reportApi;
    private UserApi $userApi;
    private ClientApi $clientApi;
    private SatisfactionApi $satisfactionApi;
    private CasrecApi $casrecApi;
    private FormFactoryInterface $formFactory;
    private TranslatorInterface $translator;

    public function __construct(
        RestClient $restClient,
        ReportApi $reportApi,
        UserApi $userApi,
        ClientApi $clientApi,
        SatisfactionApi $satisfactionApi,
        CasrecApi $casrecApi,
        FormFactoryInterface $formFactory,
        TranslatorInterface $translator
    ) {
        $this->restClient = $restClient;
        $this->reportApi = $reportApi;
        $this->userApi = $userApi;
        $this->clientApi = $clientApi;
        $this->satisfactionApi = $satisfactionApi;
        $this->casrecApi = $casrecApi;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    /**
     * List of reports.
     *
     * @Route("/lay", name="lay_home")
     * //TODO we should add Security("is_granted('ROLE_LAY_DEPUTY')") here, but not sure as not clear what "getCorrectRouteIfDifferent" does
     * @Template("@App/Report/Report/index.html.twig")
     *
     * @param Redirector $redirector
     *
     * @return array|RedirectResponse
     */
    public function indexAction(Redirector $redirector)
    {
        // not ideal to specify both user-client and client-users, but can't fix this differently with DDPB-1711. Consider a separate call to get
        // due to the way
        $user = $this->userApi->getUserWithData(['user-clients', 'client', 'client-reports', 'report', 'status']);

        // redirect if user has missing details or is on wrong page
        $route = $redirector->getCorrectRouteIfDifferent($user, 'lay_home');
        if (is_string($route)) {
            return $this->redirectToRoute($route);
        }

        $clients = $user->getClients();
        if (empty($clients)) {
            throw $this->createNotFoundException('Client not added');
        }
        $client = array_shift($clients);

        //refresh client adding codeputes (another API call to avoid recursion with users)
        $clientWithCoDeputies = $this->clientApi->getWithUsersV2($client->getId());
        $coDeputies = $clientWithCoDeputies->getCoDeputies();

        return [
            'user' => $user,
            'clientHasCoDeputies' => $this->casrecApi->clientHasCoDeputies($client->getCaseNumber()),
            'client' => $client,
            'coDeputies' => $coDeputies,
        ];
    }

    /**
     * Edit single report
     *
     * @Route("/reports/edit/{reportId}", name="report_edit")
     * @Template("@App/Report/Report/edit.html.twig")
     *
     * @param Request $request
     * @param $reportId
     *
     * @return array|RedirectResponse
     * @throws \Exception
     */
    public function editAction(Request $request, $reportId)
    {
        $report = $this->reportApi->getReportIfNotSubmitted($reportId);
        $client = $report->getClient();

        /** @var User */
        $user = $this->getUser();

        $editReportDatesForm = $this->formFactory->createNamed('report_edit', ReportType::class, $report, [ 'translation_domain' => 'report']);
        $returnLink = $user->isDeputyOrg()
            ? $this->clientApi->generateClientProfileLink($report->getClient())
            : $this->generateUrl('lay_home');

        $editReportDatesForm->handleRequest($request);
        if ($editReportDatesForm->isSubmitted() && $editReportDatesForm->isValid()) {
            $this->restClient->put('report/' . $reportId, $report, ['startEndDates']);

            return $this->redirect($returnLink);
        }

        return [
            'client' => $client,
            'report' => $report,
            'form' =>  $editReportDatesForm->createView(),
            'returnLink' => $returnLink
        ];
    }

    /**
     * Create report
     * default action "create" will create only one report (used during registration steps to avoid duplicates when going back from the browser)
     * action "add" will instead add another report.
     *
     *
     * @Route("/report/{action}/{clientId}", name="report_create",
     *   defaults={ "action" = "create"},
     *   requirements={ "action" = "(create|add)"}
     * )
     * @Template("@App/Report/Report/create.html.twig")
     *
     * @param Request $request
     * @param $clientId
     *
     * @return array|RedirectResponse
     */
    public function createAction(Request $request, $clientId, $action = false)
    {
        $client = $this->restClient->get('client/' . $clientId, 'Client', ['client', 'client-id', 'client-reports', 'report-id']);

        $existingReports = $this->reportApi->getReportsIndexedById($client);

        if (count($existingReports)) {
            throw $this->createAccessDeniedException('Client already has a report');
        }

        $report = new Report();
        $report->setClient($client);

        $form = $this->formFactory->createNamed(
            'report',
            ReportType::class,
            $report,
            [
                'translation_domain' => 'registration',
                'action'             => $this->generateUrl('report_create', ['clientId' => $clientId]) //TODO useless ?
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->restClient->post('report', $form->getData());
            return $this->redirect($this->generateUrl('homepage'));
        }

        return ['form' => $form->createView()];
    }

    /**
     * @Route("/report/{reportId}/overview", name="report_overview")
     * @Template("@App/Report/Report/overview.html.twig")
     *
     * @param Redirector $redirector
     * @param $reportId
     *
     * @return RedirectResponse|Response|null
     */
    public function overviewAction(Redirector $redirector, $reportId)
    {
        $reportJmsGroup = ['status', 'balance', 'user', 'client', 'client-reports', 'balance-state'];
        // redirect if user has missing details or is on wrong page
        $user = $this->userApi->getUserWithData();

        $route = $redirector->getCorrectRouteIfDifferent($user, 'report_overview');
        if (is_string($route)) {
            return $this->redirectToRoute($route);
        }

        // get all the groups (needed by EntityDir\Report\Status
        $clientId = $this->reportApi->getReportIfNotSubmitted($reportId, $reportJmsGroup)->getClient()->getId();

        /** @var Client */
        $client = $this->generateClient($user, $clientId);

        $activeReportId = null;
        if ($user->isDeputyOrg()) {
            // PR and PROF: unsubmitted at the top (if exists), active below (
            $template = '@App/Org/ClientProfile/overview.html.twig';

            // if there is an unsubmitted report, swap them, so linkswill both show the unsubmitted first
            $unsubmittedReport = $client->getUnsubmittedReport();
            if ($unsubmittedReport instanceof Report) {
                $reportId = $unsubmittedReport->getId();

                $activeReport = $client->getActiveReport();
                if ($activeReport instanceof Report) {
                    $activeReportId = $activeReport->getId();
                }
            }
        } else { // Lay. keep the report Id
            $template = '@App/Report/Report/overview.html.twig';
        }

        $report = $this->reportApi->getReportIfNotSubmitted($reportId, $reportJmsGroup);
        $activeReport = $activeReportId ? $this->reportApi->getReportIfNotSubmitted($activeReportId, $reportJmsGroup) : null;

        return $this->render($template, [
            'user' => $user,
            'client' => $client,
            'report' => $report,
            'activeReport' => $activeReport,
        ]);
    }

    /**
     * Due to some profs having many dozens of deputies attached to clients, we need to be conservative about generating
     * the list. Its needed for a permissions check on add client contact (logged in user has to be associated)
     *
     * @param User $user
     * @param $clientId
     *
     * @return Client
     */
    private function generateClient(User $user, $clientId)
    {
        $jms = $this->determineJmsGroups($user);

        /* Get client with all other JMS groups required */
        $client = $this->restClient->get('client/' . $clientId, 'Client', $jms);

        if ($user->isDeputyOrg()) {
            /*
            Separate call to get client Users as query taking too long for some profs with many deputies attached.
            We only need the user id for the add client contact permission check
             */
            $clientWithUsers = $this->restClient->get('client/' . $clientId, 'Client', ['user-id', 'client-users']);
            $client->setUsers($clientWithUsers->getUsers());
        }

        return $client;
    }

    /**
     * Method to return JMS groups required for overview page.
     *
     * @param User $user
     * @return array
     */
    private function determineJmsGroups(User $user)
    {
        $jms = [
            'client',
            'user',
            'client-reports',
            'report', //needed ?
            'client-clientcontacts',
            'clientcontact',
            'client-notes',
            'notes',
        ];

        if ($user->isLayDeputy()) {
            $jms[] = 'client-users';
        }

        return $jms;
    }

    /**
     * @Route("/report/{reportId}/declaration", name="report_declaration")
     * @Template("@App/Report/Report/declaration.html.twig")
     *
     * @param Request $request
     * @param $reportId
     * @param ReportSubmissionService $reportSubmissionService
     *
     * @return array|RedirectResponse
     */
    public function declarationAction(Request $request, $reportId, ReportSubmissionService $reportSubmissionService)
    {
        $report = $this->reportApi->getReportIfNotSubmitted($reportId, self::$reportGroupsAll);

        // check status
        $status = $report->getStatus();
        if (!$report->isDue() || !$status->getIsReadyToSubmit()) {
            $message = $this->translator->trans('report.submissionExceptions.readyForSubmission', [], 'validators');
            throw new ReportNotSubmittableException($message);
        }

        $deputy = $report->getClient()->getNamedDeputy();

        if (is_null($deputy)) {
            $deputy = $this->userApi->getUserWithData();
        }

        $form = $this->createForm(ReportDeclarationType::class, $report);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $currentUser */
            $currentUser = $this->getUser();

            $report->setSubmitted(true)->setSubmitDate(new DateTime());
            $reportSubmissionService->generateReportDocuments($report);

            $this->reportApi->submit($report, $currentUser);

            return $this->redirect($this->generateUrl('report_submit_confirmation', ['reportId' => $report->getId()]));
        }

        return [
            'report' => $report,
            'client' => $report->getClient(),
            'contactDetails' => $this->getAssociatedContactDetails($deputy, $report),
            'form' => $form->createView(),
        ];
    }

    /**
     * Page displaying the report has been submitted.
     *
     * @Route("/report/{reportId}/submitted", name="report_submit_confirmation")
     * @Template("@App/Report/Report/submitConfirmation.html.twig")
     *
     * @param Request $request
     * @param $reportId
     *
     * @return array|RedirectResponse
     */
    public function submitConfirmationAction(Request $request, $reportId)
    {
        $report = $this->reportApi->getReport($reportId, ['status']);

        // check status
        if (!$report->getSubmitted()) {
            $message = $this->translator->trans('report.submissionExceptions.submitted', [], 'validators');
            throw new ReportNotSubmittedException($message);
        }

        $form = $this->createForm(FeedbackReportType::class, new FeedbackReport());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->satisfactionApi->createPostSubmissionFeedback($form->getData(), $report->getType(), $this->getUser());

            return $this->redirect($this->generateUrl('report_post_submission_user_research', ['reportId' => $reportId]));
        }

        return [
            'report' => $report,
            'form' => $form->createView(),
            'homePathName' => $this->getUser()->isLayDeputy() ? 'lay_home' : "org_dashboard"
        ];
    }

    // @TODO Remove once ticket complete along with view
    /**
     * @Route("/report/{reportId}/submit_feedback", name="report_submit_feedback")
     * @Template("@App/Report/Report/submitFeedback.html.twig")
     * @param $reportId
     * @return array
     */
    public function submitFeedbackAction($reportId)
    {
        $report = $this->reportApi->getReport($reportId, self::$reportGroupsAll);

        // check status
        if (!$report->getSubmitted()) {
            $message = $this->translator->trans('report.submissionExceptions.submitted', [], 'validators');
            throw new ReportNotSubmittedException($message);
        }

        return [
            'report' => $report,
        ];
    }

    /**
     * Used for active and archived report.
     *
     * @Route("/report/{reportId}/review", name="report_review")
     * @Template("@App/Report/Report/review.html.twig")
     *
     * @param $reportId
     * @return array
     *
     * @throws \Exception
     */
    public function reviewAction($reportId)
    {
        $report = $this->reportApi->getReport($reportId, self::$reportGroupsAll);

        // check status
        $status = $report->getStatus();

        /** @var User $user */
        $user = $this->getUser();

        if ($user->isDeputyOrg()) {
            $backLink = $this->clientApi->generateClientProfileLink($report->getClient());
        } else {
            $backLink = $this->generateUrl('lay_home');
        }

        return [
            'user' => $this->getUser(),
            'report' => $report,
            'reportStatus' => $status,
            'backLink' => $backLink,
            'feeTotals' => $report->getFeeTotals()
        ];
    }

    /**
     * Used for active and archived report.
     *
     * @Route("/report/{reportId}/pdf-debug")
     * @param $reportId
     * @return Response|null
     */
    public function pdfDebugAction($reportId)
    {
        if (!$this->getParameter('kernel.debug')) {
            throw new DisplayableException('Route only visite in debug mode');
        }
        $report = $this->reportApi->getReport($reportId, self::$reportGroupsAll);

        return $this->render('@App/Report/Formatted/formatted_standalone.html.twig', [
            'report' => $report,
            'showSummary' => true
        ]);
    }

    /**
     * @Route("/report/deputyreport-{reportId}.pdf", name="report_pdf")
     *
     * @param $reportId
     * @param ReportSubmissionService $reportSubmissionService
     *
     * @return Response
     */
    public function pdfViewAction($reportId, ReportSubmissionService $reportSubmissionService)
    {
        $report = $this->reportApi->getReport($reportId, self::$reportGroupsAll);
        $pdfBinary = $reportSubmissionService->getPdfBinaryContent($report);

        $response = new Response($pdfBinary);
        $response->headers->set('Content-Type', 'application/pdf');

        $submitDate = $report->getSubmitDate();
        /** @var DateTime $endDate */
        $endDate = $report->getEndDate();

        $attachmentName = sprintf(
            'DigiRep-%s_%s_%s.pdf',
            $endDate->format('Y'),
            $submitDate instanceof DateTime ? $submitDate->format('Y-m-d') : 'n-a-', //some old reports have no submission date
            $report->getClient()->getCaseNumber()
        );

        $response->headers->set('Content-Disposition', 'attachment; filename="' . $attachmentName . '"');

        // Send headers before outputting anything
        $response->sendHeaders();

        return $response;
    }

    /**
     * Generates Transactions CSV and returns as CSV file response
     *
     * @Route("/report/transactions-{reportId}.csv", name="report_transactions_csv")
     *
     * @param $reportId
     * @param TransactionsCsvGenerator $csvGenerator
     *
     * @return Response
     */
    public function transactionsCsvViewAction($reportId, TransactionsCsvGenerator $csvGenerator)
    {
        $report = $this->reportApi->getReport($reportId, self::$reportGroupsAll);

        // restrict access to only 102, 102-4 reports
        $reportType = $report->getType();
        if (!in_array($reportType, ['102', '102-4'])) {
            throw $this->createAccessDeniedException('Access denied');
        }

        $csvContent = $csvGenerator->generateTransactionsCsv($report);

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv');

        $submitDate = $report->getSubmitDate();
        /** @var DateTime $endDate */
        $endDate = $report->getEndDate();

        $attachmentName = sprintf(
            'DigiRepTransactions-%s_%s_%s.csv',
            $endDate->format('Y'),
            $submitDate instanceof DateTime ? $submitDate->format('Y-m-d') : 'n-a-', //some old reports have no submission date
            $report->getClient()->getCaseNumber()
        );

        $response->headers->set('Content-Disposition', 'attachment; filename="' . $attachmentName . '"');

        // Send headers before outputting anything
        $response->sendHeaders();

        return $response;
    }

    /**
     * @param DeputyInterface $deputy
     * @param Report $report
     *
     * @return array
     */
    private function getAssociatedContactDetails(DeputyInterface $deputy, Report $report)
    {
        return [
            'client' => $this->getClientContactDetails($report),
            'deputy' => $this->getDeputyContactDetails($deputy, $report),
        ];
    }

    /**
     * @param Report $report
     *
     * @return array
     */
    private function getClientContactDetails(Report $report)
    {
        $client = $report->getClient();

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        return [
            'name' => $client->getFullName() . ' (client)',
            'address' => $client->getAddressNotEmptyParts(),
            'phone' => ['main' => $client->getPhone()],
            'email' => $client->getEmail(),
            'editUrl' => $currentUser->isLayDeputy() ?
                $this->generateUrl('client_edit', ['from' => 'declaration']) :
                $this->generateUrl('org_client_edit', ['clientId' => $client->getId(), 'from' => 'declaration'])
        ];
    }

    /**
     * @param DeputyInterface $deputy
     * @param Report $report
     *
     * @return array
     */
    private function getDeputyContactDetails(DeputyInterface $deputy, Report $report)
    {
        if ($deputy instanceof User) {
            if ($deputy->isLayDeputy()) {
                $editUrl = $this->generateUrl('user_edit', ['from' => 'declaration', 'rid' => $report->getId()]);
            } else {
                $editUrl = $this->generateUrl('org_profile_edit', ['from' => 'declaration', 'rid' => $report->getId()]);
            }
        } else {
            $editUrl = '';
        }

        return [
            'name' => $deputy->getFullName() . ' (deputy)',
            'address' => $deputy->getAddressNotEmptyParts(),
            'phone' => [
                'main' => $deputy->getPhoneMain(),
                'alternative' => $deputy->getPhoneAlternative()
            ],
            'email' => $deputy->getEmail(),
            'editUrl' => $editUrl
        ];
    }
}
