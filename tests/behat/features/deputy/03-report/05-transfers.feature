Feature: deputy / report / account transfers

    @deputy
    Scenario: account transfers
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        And I am on the accounts page of the "2016" report
        And I follow "account-transfers"
        # wrong values (wrong amount types, amount without explanation, explanation without amount)
        When I press "transfers_save"
        Then the following fields should have an error:
            | transfers_accountFromId |
            | transfers_accountToId   |
            | transfers_amount_0        |
        And I save the page as "report-account-transfers-errors"
        # right values
        When I fill in "transfers_amount_0" with "1200"
        And I select "HSBC main account (****0876)" from "transfers_accountFromId"
        And I select "temp (****8888)" from "transfers_accountToId"
        And I press "transfers_save"
        Then the form should be valid
        Then I should see the "transfer-n-1" region
        # delete
        When I follow "delete-button"
        Then I should not see the "transfer-n-1" region
        # no transfers
        Given the checkbox "report_no_transfers_noTransfersToAdd" is not checked
        When I check "report_no_transfers_noTransfersToAdd"
        And I press "report_no_transfers_saveNoTransfer"
        Then the checkbox "report_no_transfers_noTransfersToAdd" should be checked 