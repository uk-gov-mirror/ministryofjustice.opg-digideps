<?php

namespace AppBundle\Controller;

use AppBundle\Entity\MoneyTransfer;

class MoneyTransferControllerTest extends AbstractTestController
{
    private static $deputy1;
    private static $report1;
    private static $account1;
    private static $deputy2;
    private static $report2;
    private static $account2;
    private static $account3;
    private static $transfer1;
    private static $tokenAdmin = null;
    private static $tokenDeputy = null;
    
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        
        self::$deputy1 = self::fixtures()->getRepo('User')->findOneByEmail('deputy@example.org');
        
        $client1 = self::fixtures()->createClient(self::$deputy1);
        self::fixtures()->flush();
        
        self::$report1 = self::fixtures()->createReport($client1);
        self::$account1 = self::fixtures()->createAccount(self::$report1, ['setBank'=>'bank1']);
        self::$account2 = self::fixtures()->createAccount(self::$report1, ['setBank'=>'bank2']);
        
        // add two transfer to report 1 between accounts
        self::$transfer1 = new MoneyTransfer;
        self::$transfer1->setReport(self::$report1)
            ->setAmount(1001)
            ->setFrom(self::$account2)
            ->setTo(self::$account1);
        self::fixtures()->persist(self::$transfer1);
        
        $transfer2 = new MoneyTransfer;
        $transfer2->setReport(self::$report1)
            ->setAmount(52)
            ->setFrom(self::$account1)
            ->setTo(self::$account2);
        self::fixtures()->persist($transfer2);
        
        // deputy 2
        self::$deputy2 = self::fixtures()->createUser();
        $client2 = self::fixtures()->createClient(self::$deputy2);
        self::$report2 = self::fixtures()->createReport($client2);
        self::$account3 = self::fixtures()->createAccount(self::$report2, ['setBank'=>'bank3']);
        
        
        
        self::fixtures()->flush()->clear();
    }
    
    /**
     * clear fixtures 
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        
        self::fixtures()->clear();
    }
    
    public function setUp()
    {
        if (null === self::$tokenAdmin) {
            self::$tokenAdmin = $this->loginAsAdmin();
            self::$tokenDeputy = $this->loginAsDeputy();
        }
    }
    
    public function testGetTransfers()
    {
        $url = '/report/' . self::$report1->getId() . '?groups=transfers';
        
        // assert data is retrieved
        $data = $this->assertJsonRequest('GET', $url, [
            'mustSucceed'=>true,
            'AuthToken' => self::$tokenDeputy,
        ])['data']['money_transfers'];
        
        $this->assertEquals(1001, $data[0]['amount']);
        $this->assertEquals('bank2', $data[0]['accountFrom']['bank']);
        $this->assertEquals('bank1', $data[0]['accountTo']['bank']);
        
        $this->assertEquals(52, $data[1]['amount']);
        $this->assertEquals('bank1', $data[1]['accountFrom']['bank']);
        $this->assertEquals('bank2', $data[1]['accountTo']['bank']);
    }
   
    
    public function testAddTransfer()
    {
        $url = '/report/' . self::$report1->getId() . '/money-transfers';
        $url2 = '/report/' . self::$report2->getId() . '/money-transfers';
        
        $this->assertEndpointNeedsAuth('POST', $url); 
        $this->assertEndpointNotAllowedFor('POST', $url, self::$tokenAdmin); 
        $this->assertEndpointNotAllowedFor('POST', $url2, self::$tokenDeputy); 
        
        $return = $this->assertJsonRequest('POST', $url, [
            'mustSucceed'=>true,
            'AuthToken' => self::$tokenDeputy,
            'data'=> [
                'accountFrom' => ['id'=>self::$account1->getId()],
                'accountTo' => ['id'=>self::$account2->getId()],
                'amount' => 123,
            ]
        ]);
        $this->assertTrue($return['data']['id'] > 0);
        $this->assertEquals(self::$account1->getId(), $return['data']['accountFrom']['id']);
        $this->assertEquals(self::$account2->getId(), $return['data']['accountTo']['id']);
        $this->assertEquals(123, $return['data']['amount']);
        
        self::fixtures()->clear();
        
        // assert account created with transactions
        $report = self::fixtures()->getRepo('Report')->find(self::$report1->getId()); /* @var $report \AppBundle\Entity\Report */
      
        // test last transaction
        $t = $report->getMoneyTransfers()->get(2);
        $this->assertEquals(123, $t->getAmount());
        $this->assertEquals(self::$account1->getId(), $t->getFrom()->getId());
        $this->assertEquals(self::$account2->getId(), $t->getTo()->getId());
        
    }
    
    public function testEditTransfer()
    {
        $url = '/report/' . self::$report1->getId() . '/money-transfers/' . self::$transfer1->getId();
        $url2 = '/report/' . self::$report2->getId() . '/money-transfers/' . self::$transfer1->getId();
        
        $this->assertEndpointNeedsAuth('PUT', $url); 
        $this->assertEndpointNotAllowedFor('PUT', $url, self::$tokenAdmin); 
        $this->assertEndpointNotAllowedFor('PUT', $url2, self::$tokenDeputy); 
        
        $return = $this->assertJsonRequest('PUT', $url, [
            'mustSucceed'=>true,
            'AuthToken' => self::$tokenDeputy,
            'data'=> [
                'accountFrom' => ['id'=>self::$account2->getId()],
                'accountTo' => ['id'=>self::$account1->getId()],
                'amount' => 124,
            ]
        ]);
        $this->assertTrue($return['data']['id'] > 0);
        $this->assertEquals(self::$account2->getId(), $return['data']['accountFrom']['id']);
        $this->assertEquals(self::$account1->getId(), $return['data']['accountTo']['id']);
        $this->assertEquals(124, $return['data']['amount']);
        
        self::fixtures()->clear();
        
        $t = self::fixtures()->getRepo('MoneyTransfer')->find(self::$transfer1->getId());
        $this->assertEquals(124, $t->getAmount());
        $this->assertEquals(self::$account2->getId(), $t->getFrom()->getId());
        $this->assertEquals(self::$account1->getId(), $t->getTo()->getId());
        $this->assertEquals(self::$report1->getId(), $t->getReport()->getId());
        
    }
    
}
