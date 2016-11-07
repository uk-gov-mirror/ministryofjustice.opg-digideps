<?php

namespace AppBundle\Controller\Report;

use AppBundle\Controller\AbstractController;
use AppBundle\Entity as EntityDir;
use AppBundle\Form as FormDir;
use AppBundle\Service\ReportStatusService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Service\SectionValidator\VisitsCareValidator;
use AppBundle\Service\StepRedirector;
use Symfony\Component\HttpFoundation\Request;

class VisitsCareController extends AbstractController
{
    const STEPS = 4;

    /**
     * @Route("/report/{reportId}/visits-care/start", name="visits_care")
     * @Template()
     */
    public function startAction(Request $request, $reportId)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId, ['visits-care']);
        if ($report->getVisitsCare() != null) {
            return $this->redirectToRoute('visits_care_summary_overview', ['reportId' => $reportId]);
        }

        return [
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/visits-care/step/{step}", name="visits_care_step")
     * @Template()
     */
    public function stepAction(Request $request, $reportId, $step)
    {
        if ($step < 1 || $step > self::STEPS) {
            return $this->redirectToRoute('visits_care_summary_overview', ['reportId' => $reportId]);
        }
        $report = $this->getReportIfReportNotSubmitted($reportId, ['visits-care']);
        $visitsCare = $report->getVisitsCare() ?: new EntityDir\Report\VisitsCare();
        $fromPage = $request->get('from');

        /* @var $stepRedirector StepRedirector */
        $stepRedirector = $this->get('stepRedirector')
            ->setRoutePrefix('visits_care')
            ->setFromPage($fromPage)
            ->setCurrentStep($step)->setTotalSteps(self::STEPS)
            ->setRouteBaseParams(['reportId'=>$reportId]);

        $form = $this->createForm(new FormDir\Report\VisitsCareType($step, $this->get('translator'), $report->getClient()->getFirstname()), $visitsCare);
        $form->handleRequest($request);

        if ($form->get('save')->isClicked() && $form->isValid()) {
            $data = $form->getData();
            /* @var $data EntityDir\Report\VisitsCare */
            $data
                ->setReport($report)
                ->keepOnlyRelevantVisitsCareData();

            if ($visitsCare->getId() == null) {
                $this->getRestClient()->post('report/visits-care', $data, ['visits-care', 'report-id']);
            } else {
                $this->getRestClient()->put('report/visits-care/' . $visitsCare->getId(), $data, ['visits-care']);
            }

            if ($fromPage)  {
                $request->getSession()->getFlashBag()->add(
                    'notice',
                    'Record edited'
                );
            }

            return $this->redirect($stepRedirector->getRedirectLinkAfterSaving());
        }


        return [
            'report' => $report,
            'step' => $step,
            'reportStatus' => new ReportStatusService($report),
            'form' => $form->createView(),
            'backLink' => $stepRedirector->getBackLink(),
            'skipLink' => $stepRedirector->getSkipLink(),
        ];
    }

    /**
     * @Route("/report/{reportId}/visits-care/summary-check", name="visits_care_summary_check")
     * @Template()
     */
    public function summaryCheckAction(Request $request, $reportId)
    {
        $fromPage = $request->get('from');
        $report = $this->getReportIfReportNotSubmitted($reportId, ['visits-care']);
        if (!$report->getVisitsCare() && $fromPage != 'skip-step') {
            return $this->redirectToRoute('visits_care', ['reportId' => $reportId]);
        }

        if (!$report->getVisitsCare()) { //allow validation with answers all skipped
            $report->setVisitsCare(new EntityDir\Report\VisitsCare());
        }

        return [
            'report' => $report,
            'validator' => new VisitsCareValidator($report->getVisitsCare()),
        ];
    }

    /**
     * @Route("/report/{reportId}/visits-care/summary-overview", name="visits_care_summary_overview")
     * @Template()
     */
    public function summaryOverviewAction(Request $request, $reportId)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId, ['visits-care']);
        if (!$report->getVisitsCare()) {
            return $this->redirectToRoute('visits_care', ['reportId' => $reportId]);
        }

        return [
            'report' => $report,
            'validator' => new VisitsCareValidator($report->getVisitsCare()),
        ];
    }
}
