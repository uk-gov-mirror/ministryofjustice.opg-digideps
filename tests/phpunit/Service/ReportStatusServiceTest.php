<?php

namespace AppBundle\Service;

use Mockery as m;
use AppBundle\Entity\Report;
use AppBundle\Entity\Account;

class ReportStatusServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Mockery\MockInterface $translator */
    private $translator;

    public function setUp()
    {
        $this->translator = m::mock('Symfony\Component\Translation\TranslatorInterface')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('trans')->with('decision', [], 'status')->andReturn('Decision')
            ->shouldReceive('trans')->with('decisions', [], 'status')->andReturn('Decisions')
            ->shouldReceive('trans')->with('nodecisions', [], 'status')->andReturn('No decisions')
            ->shouldReceive('trans')->with('contact', [], 'status')->andReturn('Contact')
            ->shouldReceive('trans')->with('contacts', [], 'status')->andReturn('Contacts')
            ->shouldReceive('trans')->with('account', [], 'status')->andReturn('Account')
            ->shouldReceive('trans')->with('accounts', [], 'status')->andReturn('Accounts')
            ->shouldReceive('trans')->with('asset', [], 'status')->andReturn('Asset')
            ->shouldReceive('trans')->with('assets', [], 'status')->andReturn('Assets')
            ->shouldReceive('trans')->with('noassets', [], 'status')->andReturn('No assets')
            ->shouldReceive('trans')->with('nocontacts', [], 'status')->andReturn('No contacts')
            ->shouldReceive('trans')->with('notstarted', [], 'status')->andReturn('Not started')
            ->shouldReceive('trans')->with('notFinished', [], 'status')->andReturn('Not finished')
            ->shouldReceive('trans')->with('finished', [], 'status')->andReturn('Finished')
            ->getMock();
    }

    public function tearDown()
    {
        m::close();
    }

    public function testnotice()
    {
        $this->markTestIncomplete('too much duplication in this test. needs more object in setup()');
    }

    /** @test */
    public function hasOutstandingAccountsIsTrue()
    {
        $account = m::mock('AppBundle\Entity\Account')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('hasClosingBalance')->andReturn(false)
            ->shouldReceive('hasMissingInformation')->andReturn(false)
            ->getMock();

        $accounts = array($account);

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getAccounts')->andReturn($accounts)
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $this->assertTrue($reportStatusService->hasOutstandingAccounts());
    }

    /** @test */
    public function hasOutstandingAccountsIsFalse()
    {
        $account = m::mock('AppBundle\Entity\Account')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('hasClosingBalance')->andReturn(true)
            ->shouldReceive('hasMissingInformation')->andReturn(false)
            ->getMock();

        $accounts = array($account);

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getAccounts')->andReturn($accounts)
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $this->assertFalse($reportStatusService->hasOutstandingAccounts());
    }

    public static function missingTransfersProvider()
    {
        return [
            [0, 0, false, false],
            [0, 0, true, false],
            // 1 acccount
            [1, 0, false, false],
            [1, 0, true, false],
            // 2 accounts
            [2, 0, false, true], // no transfers, unticked => missing
            [2, 0, true, false], // no transfers, ticked
            [2, 1, true, false], // 1 transfer, ticked
            [2, 1, true, false], // 1 transfer, unticked
        ];
    }

    /**
     * @dataProvider missingTransfersProvider
     */
    public function testMissingTransfers($nOfAccounts, $nOfTransfers, $noTransfersToAdd, $expected)
    {
        $accounts = [];
        while ($nOfAccounts--) {
            $accounts[] = m::mock('AppBundle\Entity\Account')
                ->shouldIgnoreMissing(true)
                ->shouldReceive('hasClosingBalance')->andReturn(true)
                ->shouldReceive('hasMissingInformation')->andReturn(true)
                ->getMock();
        }

        $transfers = [];
        while ($nOfTransfers--) {
            $transfers[] = m::mock('AppBundle\Entity\MoneyTransfer');
        }

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getAccounts')->andReturn($accounts)
            ->shouldReceive('getMoneyTransfers')->andReturn($transfers)
            ->shouldReceive('getNoTransfersToAdd')->andReturn($noTransfersToAdd)
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $this->assertEquals($expected, $reportStatusService->missingTransfers());
    }


    /** @test */
    public function isReadyToSubmitIsTrue()
    {
        $contact = m::mock('AppBundle\Entity\Contact');
        $decision = m::mock('AppBundle\Entity\Decision');
        $asset = m::mock('AppBundle\Entity\Asset');

        $safeguarding = m::mock('AppBundle\Entity\Safeguarding')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('missingSafeguardingInfo')->andReturn(false)
            ->getMock();

        $account = m::mock('AppBundle\Entity\Account')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('hasClosingBalance')->andReturn(true)
            ->shouldReceive('hasMissingInformation')->andReturn(false)
            ->getMock();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getDecisions')->andReturn([$decision])
            ->shouldReceive('getContacts')->andReturn([$contact])
            ->shouldReceive('getAssets')->andReturn([$asset])
            ->shouldReceive('getAccounts')->andReturn([$account])
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->shouldReceive('getSafeguarding')->andReturn($safeguarding)
            ->shouldReceive('getAction')->andReturn(m::mock('AppBundle\Entity\Action', ['isComplete' => true]))
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $this->assertTrue($reportStatusService->isReadyToSubmit());
    }

    /** @test */
    public function isReadyToSubmitIsTrueNoContacts()
    {
        $decision = m::mock('AppBundle\Entity\Decision');
        $asset = m::mock('AppBundle\Entity\Asset');

        $safeguarding = m::mock('AppBundle\Entity\Safeguarding')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('missingSafeguardingInfo')->andReturn(false)
            ->getMock();

        $account = m::mock('AppBundle\Entity\Account')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('hasClosingBalance')->andReturn(true)
            ->shouldReceive('hasMissingInformation')->andReturn(false)
            ->getMock();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getDecisions')->andReturn([$decision])
            ->shouldReceive('getContacts')->andReturn([])
            ->shouldReceive('getReasonForNoContacts')->andReturn('stuff')
            ->shouldReceive('getAssets')->andReturn([$asset])
            ->shouldReceive('getAccounts')->andReturn([$account])
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->shouldReceive('getSafeguarding')->andReturn($safeguarding)
            ->shouldReceive('getAction')->andReturn(m::mock('AppBundle\Entity\Action', ['isComplete' => true]))
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $this->assertTrue($reportStatusService->isReadyToSubmit());
    }

    /** @test */
    public function isReadyToSubmitIsTrueNoAssets()
    {
        $decision = m::mock('AppBundle\Entity\Decision');
        $contact = m::mock('AppBundle\Entity\Contact');

        $safeguarding = m::mock('AppBundle\Entity\Safeguarding')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('missingSafeguardingInfo')->andReturn(false)
            ->getMock();

        $account = m::mock('AppBundle\Entity\Account')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('hasClosingBalance')->andReturn(true)
            ->shouldReceive('hasMissingInformation')->andReturn(false)
            ->getMock();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getDecisions')->andReturn([$decision])
            ->shouldReceive('getContacts')->andReturn([$contact])
            ->shouldReceive('getNoAssetsToAdd')->andReturn(true)
            ->shouldReceive('getAssets')->andReturn([])
            ->shouldReceive('getAccounts')->andReturn([$account])
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->shouldReceive('getSafeguarding')->andReturn($safeguarding)
            ->shouldReceive('getAction')->andReturn(m::mock('AppBundle\Entity\Action', ['isComplete' => true]))
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $this->assertTrue($reportStatusService->isReadyToSubmit());
    }

    /** @test */
    public function isReadyToSubmitIsTrueNoDecisions()
    {
        $contact = m::mock('AppBundle\Entity\Contact');

        $asset = m::mock('AppBundle\Entity\Asset');

        $safeguarding = m::mock('AppBundle\Entity\Safeguarding')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('missingSafeguardingInfo')->andReturn(false)
            ->getMock();

        $account = m::mock('AppBundle\Entity\Account')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('hasClosingBalance')->andReturn(true)
            ->shouldReceive('hasMissingInformation')->andReturn(false)
            ->getMock();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getDecisions')->andReturn([])
            ->shouldReceive('getReasonForNoDecisions')->andReturn('stuff')
            ->shouldReceive('getContacts')->andReturn([$contact])
            ->shouldReceive('getAssets')->andReturn([$asset])
            ->shouldReceive('getAccounts')->andReturn([$account])
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->shouldReceive('getSafeguarding')->andReturn($safeguarding)
            ->shouldReceive('getAction')->andReturn(m::mock('AppBundle\Entity\Action', ['isComplete' => true]))
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $this->assertTrue($reportStatusService->isReadyToSubmit());
    }


    /** @test */
    public function indicateDecisionsNotStarted()
    {
        $decisions = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getDecisions')->andReturn($decisions)
            ->shouldReceive('getReasonForNoDecisions')->andReturn('')
            ->shouldReceive('getMentalCapacity')->andReturn(null)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getDecisionsStatus();

        $this->assertEquals('Not started', $answer);
    }

    /** @test */
    public function indicateSingleContact()
    {
        $contacts = array(1);

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getContacts')->andReturn($contacts)
            ->shouldReceive('getReasonForNoContacts')->andReturn('')
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getContactsStatus();

        $this->assertEquals('1 Contact', $answer);
    }

    /** @test */
    public function indicateMultipleContacts()
    {
        $contacts = array(1, 2);

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getContacts')->andReturn($contacts)
            ->shouldReceive('getReasonForNoContacts')->andReturn('')
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getContactsStatus();

        $this->assertEquals('2 Contacts', $answer);
    }

    /** @test */
    public function indicateNoContactsAdded()
    {
        $contacts = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getContacts')->andReturn($contacts)
            ->shouldReceive('getReasonForNoContacts')->andReturn('There was nothing')
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getContactsStatus();

        $this->assertEquals('No contacts', $answer);
    }

    /** @test */
    public function indicateContactsNotStarted()
    {
        $contacts = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getContacts')->andReturn($contacts)
            ->shouldReceive('getReasonForNoContacts')->andReturn('')
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getContactsStatus();

        $this->assertEquals('Not started', $answer);
    }

    /** @test */
    public function indicateThatSafeguardingHasNotBeenStarted()
    {
        $safeguarding = m::mock('AppBundle\Entity\Safeguarding')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('missingSafeguardingInfo')->andReturn(true)
            ->getMock();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getSafeguarding')->andReturn($safeguarding)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);
        $answer = $reportStatusService->getSafeguardingStatus();

        $this->assertEquals('Not started', $answer);
    }

    /** @test */
    public function indicateThatSafeguardingIsComplete()
    {
        $safeguarding = m::mock('AppBundle\Entity\Safeguarding')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('missingSafeguardingInfo')->andReturn(false)
            ->getMock();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getSafeguarding')->andReturn($safeguarding)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getSafeguardingStatus();

        $this->assertEquals('Finished', $answer);
    }

    public function accountsStateProvider()
    {
        return [
            // grey if has nothing
            [[], false, false, false, false, ReportStatusService::STATUS_GREY],

            // green when has account, has moneyin, moneyout, total match (or explanation given)
            [[true, true], true, true, true, false, ReportStatusService::STATUS_GREEN],
            [[true, true], true, true, false, true, ReportStatusService::STATUS_GREEN],

             // amber in all the other cases 
            [[true], false, false, false, false,  ReportStatusService::STATUS_AMBER], //only one account
            [[], true, false, false, false,  ReportStatusService::STATUS_AMBER], //only money in
            [[], false, true, false, false,  ReportStatusService::STATUS_AMBER], //only money out
            [[], true, true, true, false, ReportStatusService::STATUS_AMBER], //everything except account
            [[true], false, true, true, false, ReportStatusService::STATUS_AMBER], //everything except moneyin
            [[true], true, false, true, false, ReportStatusService::STATUS_AMBER], //everything except moneyout
            [[true], true, true, false, false,  ReportStatusService::STATUS_AMBER], // account ,money in and out but not balance
            [[true, false], true, true, false, true, ReportStatusService::STATUS_AMBER], // one account has missing balance

        ];
    }

    /** 
     * @test 
     * @dataProvider accountsStateProvider
     */
    public function getAccountsState($accounts, $hasMoneyIn, $hasMoneyOut, $isTotalMatch, $balanceExpl, $expectedState)
    {
        $accountsMocks = [];
        foreach ($accounts as $hasClosingBalance) {
            $accountsMocks[] = m::mock('AppBundle\Entity\Account')
                ->shouldReceive('hasClosingBalance')->andReturn($hasClosingBalance)
                ->shouldReceive('hasMissingInformation')->andReturn(false)
                ->getMock();
        }

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('hasMoneyIn')->andReturn($hasMoneyIn)
            ->shouldReceive('hasMoneyOut')->andReturn($hasMoneyOut)
            ->shouldReceive('getAccounts')->andReturn($accountsMocks)
            ->shouldReceive('getMoneyTransfers')->andReturn([])
            ->shouldReceive('getNoTransfersToAdd')->andReturn(true)
            ->shouldReceive('isTotalsMatch')->andReturn($isTotalMatch)
            ->shouldReceive('getBalanceMismatchExplanation')->andReturn($balanceExpl)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);
        $this->assertEquals($expectedState, $reportStatusService->getAccountsState());
    }

    public function accountsStatusProvider()
    {
        return [
            // if has nothing
            [[], false, false, false, false, 'Not started'],

            // account, has moneyin, moneyout, total match (or explanation given)
            [[true], true, true, true, false, 'Finished'],
            [[true], true, true, false, true, 'Finished'],

             // all the other cases 
            [[true], false, false, false, false,  'Not finished'], //only one account
            [[], true, false, false, false,  'Not finished'], //only money in
            [[], false, true, false, false,  'Not finished'], //only money out
            [[], true, true, true, false, 'Not finished'], //everything except account
            [[true], false, true, true, false, 'Not finished'], //everything except moneyin
            [[true], true, false, true, false, 'Not finished'], //everything except moneyout
            [[true], true, true, false, false,  'Not finished'], // account ,money in and out but not balance
            [[true, false], true, true, false, true, 'Not finished'],

        ];
    }

    /** 
     * @test 
     * @dataProvider accountsStatusProvider
     */
    public function getAccountsStatus($accounts, $hasMoneyIn, $hasMoneyOut,
            $isTotalMatch, $balanceExpl, $expectedStatus)
    {
        $accountsMocks = [];
        foreach ($accounts as $hasClosingBalance) {
            $accountsMocks[] = m::mock('AppBundle\Entity\Account')
                ->shouldReceive('hasClosingBalance')->andReturn($hasClosingBalance)
                ->shouldReceive('hasMissingInformation')->andReturn(false)
                ->getMock();
        }

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('hasMoneyIn')->andReturn($hasMoneyIn)
            ->shouldReceive('hasMoneyOut')->andReturn($hasMoneyOut)
            ->shouldReceive('getAccounts')->andReturn($accountsMocks)
            ->shouldReceive('isTotalsMatch')->andReturn($isTotalMatch)
            ->shouldReceive('getBalanceMismatchExplanation')->andReturn($balanceExpl)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);
        $this->assertEquals($expectedStatus, $reportStatusService->getAccountsStatus());
    }

    /** @test */
    public function indicateSingleAssetStatus()
    {
        $assets = array(1);

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getAssets')->andReturn($assets)
            ->shouldReceive('getNoAssetToAdd')->andReturn(false)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getAssetsStatus();

        $this->assertEquals('1 Asset', $answer);
    }

    /** @test */
    public function indicateMultipleAssetsStatus()
    {
        $assets = array(1, 1);

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getAssets')->andReturn($assets)
            ->shouldReceive('getNoAssetToAdd')->andReturn(false)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getAssetsStatus();

        $this->assertEquals('2 Assets', $answer);
    }

    /** @test */
    public function indicateWhenNoAssetsToAdd()
    {
        $assets = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getAssets')->andReturn($assets)
            ->shouldReceive('getNoAssetToAdd')->andReturn(true)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getAssetsStatus();

        $this->assertEquals('No assets', $answer);
    }

    /** @test */
    public function indicateAssetsNotStarted()
    {
        $assets = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getAssets')->andReturn($assets)
            ->shouldReceive('getNoAssetToAdd')->andReturn(false)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getAssetsStatus();

        $this->assertEquals('Not started', $answer);
    }

    /** @test */
    public function indicateDecisionsStateNotStarted()
    {
        $decisions = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getDecisions')->andReturn($decisions)
            ->shouldReceive('getReasonForNoDecisions')->andReturn('')
            ->shouldReceive('getMentalCapacity')->andReturn(null)
            ->getMock();

        /** @var ReportStatusService $reportStatusService */
        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getDecisionsState();

        $this->assertEquals(ReportStatusService::NOTSTARTED, $answer);
    }

    /** @test */
    public function indicateDecisionsStateDoneWhenDecisions()
    {
        $decisions = array(1);

        $mc = m::mock('AppBundle\Entity\MentalCapacity');
        
        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getDecisions')->andReturn($decisions)
            ->shouldReceive('getReasonForNoDecisions')->andReturn('')
            ->shouldReceive('getMentalCapacity')->andReturn($mc)
            ->getMock();

        /** @var ReportStatusService $reportStatusService */
        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getDecisionsState();

        $this->assertEquals(ReportStatusService::DONE, $answer);
    }

    /** @test */
    public function indicateContactsStateNotStarted()
    {
        $contacts = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getContacts')->andReturn($contacts)
            ->shouldReceive('getReasonForNoContacts')->andReturn('')
            ->getMock();

        /** @var ReportStatusService $reportStatusService */
        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getContactsState();

        $this->assertEquals(ReportStatusService::NOTSTARTED, $answer);
    }

    /** @test */
    public function indicateContactsStateDoneWithContacts()
    {
        $contacts = array(1);

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getContacts')->andReturn($contacts)
            ->shouldReceive('getReasonForNoContacts')->andReturn('')
            ->getMock();

        /** @var ReportStatusService $reportStatusService */
        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getContactsState();

        $this->assertEquals(ReportStatusService::DONE, $answer);
    }

    /** @test */
    public function indicateContactsStateDoneForReason()
    {
        $contacts = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getContacts')->andReturn($contacts)
            ->shouldReceive('getReasonForNoContacts')->andReturn('stuff')
            ->getMock();

        /** @var ReportStatusService $reportStatusService */
        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getContactsState();

        $this->assertEquals(ReportStatusService::DONE, $answer);
    }

    /** @test */
    public function indicateThatSafeguardingStateHasNotBeenStarted()
    {
        $safeguarding = m::mock('AppBundle\Entity\Safeguarding')
            ->shouldReceive('missingSafeguardingInfo')->andReturn(true)
            ->getMock();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getSafeguarding')->andReturn($safeguarding)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);
        $answer = $reportStatusService->getSafeguardingState();

        $this->assertEquals(ReportStatusService::NOTSTARTED, $answer);
    }

    /** @test */
    public function indicateThatSafeguardingStateIsComplete()
    {
        $safeguarding = m::mock('AppBundle\Entity\Safeguarding')
            ->shouldReceive('missingSafeguardingInfo')->andReturn(false)
            ->getMock();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getSafeguarding')->andReturn($safeguarding)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getSafeguardingState();

        $this->assertEquals(ReportStatusService::DONE, $answer);
    }

    /** @test */
    public function indicateAssetsNotStartedState()
    {
        $assets = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getAssets')->andReturn($assets)
            ->shouldReceive('getNoAssetToAdd')->andReturn(false)
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getAssetsState();

        $this->assertEquals(ReportStatusService::NOTSTARTED, $answer);
    }

    /** @test */
    public function indicateAssetsDoneWithAssets()
    {
        $assets = array(1);

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getAssets')->andReturn($assets)
            ->shouldReceive('getNoAssetToAdd')->andReturn(false)
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getAssetsState();

        $this->assertEquals(ReportStatusService::DONE, $answer);
    }

    /** @test */
    public function indicateAssetsDoneWithoutAssets()
    {
        $assets = array();

        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('getAssets')->andReturn($assets)
            ->shouldReceive('getNoAssetToAdd')->andReturn(true)
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $answer = $reportStatusService->getAssetsState();

        $this->assertEquals(ReportStatusService::DONE, $answer);
    }

    /** @test */
    public function testgetRemainingSections()
    {
        $contact = m::mock('AppBundle\Entity\Contact');
        $decision = m::mock('AppBundle\Entity\Decision');
        $asset = m::mock('AppBundle\Entity\Asset');

        $safeguarding = m::mock('AppBundle\Entity\Safeguarding')
            ->shouldReceive('missingSafeguardingInfo')->andReturn(false)
            ->getMock();

        $account = m::mock('AppBundle\Entity\Account')
            ->shouldReceive('hasClosingBalance')->andReturn(true)
            ->shouldReceive('hasMissingInformation')->andReturn(false)
            ->getMock();

        $mc = m::mock('AppBundle\Entity\MentalCapacity');
        
        $report = m::mock('AppBundle\Entity\Report')
            ->shouldReceive('isTotalsMatch')->andReturn(true)
            ->shouldReceive('getDecisions')->andReturn([$decision])
            ->shouldReceive('getContacts')->andReturn([$contact])
            ->shouldReceive('getMentalCapacity')->andReturn($mc)
            ->shouldReceive('getAssets')->andReturn([$asset])
            ->shouldReceive('getAccounts')->andReturn([$account])
            ->shouldReceive('getCourtOrderType')->andReturn(Report::PROPERTY_AND_AFFAIRS)
            ->shouldReceive('getSafeguarding')->andReturn($safeguarding)
            ->shouldReceive('getAction')->andReturn(m::mock('AppBundle\Entity\Action', ['isComplete' => true]))
            ->shouldReceive('isDue')->andReturn(true)
            ->getMock();

        $reportStatusService = new ReportStatusService($report, $this->translator);

        $ret = $reportStatusService->getRemainingSections();
        $this->assertEquals([], $ret);
    }
}
