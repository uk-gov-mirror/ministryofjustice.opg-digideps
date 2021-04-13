@v2 @accounts.ndr
Feature: Accounts (NDR)

  Scenario: A user adds one of each account type
    Given a Lay Deputy has not started an NDR report
    And I view the NDR overview page
    Then I should see "bank-accounts" as "not started"
    When I view and start the accounts report section
    And I add one of each account type with a mixture of responses
    Then I should see the expected accounts on the summary page
    When I follow link back to report overview page
    Then I should see "bank-accounts" as "7 accounts"

  Scenario: A user incorrectly enters an account before correctly entering it
    Given a Lay Deputy has not started an NDR report
    When I go to add a new current account
    And I miss one of the fields
    Then I get the correct validation responses
    When I try to enter letters where it should be digits
    Then I get the correct validation responses
    When I correctly enter account details
    Then I should see the expected accounts on the summary page

  Scenario: A user edits an existing account
    Given a Lay Deputy has a completed NDR report
    And I view the accounts report section
    Then I should be on the accounts summary page
    When I need to update my current account to a different one
    Then I should see the expected accounts on the summary page

  Scenario: A user adds accounts and then changes their mind and deletes them
    Given a Lay Deputy has not started an NDR report
    When I view and start the accounts report section
    And I add a couple of new accounts
    Then I should see the expected accounts on the summary page
    When I remove the second account
    Then I should see the expected accounts on the summary page
    When I remove the remaining account
    Then I should be on account start page
    When I follow link back to report overview page
    Then I should see "bank-accounts" as "not started"
