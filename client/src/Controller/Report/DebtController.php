<?php

namespace App\Controller\Report;

use App\Controller\AbstractController;
use App\Entity as EntityDir;
use App\Form as FormDir;
use App\Service\Client\Internal\ReportApi;
use App\Service\Client\RestClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DebtController extends AbstractController
{
    private static $jmsGroups = [
        'debt',
        'debt-state',
        'debt-management'
    ];

    /** @var RestClient */
    private $restClient;

    /** @var ReportApi */
    private $reportApi;

    public function __construct(
        RestClient $restClient,
        ReportApi $reportApi
    ) {
        $this->restClient = $restClient;
        $this->reportApi = $reportApi;
    }

    /**
     * @Route("/report/{reportId}/debts", name="debts")
     * @Template("@App/Report/Debt/start.html.twig")
     *
     * @param Request $request
     * @param $reportId
     *
     * @return array|RedirectResponse
     */
    public function startAction(Request $request, $reportId)
    {
        $report = $this->reportApi->getReportIfNotSubmitted($reportId, self::$jmsGroups);
        if ($report->getStatus()->getDebtsState()['state'] != EntityDir\Report\Status::STATE_NOT_STARTED) {
            return $this->redirectToRoute('debts_summary', ['reportId' => $reportId]);
        }

        return [
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/debts/exist", name="debts_exist")
     * @Template("@App/Report/Debt/exist.html.twig")
     *
     * @param Request $request
     * @param $reportId
     *
     * @return array|RedirectResponse
     */
    public function existAction(Request $request, $reportId)
    {
        $report = $this->reportApi->getReportIfNotSubmitted($reportId, self::$jmsGroups);
        $form = $this->createForm(
            FormDir\YesNoType::class,
            $report,
            [ 'field' => 'hasDebts', 'translation_domain' => 'report-debts']
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->restClient->put('report/' . $reportId, $report, ['debt']);

            if ($report->getHasDebts() == 'yes') {
                return $this->redirectToRoute('debts_edit', ['reportId' => $reportId]);
            }

            return $this->redirectToRoute('debts_summary', ['reportId' => $reportId]);
        }

        $backLink = $this->generateUrl('debts', ['reportId'=>$reportId]);
        if ($request->get('from') == 'summary') {
            $backLink = $this->generateUrl('debts_summary', ['reportId'=>$reportId]);
        }

        return [
            'backLink' => $backLink,
            'form' => $form->createView(),
            'report' => $report,
        ];
    }

    /**
     * List debts.
     *
     * @Route("/report/{reportId}/debts/edit", name="debts_edit")
     * @Template("@App/Report/Debt/edit.html.twig")
     *
     * @param Request $request
     * @param $reportId
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, $reportId)
    {
        $report = $this->reportApi->getReportIfNotSubmitted($reportId, self::$jmsGroups);

        $form = $this->createForm(FormDir\Report\Debt\DebtsType::class, $report);
        $form->handleRequest($request);

        $fromPage = $request->get('from');

        if ($form->isSubmitted() && $form->isValid()) {
            $this->restClient->put('report/' . $report->getId(), $form->getData(), ['debt']);

            if ($fromPage == 'summary') {
                if (empty($report->getDebtManagement())) {
                    return $this->redirect($this->generateUrl('debts_management', ['reportId' => $reportId, 'from' => 'summary']));
                }
                $request->getSession()->getFlashBag()->add('notice', 'Debt edited');
                return $this->redirect($this->generateUrl('debts_summary', ['reportId' => $reportId, 'from' => 'summary']));
            }

            return $this->redirect($this->generateUrl('debts_management', ['reportId' => $reportId]));
        }

        $backLink = $this->generateUrl('debts_exist', ['reportId'=>$reportId]);
        if ($fromPage == 'summary') {
            $backLink = $this->generateUrl('debts_summary', ['reportId'=>$reportId]);
        }

        return [
            'backLink' => $backLink,
            'report' => $report,
            'form' => $form->createView(),
        ];
    }

    /**
     * How debts are managed question.
     *
     * @Route("/report/{reportId}/debts/management", name="debts_management")
     * @Template("@App/Report/Debt/management.html.twig")
     *
     * @param Request $request
     * @param $reportId
     * @return array|RedirectResponse
     *
     */
    public function managementAction(Request $request, $reportId)
    {
        $report = $this->reportApi->getReportIfNotSubmitted($reportId, self::$jmsGroups);
        $form = $this->createForm(FormDir\Report\Debt\DebtManagementType::class, $report);
        $form->handleRequest($request);
        $fromPage = $request->get('from');
        $fromSummaryPage = $request->get('from') == 'summary';

        if ($form->isSubmitted() && $form->isValid()) {
            $this->restClient->put('report/' . $report->getId(), $form->getData(), ['debt-management']);

            if ($fromPage == 'summary') {
                $request->getSession()->getFlashBag()->add('notice', 'Answer edited');
            }

            return $this->redirect($this->generateUrl('debts_summary', ['reportId' => $reportId]));
        }

        $backLink = $this->generateUrl('debts_exist', ['reportId'=>$reportId]);
        if ($fromPage == 'summary') {
            $backLink = $this->generateUrl('debts_summary', ['reportId'=>$reportId]);
        }

        return [
            'backLink' => $backLink,
            'skipLink' => $fromSummaryPage ? null : $this->generateUrl('debts_summary', ['reportId' => $report->getId()]),
            'report' => $report,
            'form' => $form->createView(),
        ];
    }

    /**
     * List debts.
     *
     * @Route("/report/{reportId}/debts/summary", name="debts_summary")
     * @Template("@App/Report/Debt/summary.html.twig")
     *
     * @param Request $request
     * @param $reportId
     *
     * @return array|RedirectResponse
     */
    public function summaryAction(Request $request, $reportId)
    {
        $fromPage = $request->get('from');
        $report = $this->reportApi->getReportIfNotSubmitted($reportId, self::$jmsGroups);
        if ($report->getStatus()->getDebtsState()['state'] == EntityDir\Report\Status::STATE_NOT_STARTED && $fromPage != 'skip-step') {
            return $this->redirectToRoute('debts', ['reportId' => $reportId]);
        }

        return [
            'comingFromLastStep' => $fromPage == 'skip-step' || $fromPage == 'last-step',
            'report' => $report,
            'status' => $report->getStatus()
        ];
    }

    /**
     * @return string
     */
    protected function getSectionId()
    {
        return 'debts';
    }
}
