<?php

namespace AppBundle\Controller;

use AppBundle\Service\Mailer\MailSenderMock;

class StatsControllerTest extends AbstractTestController
{
    private static $deputy1;
    private static $admin1;
    private static $tokenAdmin = null;
    private static $tokenDeputy = null;
    private static $client1;
    private static $report1;
    private static $report2;
    private static $account1;
    private static $account2;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$deputy1 = self::fixtures()->getRepo('User')->findOneByEmail('deputy@example.org');
        self::$admin1 = self::fixtures()->getRepo('User')->findOneByEmail('admin@example.org');

        self::$deputy1 = self::fixtures()->getRepo('User')->findOneByEmail('deputy@example.org');

        self::$client1 = self::fixtures()->createClient(self::$deputy1, ['setFirstname' => 'c1']);
        self::fixtures()->flush();


        // report 1
        self::$report1 = self::fixtures()->createReport(self::$client1);
        self::$account1 = self::fixtures()->createAccount(self::$report1, ['setBank'=>'bank1']);

        // report2
        self::$report2 = self::fixtures()->createReport(self::$client1)->setSubmitted(true);

        self::fixtures()->flush();

        self::fixtures()->getConnection()->query('UPDATE account_transaction SET amount=1 WHERE id < 10')->execute();

        self::fixtures()->clear();
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
    
    
    public function testStatsUsersAuth()
    {
        $url = '/stats/users';
        
        $this->assertEndpointNeedsAuth('GET', $url);

        $this->assertEndpointNotAllowedFor('GET', $url, self::$tokenDeputy);
    }


    
    public function testStatsUsers()
    {
        $url = '/stats/users';
        
        $data = $this->assertJsonRequest('GET', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenAdmin
        ])['data'];

        $first = array_shift($data);
        $this->assertEquals('deputy@example.org', $first['email']);
        $this->assertEquals(true, $first['is_active']);
        $this->assertEquals(1, $first['reports_unsubmitted']);
        $this->assertEquals(1, $first['reports_submitted']);
        $this->assertEquals(1, $first['reports_unsubmitted_bank_accounts']);
        $this->assertEquals(9, $first['reports_unsubmitted_completed_transactions']);
    }

}
