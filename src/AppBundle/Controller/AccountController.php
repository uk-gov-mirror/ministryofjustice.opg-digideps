<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Entity as EntityDir;


class AccountController extends RestController
{
    
    /**
     * @Route("/report/get-accounts/{id}")
     * @Method({"GET"})
     */
    public function getAccountsAction($id)
    {
        $request = $this->getRequest();
        
        $serialiseGroups = $request->query->has('groups')? $request->query->get('groups') : null;
        
        $this->setJmsSerialiserGroup($serialiseGroups);
        
        $report = $this->findEntityBy('Report', $id);
        
        $accounts = $this->getRepository('Account')->findByReport($report, [
            'id' => 'DESC'
        ]);
       
        if(count($accounts) == 0){
            return [];
        }
        return $accounts;
    }
    
    /**
     * @Route("/report/add-account")
     * @Method({"POST"})
     */
    public function addAccountAction()
    {
        $accountData = $this->deserializeBodyContent();
   
        $report = $this->findEntityBy('Report', $accountData['report']);
        
        if(empty($report)){
            throw new \Exception("Report id: ".$accountData['report']." does not exists");
        }
        
        $account = new EntityDir\Account();
        $account->setBank($accountData['bank'])
            ->setSortCode($accountData['sort_code'])
            ->setAccountNumber($accountData['account_number'])
            ->setOpeningDate(new \DateTime($accountData['opening_date']))
            ->setOpeningBalance($accountData['opening_balance'])
            ->setReport($report);
        
        $this->getRepository('Account')->addEmptyTransactionsToAccount($account);
        
        $this->getEntityManager()->persist($account);
        $this->getEntityManager()->flush();
        
        return [ 'id' => $account->getId() ];
    }
    
   /**
     * @Route("/report/find-account-by-id/{id}")
     * @Method({"GET"})
     */
    public function get($id)
    {
        $request = $this->getRequest();
        
        $serialiseGroups = $request->query->has('groups')? $request->query->get('groups') : null;
        
        $this->setJmsSerialiserGroup($serialiseGroups);
        
        $account = $this->findEntityBy('Account', $id, 'Account not found');

        return $account;
    }
    
    /**
     * @Route("/account/{id}")
     * @Method({"PUT"})
     */
    public function edit($id)
    {
        $account = $this->findEntityBy('Account', $id, 'Account not found'); /* @var $account EntityDir\Account*/ 
        
        $data = $this->deserializeBodyContent();
        
        //basicdata
        if (array_key_exists('bank', $data)) {
           $account->setBank($data['bank']);
        }
        if (array_key_exists('sort_code', $data)) {
           $account->setSortCode($data['sort_code']);
        }
        if (array_key_exists('account_number', $data)) {
           $account->setAccountNumber($data['account_number']);
        }
        
        // edit transactions
        if (isset($data['money_in']) && isset($data['money_out'])) {
            $transactionRepo = $this->getRepository('AccountTransaction');
            array_map(function($transactionRow) use ($transactionRepo) {
                $transactionRepo->find($transactionRow['id'])
                    ->setAmount($transactionRow['amount'])
                    ->setMoreDetails($transactionRow['more_details']);
            }, array_merge($data['money_in'], $data['money_out']));
            $this->setJmsSerialiserGroup('transactions');
        }
        
        if (array_key_exists('opening_date', $data)) {
           $account->setOpeningDate(new \DateTime($data['opening_date']));
        }
        if (array_key_exists('opening_balance', $data)) {
           $account->setOpeningBalance($data['opening_balance']);
        }
        
        if (array_key_exists('closing_date', $data)) {
           $account->setClosingDate(new \DateTime($data['closing_date']));
        }
        if (array_key_exists('closing_balance', $data)) {
           $account->setClosingBalance($data['closing_balance']);
        }
        
        $account->setLastEdit(new \DateTime());
        
        $this->getEntityManager()->flush();
        
        return $account;
    }
    
}