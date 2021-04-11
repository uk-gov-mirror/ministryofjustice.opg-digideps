<?php declare(strict_types=1);

namespace DigidepsBehat\v2\Reporting\Sections;

trait AccountsSectionTrait
{
    private array $accountList = [];

    /**
     * @When I view the accounts report section
     */
    public function iViewAccountsSection()
    {
        $activeReportId = $this->loggedInUserDetails->getCurrentReportId();
        $reportSectionUrl = sprintf(self::REPORT_SECTION_ENDPOINT, $activeReportId, 'bank-accounts');
        $this->visitPath($reportSectionUrl);
    }

    /**
     * @When I view and start the accounts report section
     */
    public function iViewAndStartAccountsSection()
    {
        $this->iViewAccountsSection();
        $this->clickLink('Start accounts');
    }

    /**
     * @When I go to add a new current account
     */
    public function iGoToAddNewCurrentAccount()
    {
        $account = [
            'account' => 'current',
            'accountType' => 'current account',
            'name' => 'account-1',
            'accountNumber' => '1111',
            'sortCode' => '01-01-01',
            'joint' => 'no',
            'openingBalance' => '101',
            'closingBalance' => '201'
        ];

        $this->accountList[] = $account;
        $this->visitPath($this->getAccountsAddAnAccountUrl($this->loggedInUserDetails->getCurrentReportId()));
        $this->iAmOnAccountsAddInitialPage();
        $this->iChooseAccountType('current');
    }

    /**
     * @When I miss one of the fields
     */
    public function iMissOneOfTheFields()
    {
        $this->iFillInAccountDetails(
            '',
            '01-01-01',
            'no',
            'account-1'
        );
    }

    /**
     * @When I add one of each account type with a mixture of responses
     */
    public function iAddOneOfEachTypeOfAccounts()
    {
        $this->accountList = [
            [
                'account' => 'current',
                'accountType' => 'current account',
                'name' => 'account-1',
                'accountNumber' => '1111',
                'sortCode' => '01-01-01',
                'joint' => 'no',
                'openingBalance' => '101',
                'closingBalance' => '201'
            ],
            [
                'account' => 'savings',
                'accountType' => 'savings account',
                'name' => 'account-2',
                'accountNumber' => '2222',
                'sortCode' => '02-02-02',
                'joint' => 'yes',
                'openingBalance' => '102',
                'closingBalance' => '202'
            ],
            [
                'account' => 'isa',
                'accountType' => 'isa',
                'name' => 'account-3',
                'accountNumber' => '3333',
                'sortCode' => '03-03-03',
                'joint' => 'no',
                'openingBalance' => '103',
                'closingBalance' => '203'
            ],
            [
                'account' => 'postoffice',
                'accountType' => 'post office account',
                'name' => '',
                'accountNumber' => '4444',
                'sortCode' => '',
                'joint' => 'yes',
                'openingBalance' => '104',
                'closingBalance' => '204'
            ],
            [
                'account' => 'cfo',
                'accountType' => 'court funds office account',
                'name' => '',
                'accountNumber' => '5555',
                'sortCode' => '',
                'joint' => 'no',
                'openingBalance' => '105',
                'closingBalance' => '205'
            ],
            [
                'account' => 'other',
                'accountType' => 'other',
                'name' => 'account-6',
                'accountNumber' => '6666',
                'sortCode' => '06-06-06',
                'joint' => 'yes',
                'openingBalance' => '106',
                'closingBalance' => '206'
            ],
            [
                'account' => 'other_no_sortcode',
                'accountType' => 'other without sort code',
                'name' => 'account-7',
                'accountNumber' => '7777',
                'sortCode' => '',
                'joint' => 'no',
                'openingBalance' => '107',
                'closingBalance' => '207'
            ],
        ];

        foreach ($this->accountList as $account) {
            $this->iAddAnAccount(
                $account['account'],
                $account['name'],
                $account['accountNumber'],
                $account['sortCode'],
                $account['joint'],
                $account['openingBalance'],
                $account['closingBalance'],
            );
        }
        $this->iAmOnAccountsAddAnotherPage();
        $this->selectOption('add_another[addAnother]', 'no');
        $this->pressButton('Continue');
    }

    /**
     * @Then I should see the expected accounts on the summary page
     */
    public function iShouldSeeTheExpectedAccountsOnSummaryPage()
    {
        $this->iAmOnAccountsSummaryPage();

        $tableBody = $this->getSession()->getPage()->find('css', 'tbody');

        if (!$tableBody) {
            $this->throwContextualException('A tbody element was not found on the page');
        }

        $tableRows = $tableBody->findAll('css', 'tr');

        if (!$tableRows) {
            $this->throwContextualException('A tr element was not found on the page');
        }

        foreach ($tableRows as $tRowKey=>$tableRow) {
            $tableHeader = $tableRow->find('css', 'th');
            $headHtml = trim(strtolower($tableHeader->getHtml()));
            assert(
                str_contains($headHtml, $this->accountList[$tRowKey]['accountType']),
                sprintf('matching account %s ', $this->accountList[$tRowKey]['accountType'])
            );
            assert(
                str_contains($headHtml, $this->accountList[$tRowKey]['name']),
                sprintf('matching name %s ', $this->accountList[$tRowKey]['name'])
            );
            assert(
                str_contains($headHtml, $this->accountList[$tRowKey]['accountNumber']),
                sprintf('matching account number %s ', $this->accountList[$tRowKey]['accountNumber'])
            );
            $sortCode = str_replace('-', '', $this->accountList[$tRowKey]['sortCode']);
            assert(
                str_contains($headHtml, $sortCode),
                sprintf('matching sort code %s ', $sortCode)
            );
            assert(
                str_contains($headHtml, $this->accountList[$tRowKey]['joint']),
                sprintf('matching sort code %s ', $this->accountList[$tRowKey]['joint'])
            );

            $tableFields = $tableRow->findAll('css', 'td');

            foreach ($tableFields as $tFieldKey=>$tableField) {
                $balanceItem = trim(strtolower($tableField->getHtml()));
                if ($tFieldKey == 0) {
                    assert(
                        str_contains($balanceItem, $this->accountList[$tRowKey]['openingBalance']),
                        $this->accountList[$tRowKey]['openingBalance']
                    );
                } elseif ($tFieldKey == 1) {
                    assert(
                        str_contains($balanceItem, $this->accountList[$tRowKey]['closingBalance']),
                        $this->accountList[$tRowKey]['closingBalance']
                    );
                }
            }
        }
    }

    public function iAddAnAccount(
        string $account,
        string $name,
        string $accountNumber,
        string $sortCode,
        string $joint,
        string $openingBalance,
        string $closingBalance
    ) {
        $this->iChooseAccountType($account);
        $this->iFillInAccountDetails($name, $accountNumber, $sortCode, $joint);
        $this->iFillInAccountBalance($openingBalance, $closingBalance);
    }

    public function iChooseAccountType(string $account)
    {
        $this->visitPath($this->getAccountsAddAnAccountUrl($this->loggedInUserDetails->getCurrentReportId()));
        $this->iSelectRadioBasedOnName('div', 'data-module', 'govuk-radios', $account);
        $this->pressButton('Save and continue');
    }

    public function iFillInAccountDetails(string $accountNumber, string $sortCode, string $joint, string $name)
    {
        if ($this->elementExistsOnPage('input', 'name', 'account[bank]')) {
            $this->fillField('account[bank]', $name);
        }

        $this->fillField('account[accountNumber]', $accountNumber);

        if ($this->elementExistsOnPage('input', 'name', 'account[sortCode][sort_code_part_1]')) {
            $this->fillField('account[sortCode][sort_code_part_1]', explode('-', $sortCode)[0]);
            $this->fillField('account[sortCode][sort_code_part_2]', explode('-', $sortCode)[1]);
            $this->fillField('account[sortCode][sort_code_part_3]', explode('-', $sortCode)[2]);
        }

        $this->iSelectRadioBasedOnName('div', 'data-module', 'govuk-radios', $joint);
        $this->pressButton('Save and continue');
    }

    public function iFillInAccountBalance(string $openingBalance, string $closingBalance)
    {
        $this->fillField('account[openingBalance]', $openingBalance);
        $this->fillField('account[closingBalance]', $closingBalance);
        $this->pressButton('Save and continue');
    }
}
