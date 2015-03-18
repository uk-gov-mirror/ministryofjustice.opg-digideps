<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Form as FormDir;
use AppBundle\Entity as EntityDir;
use Symfony\Component\Form\FormError;
use AppBundle\Service\ApiClient;


class AccountController extends Controller
{
    /**
     * @Route("/report/{reportId}/accounts/{action}", name="accounts", defaults={ "action" = "list"}, requirements={
     *   "action" = "(add|list)"
     * })
     * @Template()
     */
    public function accountsAction($reportId,$action)
    {
        $util = $this->get('util');
        $request = $this->getRequest();
         
        $report = $util->getReport($reportId);
        $client = $util->getClient($report->getClient());
        
        $apiClient = $this->get('apiclient');
        $accounts = $apiClient->getEntities('Account','get_report_accounts', [ 'query' => ['id' => $reportId ]]);
       
        $account = new EntityDir\Account();
        $account->setReportObject($report);
        
        $form = $this->createForm(new FormDir\AccountType(), $account);
        $form->handleRequest($request);
        
        if($request->getMethod() == 'POST'){
            if($form->isValid()){
                $account = $form->getData();
                $account->setReport($reportId);
                
                $apiClient->postC('add_report_account', $account);
                return $this->redirect($this->generateUrl('accounts', [ 'reportId' => $reportId ]));
            }
        }
        
        return [
            'report' => $report,
            'client' => $client,
            'action' => $action,
            'form' => $form->createView(),
            'accounts' => $accounts
        ];
    }
    
    
    /**
     * @Route("/report/{reportId}/account/{accountId}", name="account", requirements={
     *   "accountId" = "\d+"
     * })
     * @Template()
     */
    public function accountAction($reportId, $accountId, $action = 'list')
    {
        $util = $this->get('util');
        $request = $this->getRequest();
         
        $report = $util->getReport($reportId);
        $client = $util->getClient($report->getClient());
        
        $apiClient = $this->get('apiclient'); /* @var $apiClient ApiClient */
        $account = $this->getTempAccount($report); //$apiClient->getEntity('Account', 'find_account_by_id', [ 'query' => ['id' => $accountId ]]);
        
        // forms
        $formMoneyIn = $this->createForm(new FormDir\AccountMoneyInType(), $account);
        $formMoneyOut = $this->createForm(new FormDir\AccountMoneyOutType(), $account);
        
        
        $formMoneyIn->handleRequest($request);
        if($formMoneyIn->isSubmitted()){
            $this->debugFormData($formMoneyIn, 'money_in');
            
        }
        
        $formMoneyOut->handleRequest($request);
        if($formMoneyOut->isSubmitted()){
            $this->debugFormData($formMoneyOut, 'money_out');
            
        }
        
        return [
            'report' => $report,
            'client' => $client,
            'formIn' => $formMoneyIn->createView(),
            'formOut' => $formMoneyOut->createView(),
            'account' => $account
        ];
    }
    
    private function debugFormData($form, $jmsGroup)
    {
        echo "<pre>";
        if (!$form->isValid()) {
            echo $form->getErrorsAsString();
            die;
        }
        $account = $form->getData();

        $context = \JMS\Serializer\SerializationContext::create()
                ->setSerializeNull(true)
                ->setGroups($jmsGroup);

        $data = $this->get('jms_serializer')->serialize($account, 'json', $context);
        // print_r($account);
        echo "data passed to API: " . print_r(json_decode($data, 1), 1);die;
    }
    
    // until API not reay
    private function getTempAccount($report)
    {
        // fake data until API is not ready
        $account = new EntityDir\Account;
        $account->setId(1);
        $account->setReportObject($report);
        $account->setMoneyIn([
            new EntityDir\AccountTransaction('disability_living_allowance_or_personal_independence_payment', 2500),
            new EntityDir\AccountTransaction('attendance_allowance', 450),
            new EntityDir\AccountTransaction('employment_support_allowance_or_incapacity_benefit', 1250),
        ]);
        $account->setMoneyOut([
            new EntityDir\AccountTransaction('care_fees_or_local_authority_charges_for_care', 455),
            new EntityDir\AccountTransaction('accommodation_costs_eg_rent_mortgage_service_charges', 255),
        ]);
        
        return $account;
    }
}