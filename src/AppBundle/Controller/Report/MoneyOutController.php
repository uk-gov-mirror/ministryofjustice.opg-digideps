<?php

namespace AppBundle\Controller\Report;

use AppBundle\Controller\AbstractController;
use AppBundle\Entity as EntityDir;
use AppBundle\Form as FormDir;
use AppBundle\Service\ReportStatusService;
use AppBundle\Service\StepRedirector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class MoneyOutController extends AbstractController
{
    private static $jmsGroups = [
        'transactionsOut',
    ];



    /**
     * @Route("/report/{reportId}/money-out", name="money_out")
     * @Template()
     */
    public function startAction(Request $request, $reportId)
    {
        $report = $this->getReportIfNotSubmitted($reportId, self::$jmsGroups);
        if ($report->hasMoneyOut()) {
            return $this->redirectToRoute('money_out_summary', ['reportId' => $reportId]);
        }

        return [
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/money-out/step{step}/{transactionId}", name="money_out_step", requirements={"step":"\d+"})
     * @Template()
     */
    public function stepAction(Request $request, $reportId, $step, $transactionId = null)
    {
        $totalSteps = 3;
        if ($step < 1 || $step > $totalSteps) {
            return $this->redirectToRoute('money_out_summary', ['reportId' => $reportId]);
        }

        // common vars and data
        $dataFromUrl = $request->get('data') ?: [];
        $stepUrlData = $dataFromUrl;
        $report = $this->getReportIfNotSubmitted($reportId, self::$jmsGroups);
        $fromPage = $request->get('from');

        /* @var $stepRedirector StepRedirector */
        $stepRedirector = $this->get('stepRedirector')
            ->setRoutes('money_out', 'money_out_step', 'money_out_summary')
            ->setFromPage($fromPage)
            ->setCurrentStep($step)->setTotalSteps($totalSteps)
            ->setRouteBaseParams(['reportId'=>$reportId, 'transactionId' => $transactionId]);


        // create (add mode) or load transaction (edit mode)
        if ($transactionId) {
            $transaction = array_filter($report->getMoneyTransactionsOut(), function($t) use ($transactionId) {
                return $t->getId() == $transactionId;
            });
            $transaction = array_shift($transaction);
        } else {
            $transaction = new EntityDir\Report\MoneyTransaction();
        }

        // add URL-data into model
        isset($dataFromUrl['group']) && $transaction->setGroup($dataFromUrl['group']);
        isset($dataFromUrl['category']) && $transaction->setCategory($dataFromUrl['category']);
        $stepRedirector->setStepUrlAdditionalParams([
            'data' => $dataFromUrl
        ]);

        // crete and handle form
        $form = $this->createForm(new FormDir\Report\MoneyTransactionType(
            $step, 'out', $this->get('translator'), $report->getClient()->getFirstname(),
            $transaction->getGroup(), $transaction->getCategory()
        ), $transaction);
        $form->handleRequest($request);

        if ($form->get('save')->isClicked() && $form->isValid()) {
            // decide what data in the partial form needs to be passed to next step
            if ($step == 1) {
                $stepUrlData['group'] = $transaction->getGroup();
            } else if ($step == 2) {
                $stepUrlData['category'] = $transaction->getCategory();
            } else if ($step == $totalSteps) {
                if ($transactionId) { // edit
                    $request->getSession()->getFlashBag()->add(
                        'notice',
                        'Entry edited'
                    );
                    $this->getRestClient()->put('/report/'.$reportId.'/money-transaction/'.$transactionId, $transaction, ['transaction']);
                    return $this->redirectToRoute('money_out_summary', ['reportId' => $reportId]);
                } else { // add
                    $this->getRestClient()->post('/report/'.$reportId.'/money-transaction', $transaction, ['transaction']);
                    return $this->redirectToRoute('money_out_add_another', ['reportId' => $reportId]);
                }
            }

            $stepRedirector->setStepUrlAdditionalParams([
                'data' => $stepUrlData
            ]);

            return $this->redirect($stepRedirector->getRedirectLinkAfterSaving());
        }

        return [
            'transaction' => $transaction,
            'report' => $report,
            'step' => $step,
            'reportStatus' => new ReportStatusService($report),
            'form' => $form->createView(),
            'backLink' => $stepRedirector->getBackLink(),
            'skipLink' => null,
        ];
    }

    /**
     * @Route("/report/{reportId}/money-out/add_another", name="money_out_add_another")
     * @Template()
     */
    public function addAnotherAction(Request $request, $reportId)
    {
        $report = $this->getReportIfNotSubmitted($reportId);

        $form = $this->createForm(new FormDir\Report\MoneyTransactionAddAnotherType(), $report);
        $form->handleRequest($request);

        if ($form->isValid()) {
            switch ($form['addAnother']->getData()) {
                case 'yes':
                    return $this->redirectToRoute('money_out_step', ['reportId' => $reportId, 'step' => 1]);
                case 'no':
                    return $this->redirectToRoute('money_out_summary', ['reportId' => $reportId]);
            }
        }

        return [
            'form' => $form->createView(),
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/money-out/summary", name="money_out_summary")
     *
     * @param int $reportId
     * @Template()
     *
     * @return array
     */
    public function summaryAction($reportId)
    {
        $report = $this->getReportIfNotSubmitted($reportId, self::$jmsGroups);
        if (!$report->hasMoneyOut()) {
            return $this->redirectToRoute('money_out', ['reportId' => $reportId]);
        }

        return [
            'report' => $report,
        ];
    }

    /**
     * @Route("/report/{reportId}/money-out/{transactionId}/delete", name="money_out_delete")
     *
     * @param int $reportId
     * @param int $transactionId
     *
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, $reportId, $transactionId)
    {
        $report = $this->getReportIfNotSubmitted($reportId, self::$jmsGroups);
        $transaction = array_filter($report->getMoneyTransactionsOut(), function($t) use ($transactionId) {
            return $t->getId() == $transactionId;
        });
        if (!$transaction) {
            throw new \RuntimeException('Transaction not found');
        }
        $this->getRestClient()->delete('/report/'.$reportId.'/money-transaction/' . $transactionId);

        $request->getSession()->getFlashBag()->add(
            'notice',
            'Entry deleted'
        );

        return $this->redirect($this->generateUrl('money_out_summary', ['reportId' => $reportId]));
    }
}
