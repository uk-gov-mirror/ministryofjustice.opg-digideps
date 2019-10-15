Feature: Users can access the correct clients

  Scenario: User in a non active organisation can only see their own Clients
    Given I am logged in as "behat-prof-org-1@org-1.co.uk" with password "Abcd1234"
    And I should see the "client-03000025" region
    And I should not see the "client-03000026" region
    And I should see the "client" region exactly 1 times
    When I click on "pa-report-open" in the "client-03000025" region
    And I save the report as "client-03000025-report"
    Then the response status code should be 200

  Scenario: User in an inactive organisation edits a report
    Given I am logged in as "behat-prof-org-2@org-1.co.uk" with password "Abcd1234"
    When I click on "pa-report-open" in the "client-03000026" region
    And I save the report as "client-03000026-report"
    Then the response status code should be 200

  Scenario: User attempts to view report not belonging to their client
    Given I am logged in as "behat-prof-org-1@org-1.co.uk" with password "Abcd1234"
    When I go to the report URL "overview" for "client-03000026-report"
    Then the response status code should be 500

  Scenario: User in an active organisation can only see the organisations Clients
    Given I am logged in to admin as "admin@publicguardian.gov.uk" with password "Abcd1234"
    And the organisation "behat-prof-org-3@org-2.co.uk" is active
    And "behat-prof-org-3@org-2.co.uk" has been added to the "behat-prof-org-3@org-2.co.uk" organisation
    When I am logged in as "behat-prof-org-3@org-2.co.uk" with password "Abcd1234"
    Then I should not see the "client-03000025" region
    And I should see the "client-03000027" region
    And I should see the "client-03000028" region
    When I click on "pa-report-open" in the "client-03000027" region
    And I save the report as "client-03000027-report"
    Then the response status code should be 200
