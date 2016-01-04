<?php
namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use AppBundle\Form as FormDir;
use AppBundle\Service\Client\RestClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends AbstractController
{
    
    /**
     * @Route("/report/{reportId}/accounts/moneyin", name="accounts_moneyin")
     * @param integer $reportId
     * @param Request $request
     * @Template()
     * @return array
     */
    public function moneyinAction(Request $request, $reportId) {

        $report = $this->getReport($reportId, [ 'transactionsIn', 'basic', 'client', 'balance']);
        if ($report->getSubmitted()) {
            throw new \RuntimeException("Report already submitted and not editable.");
        }
        
        $form = $this->createForm(new FormDir\TransactionsType('transactionsIn'), $report);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('restClient')->put('report/' .  $report->getId(), $form->getData(), [
                'deserialise_group' => 'transactionsIn',
            ]);
        }

        return [
            'report' => $report,
            'subsection' => 'moneyin',
            'jsonEndpoint' => 'transactionsIn',
            'form' => $form->createView()
        ];
        
    }

    /**
     * @Route("/report/{reportId}/accounts/moneyout", name="accounts_moneyout")
     * @param integer $reportId
     * @param Request $request
     * @Template()
     * @return array
     */
    public function moneyoutAction(Request $request, $reportId) 
    {
        $report = $this->getReport($reportId, [ 'transactionsOut', 'basic', 'client', 'balance']);
        if ($report->getSubmitted()) {
            throw new \RuntimeException("Report already submitted and not editable.");
        }
        
        $form = $this->createForm(new FormDir\TransactionsType('transactionsOut'), $report);
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $this->get('restClient')->put('report/' .  $report->getId(), $form->getData(), [
                'deserialise_group' => 'transactionsOut',
            ]);
        }
        
        return [
            'report' => $report,
            'subsection' => "moneyout",
            'jsonEndpoint' => 'transactionsIn',
            'form' => $form->createView()
        ];
        
    }

    /**
     * @Route("/report/{reportId}/accounts/balance", name="accounts_balance")
     * @param integer $reportId
     * @Template()
     * @return array
     */
    public function balanceAction(Request $request, $reportId)
    {
        $restClient = $this->get('restClient'); /* @var $restClient RestClient */
        
        $report = $this->getReport($reportId, [ 'basic', 'balance', 'client', 'transactionsIn', 'transactionsOut']);
        $accounts = $restClient->get("/report/{$reportId}/accounts", 'Account[]');
        $report->setAccounts($accounts);
        
        if ($report->getSubmitted()) {
            throw new \RuntimeException("Report already submitted and not editable.");
        }

        $form = $this->createForm(new FormDir\ReasonForBalanceType(), $report);
        $form->handleRequest($request);

        if ($form->isValid()) {

            $data = $form->getData();
            $this->get('restClient')->put('report/' . $reportId, $data, [
                'deserialise_group' => 'balance_mismatch_explanation'
            ]);
        }
        
        return [
            'report' => $report,
            'form' => $form->createView(),
            'subsection' => 'balance'
        ];
        
    }
    
    /**
     * @Route("/report/{reportId}/accounts", name="accounts")
     * @param integer $reportId
     * @Template()
     * @return array
     */
    public function banksAction($reportId) 
    {
        $report = $this->getReport($reportId, ['basic', 'client', 'balance', 'accounts']);
        if ($report->getSubmitted()) {
            throw new \RuntimeException("Report already submitted and not editable.");
        }
        
        return [
            'report' => $report,
            'subsection' => 'banks'
        ];
    }
    
    /**
     * @Route("/{reportId}/accounts/banks/add", name="add_account")
     * @param integer $reportId
     * @param Request $request
     * @Template()
     * @return array    
     */
    public function addAction(Request $request, $reportId) 
    {

        $report = $this->getReportIfReportNotSubmitted($reportId, ['transactions', 'basic', 'client', 'client']);

        $account = new EntityDir\Account();
        $account->setReport($report);
        
        $form = $this->createForm(new FormDir\AccountType(), $account);

        $form->handleRequest($request);

        if ($form->isValid()) {

            $data = $form->getData();
            $data->setReport($report);
            $this->get('restClient')->post('report/' . $reportId . '/account', $account, [
                'deserialise_group' => 'add_edit'
            ]);

            return $this->redirect($this->generateUrl('accounts', ['reportId' => $reportId]));

        }

        return [
            'report' => $report,
            'subsection' => 'banks',
            'form' => $form->createView()
        ]; 
    }

    /**
     * @Route("/report/{reportId}/accounts/banks/{id}/edit", name="edit_account")
     * @param integer $reportId
     * @param integer $id
     * @param Request $request
     * @Template()
     * @return array
     */
    public function editAction(Request $request, $reportId, $id) 
    {

        $restClient = $this->getRestClient(); /* @var $restClient RestClient */

        $report = $this->getReportIfReportNotSubmitted($reportId, ['transactions', 'basic', 'client', 'accounts']);

        if (0 === count(array_filter($report->getAccounts(), function($account) use ($id) {
            return $account->getId() == $id;
        }))) {
            throw new \RuntimeException("Account not found.");
        }
        
        $account = $restClient->get('report/account/' . $id, 'Account');

        $form = $this->createForm(new FormDir\AccountType(), $account);
        $form->handleRequest($request);

        if($form->isValid()){

            $data = $form->getData();
            $data->setReport($report);
            $restClient->put('/account/' . $id, $account, [
                'deserialise_group' => 'add_edit'
            ]);

            return $this->redirect($this->generateUrl('accounts', ['reportId'=>$reportId]));
        
        }

        return [
            'report' => $report,
            'subsection' => 'banks',
            'form' => $form->createView()
        ];

    }

    /**
     * @Route("/report/{reportId}/accounts/banks/{id}/delete", name="delete_account")
     * @param integer $reportId
     * @param integer $id
     *
     * @return RedirectResponse
     */
    public function deleteAction($reportId, $id)
    {
        $report = $this->getReportIfReportNotSubmitted($reportId, ['transactions', 'basic', 'client']);
        $restClient = $this->getRestClient(); /* @var $restClient RestClient */

        if(!empty($report) && in_array($id, $report->getAccounts())){
            $restClient->delete("/account/{$id}");
        }

        return $this->redirect($this->generateUrl('accounts', [ 'reportId' => $reportId ]));

    }

    /**
     * @Route("/report/{reportId}/accounts/{type}.json", name="accounts_money_save_json",
     *     requirements={"type"="transactionsIn|transactionsOut"}
     * )
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $reportId
     * @param string $type
     *
     * @return JsonResponse
     */
    public function moneySaveJson(Request $request, $reportId, $type)
    {
        try {
            $report = $this->getReport($reportId, [$type, 'basic', 'balance']);
            if ($report->getSubmitted()) {
                throw new \RuntimeException("Report already submitted and not editable.");
            }

            $form = $this->createForm(new FormDir\TransactionsType($type), $report, [
                'method' => 'PUT'
            ]);
            $form->handleRequest($request);

            if (!$form->isValid()) {
                $errorsArray = $this->get('formErrorsFormatter')->toArray($form);

                return new JsonResponse(['success' => false, 'errors' => $errorsArray], 500);
            }
            $this->get('restClient')->put('report/' . $report->getId(), $form->getData(), [
                'deserialise_group' => $type,
            ]);
            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }




}
