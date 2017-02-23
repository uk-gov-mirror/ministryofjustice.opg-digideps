<?php

namespace Tests\AppBundle\Controller\Report;

use AppBundle\Entity\Report\Report;
use Tests\AppBundle\Controller\AbstractTestController;

class ReportControllerTest extends AbstractTestController
{
    private static $deputy1;
    private static $client1;
    private static $report1;
    private static $report103;
    private static $deputy2;
    private static $client2;
    private static $report2;
    private static $tokenAdmin = null;
    private static $tokenDeputy = null;
    private static $tokenPa = null;

    // pa
    private static $pa1;
    private static $paClient1;
    private static $paClient1Report1;
    private static $paClient2;
    private static $paClient2Report1;
    private static $paClient3;
    private static $paClient3Report1;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$deputy1 = self::fixtures()->getRepo('User')->findOneByEmail('deputy@example.org');

        self::$client1 = self::fixtures()->createClient(self::$deputy1, ['setFirstname' => 'c1']);
        self::fixtures()->flush();

        self::$report1 = self::fixtures()->createReport(self::$client1);
        self::$report103 = self::fixtures()->createReport(self::$client1, ['setType'=>Report::TYPE_103]);

        // deputy 2
        self::$deputy2 = self::fixtures()->createUser();
        self::$client2 = self::fixtures()->createClient(self::$deputy2);
        self::$report2 = self::fixtures()->createReport(self::$client2);

        // pa1
        self::$pa1 = self::fixtures()->getRepo('User')->findOneByEmail('pa@example.org');
        self::$paClient1 = self::fixtures()->createClient(self::$pa1, ['setFirstname' => 'paClient1']);
        self::$paClient1Report1 = self::fixtures()->createReport(self::$paClient1);
        self::$paClient2 = self::fixtures()->createClient(self::$pa1, ['setFirstname' => 'paClient2']);
        self::$paClient2Report1 = self::fixtures()->createReport(self::$paClient2);
        self::$paClient3 = self::fixtures()->createClient(self::$pa1, ['setFirstname' => 'paClient3']);
        self::$paClient3Report1 = self::fixtures()->createReport(self::$paClient3);

        self::fixtures()->flush()->clear();
    }

    /**
     * clear fixtures.
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
            self::$tokenPa = $this->loginAsPa();
        }
    }

    public function testAddAuth()
    {
        $url = '/report';
        $this->assertEndpointNeedsAuth('POST', $url);

        $this->assertEndpointNotAllowedFor('POST', $url, self::$tokenAdmin);
    }

    public function testAddAcl()
    {
        $url = '/report';
        $this->assertEndpointNotAllowedFor('POST', $url, self::$tokenDeputy, [
            'client' => ['id' => self::$client2->getId()],
        ]);
    }

    private $fixedData = [
        'start_date' => '2015-01-01',
        'end_date' => '2015-12-31',
    ];

    public function testAdd()
    {
        $url = '/report';

        $reportId = $this->assertJsonRequest('POST', $url, [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
                'data' => ['client' => ['id' => self::$client1->getId()]] + $this->fixedData,
            ])['data']['report'];

        self::fixtures()->clear();

        // assert creation
        $report = self::fixtures()->getReportById($reportId);
        /* @var $report \AppBundle\Entity\Report\Report */
        $this->assertEquals(self::$client1->getId(), $report->getClient()->getId());
        $this->assertEquals('2015-01-01', $report->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2015-12-31', $report->getEndDate()->format('Y-m-d'));
    }

    public function testGetByIdAuth()
    {
        $url = '/report/' . self::$report1->getId();
        $this->assertEndpointNeedsAuth('GET', $url);

        $this->assertEndpointNotAllowedFor('GET', $url, self::$tokenAdmin);
    }

    public function testGetByIdAcl()
    {
        $url2 = '/report/' . self::$report2->getId();

        $this->assertEndpointNotAllowedFor('GET', $url2, self::$tokenDeputy);
    }

    /**
     * @depends testAdd
     */
    public function testGetById()
    {
        $url = '/report/' . self::$report1->getId();

        $q = http_build_query(['groups' => ['report', 'client']]);
        $data = $this->assertJsonRequest('GET', $url . '?' . $q, [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];
        $this->assertArrayHasKey('report_seen', $data);
        $this->assertArrayNotHasKey('transactions', $data);
        $this->assertArrayNotHasKey('debts', $data);
        $this->assertEquals(self::$report1->getId(), $data['id']);
        $this->assertEquals(self::$client1->getId(), $data['client']['id']);
        $this->assertArrayHasKey('start_date', $data);
        $this->assertArrayHasKey('end_date', $data);

        // assert decisions
        $data = $this->assertJsonRequest('GET', $url . '?groups=decision', [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];
        $this->assertArrayHasKey('decisions', $data);

        // assert assets
        $data = $this->assertJsonRequest('GET', $url . '?groups=asset', [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];
        $this->assertArrayHasKey('assets', $data);

        // assert debts
        $data = $this->assertJsonRequest('GET', $url . '?groups=debt', [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
        ])['data'];
        $this->assertArrayHasKey('debts', $data);

        // assert status
        $data = $this->assertJsonRequest('GET', $url . '?groups=status', [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
        ])['data']['status'];

        foreach ([
            'decisions_state',
            'contacts_state',
            'visits_care_state',
            'bank_accounts_state',
            'money_transfer_state',
            'money_in_state',
            'money_out_state',
            'money_in_short_state',
            'money_out_short_state',
            'balance_state',
            'assets_state',
            'debts_state',
            'actions_state',
            'other_info_state',
            'expenses_state',
            'gifts_state',
            'submit_state',
                ] as $key) {
            $this->assertArrayHasKey('state', $data[$key]);
            $this->assertArrayHasKey('nOfRecords', $data[$key]);
        }

        $this->assertArrayHasKey('balance_matches', $data);
        $this->assertArrayHasKey('remaining_sections', $data);
        $this->assertArrayHasKey('section_status', $data);
        $this->assertArrayHasKey('is_ready_to_submit', $data);
    }

    public function testSubmitAuth()
    {
        $url = '/report/' . self::$report1->getId() . '/submit';

        $this->assertEndpointNeedsAuth('PUT', $url);
        $this->assertEndpointNotAllowedFor('PUT', $url, self::$tokenAdmin);
    }

    public function testSubmitAcl()
    {
        $url2 = '/report/' . self::$report2->getId() . '/submit';

        $this->assertEndpointNotAllowedFor('PUT', $url2, self::$tokenDeputy);
    }

    public function testSubmitNotAllAgree()
    {
        $this->assertEquals(false, self::$report1->getSubmitted());

        $reportId = self::$report1->getId();
        $url = '/report/' . $reportId . '/submit';

        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'submit_date' => '2015-12-30',
                'agreed_behalf_deputy' => 'more_deputies_not_behalf',
                'agreed_behalf_deputy_explanation' => 'abdexplanation',
            ],
        ]);

        // assert account created with transactions
        $report = self::fixtures()->clear()->getRepo('Report\Report')->find($reportId);
        /* @var $report \AppBundle\Entity\Report\Report */
        $this->assertEquals(true, $report->getSubmitted());
        $this->assertEquals('more_deputies_not_behalf', $report->getAgreedBehalfDeputy());
        $this->assertEquals('abdexplanation', $report->getAgreedBehalfDeputyExplanation());
    }

    public function testSubmit()
    {
        $this->assertEquals(false, self::$report1->getSubmitted());

        $reportId = self::$report1->getId();
        $url = '/report/' . $reportId . '/submit';

        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'submit_date' => '2015-12-30',
                'agreed_behalf_deputy' => 'only_deputy',
                'agreed_behalf_deputy_explanation' => 'should not be saved',
            ],
        ]);

        // assert account created with transactions
        $report = self::fixtures()->clear()->getRepo('Report\Report')->find($reportId);
        /* @var $report \AppBundle\Entity\Report\Report */
        $this->assertEquals(true, $report->getSubmitted());
        $this->assertEquals('only_deputy', $report->getAgreedBehalfDeputy());
        $this->assertEquals(null, $report->getAgreedBehalfDeputyExplanation());
        $this->assertEquals('2015-12-30', $report->getSubmitDate()->format('Y-m-d'));
    }

    public function testUpdateAuth()
    {
        $url = '/report/' . self::$report1->getId();

        $this->assertEndpointNeedsAuth('PUT', $url);
        $this->assertEndpointNotAllowedFor('PUT', $url, self::$tokenAdmin);
    }

    public function testUpdateAcl()
    {
        $url2 = '/report/' . self::$report2->getId();

        $this->assertEndpointNotAllowedFor('PUT', $url2, self::$tokenDeputy);
    }

    public function testUpdate()
    {
        $reportId = self::$report1->getId();
        $url = '/report/' . $reportId;

        // assert get
        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'start_date' => '2015-01-29',
                'end_date' => '2015-12-29',
                'balance_mismatch_explanation' => 'bme',
                'metadata' => 'md',
            ],
        ]);

        // both
        $q = http_build_query(['groups' => ['report'/*, 'transactionsIn', 'transactionsOut'*/]]);
        //assert both groups (quick)
        $data = $this->assertJsonRequest('GET', $url . '?' . $q, [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenDeputy,
            ])['data'];
//        $this->assertTrue(count($data['transactions_in']) > 25);
//        $this->assertTrue(count($data['transactions_out']) > 40);
        $this->assertArrayHasKey('start_date', $data);
        $this->assertArrayHasKey('end_date', $data);
        $this->assertEquals('md', $data['metadata']);
    }

    public function testDebts()
    {
        $reportId = self::$report1->getId();
        $url = '/report/' . $reportId;

        // "yes"
        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'has_debts' => 'yes',
                'debts' => [
                    ['debt_type_id' => 'care-fees', 'amount' => 1, 'more_details' => 'should not be saved'],
                    ['debt_type_id' => 'credit-cards', 'amount' => 2, 'more_details' => ''],
                    ['debt_type_id' => 'loans', 'amount' => 3, 'more_details' => ''],
                    ['debt_type_id' => 'other', 'amount' => 4, 'more_details' => 'md'],
                ],
            ],
        ]);

        $q = http_build_query(['groups' => ['debt']]);
        //assert both groups (quick)
        $data = $this->assertJsonRequest('GET', $url . '?' . $q, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
        ])['data'];
        $debt = array_shift($data['debts']);
        $this->assertEquals('care-fees', $debt['debt_type_id']);
        $this->assertEquals(1, $debt['amount']);
        $this->assertEquals('', $debt['more_details']);
        $debt = array_shift($data['debts']);
        $this->assertEquals('credit-cards', $debt['debt_type_id']);
        $this->assertEquals(2.00, $debt['amount']);
        $this->assertEquals('', $debt['more_details']);
        $debt = array_shift($data['debts']);
        $this->assertEquals('loans', $debt['debt_type_id']);
        $this->assertEquals(3.00, $debt['amount']);
        $this->assertEquals('', $debt['more_details']);
        $debt = array_shift($data['debts']);
        $this->assertEquals('other', $debt['debt_type_id']);
        $this->assertEquals(4.00, $debt['amount']);
        $this->assertEquals('md', $debt['more_details']);
        $this->assertEquals(10, $data['debts_total_amount']);
        $this->assertEquals('yes', $data['has_debts']);

        // "no"
        self::fixtures()->flush()->clear();
        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'has_debts' => 'no',
                'debts' => [],
            ],
        ]);
        $data = $this->assertJsonRequest('GET', $url . '?' . $q, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
        ])['data'];
        $debt = array_shift($data['debts']);
        $this->assertEquals('care-fees', $debt['debt_type_id']);
        $this->assertEquals(0, $debt['amount']);
        $this->assertEquals('', $debt['more_details']);
        $this->assertEquals(0, $data['debts_total_amount']);
        $this->assertEquals('no', $data['has_debts']);
    }

    public function testActions()
    {
        $url = '/report/' . self::$report1->getId();

        // PUT
        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
            'data' => [
                'action_more_info' => 'yes',
                'action_more_info_details' => 'md2',
            ],
        ]);

        // GET and assert
        $q = http_build_query(['groups' => [
            'action-more-info',
        ]]);
        $data = $this->assertJsonRequest('GET', $url . '?' . $q, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
        ])['data'];

        $this->assertEquals('yes', $data['action_more_info']);
        $this->assertEquals('md2', $data['action_more_info_details']);
    }

    public function testMoneyCategories()
    {
        $url = '/report/' . self::$report103->getId();

        //refresh
        self::$report103 = self::fixtures()->getRepo('REport\Report')->find(self::$report103->getId());

        $this->assertCount(15, self::$report103->getMoneyShortCategories());

        // check default
        $q = http_build_query(['groups' => [
            'moneyShortCategoriesIn',
            'moneyShortCategoriesOut',
        ]]);
        $data = $this->assertJsonRequest('GET', $url . '?' . $q, [
            'mustSucceed' => true,
            'AuthToken' => self::$tokenDeputy,
        ])['data'];

        $this->assertCount(7, $data['money_short_categories_in']);
        $this->assertCount(8, $data['money_short_categories_out']);


        // PUT
        $this->assertJsonRequest('PUT', $url, [
            'mustSucceed' => true,
            'AuthToken'   => self::$tokenDeputy,
            'data'        => [
                'money_short_categories_in'                      => [
                    ['type_id' => 'state_pension_and_benefit', 'present' => true],
                    ['type_id' => 'bequests', 'present' => false],
                ],
                'money_short_categories_out'                      => [
                    ['type_id' => 'accomodation_costs', 'present' => true],
                    ['type_id' => 'care_fees', 'present' => false],
                ],

            ],
        ]);

        // GET and assert
        $q = http_build_query(['groups' => [
            'moneyShortCategoriesIn',
            'moneyShortCategoriesOut',
        ]]);
        $data = $this->assertJsonRequest('GET', $url . '?' . $q, [
            'mustSucceed' => true,
            'AuthToken'   => self::$tokenDeputy,
        ])['data'];

        $this->assertEquals('state_pension_and_benefit', $data['money_short_categories_in'][0]['type_id']);
        $this->assertEquals(true, $data['money_short_categories_in'][0]['present']);

        $this->assertEquals('accomodation_costs', $data['money_short_categories_out'][7]['type_id']);
        $this->assertEquals(true, $data['money_short_categories_out'][7]['present']);

        $this->assertEquals('care_fees', $data['money_short_categories_out'][8]['type_id']);
        $this->assertEquals(false, $data['money_short_categories_out'][8]['present']);
    }


    public function testGetAllAuth()
    {
        $url = '/report/get-all';
        $this->assertEndpointNeedsAuth('GET', $url);
        $this->assertEndpointNotAllowedFor('GET', $url, self::$tokenAdmin);
    }

    public function testGetAllAcl()
    {
        $url = '/report/get-all';

        $this->assertEndpointNotAllowedFor('GET', '/report/get-all', self::$tokenAdmin);
        $this->assertEndpointNotAllowedFor('GET', $url, self::$tokenDeputy);
    }

    public function testGetAll()
    {
        $url = '/report/get-all';

        $reportsGetAllRequest = function(array $params)  {
            $url = '/report/get-all?' . http_build_query($params);
            return $this->assertJsonRequest('GET', $url, [
                'mustSucceed' => true,
                'AuthToken' => self::$tokenPa,
            ])['data'];
        };

        // assert get
        $ret = $reportsGetAllRequest([]);
        // assert counts
//        $this->assertEquals(0, $ret['counts']['total']);
//        $this->assertEquals(0, $ret['counts']['notStarted']);
//        $this->assertEquals(0, $ret['counts']['notFinished']);
//        $this->assertEquals(0, $ret['counts']['readyToSubmit']);

        //assert results
        $this->assertCount(3,  $ret['reports']);
        $this->assertEquals('102',  $ret['reports'][0]['type']);
        $this->assertEquals('paClient1',  $ret['reports'][0]['client']['firstname']);

        //test pagination
        $reportsPaginated = $reportsGetAllRequest([
            'offset'    => 1,
            'limit'  => '1',
        ]);
        $this->assertCount(1, $reportsPaginated['reports']);
        $this->assertEquals($reportsPaginated['reports'][0]['id'], $ret['reports'][1]['id']);

        //test status
        $reportsNotStarted = $reportsGetAllRequest([
            'status'    => 'notStarted',
        ]);
        $this->assertCount(3,  $reportsNotStarted['reports']);
        $reportsFilteredReadyToSubmit = $reportsGetAllRequest([
            'status'    => 'readyToSubmit',
        ]);
        $this->assertCount(0,  $reportsFilteredReadyToSubmit['reports']);

        // test search
        $reportsSearched = $reportsGetAllRequest([
            'q'    => 'paClient3',
        ]);
        $this->assertCount(1,  $reportsSearched['reports']);
    }
}
