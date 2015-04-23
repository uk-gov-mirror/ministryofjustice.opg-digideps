Feature: report
    
    @deputy
    Scenario: test login goes to previous page
        Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
        And I go to the homepage
        Given I am on client home "client-home" and I click first report "report-n1"
        When I follow "tab-accounts"
        And I click on "account-n1"
        Then the URL should match "/report/\d+/account/\d+"
        When I expire the session
        # reload the page and trigger SesionListener 
        And I reload the page
        Then I should be on "/login"
        #And I should see the "session-timeout" region
        When I fill in the following:
          | login_email | behat-user@publicguardian.gsi.gov.uk |
          | login_password | Abcd1234 |
        And I press "login_login"
        #Then the URL should match "/report/\d+/account/\d+"
        
    @deputy
    Scenario: manual logout
      Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
      When I click on "logout"
      Then I should be on "/login"
      And I should see the "manual-logout-message" region

    @deputy
    Scenario: no cache
      Given I am logged in as "behat-user@publicguardian.gsi.gov.uk" with password "Abcd1234"
      And I go to the homepage
      Given I am on client home "client-home" and I click first report "report-n1"
      And I follow "tab-accounts"
      And I follow "tab-decisions"
      Then the response should have the "Cache-Control" header containing "no-cache"
      Then the response should have the "Cache-Control" header containing "no-store"
      Then the response should have the "Cache-Control" header containing "must-revalidate"
      Then the response should have the "Pragma" header containing "no-cache"
#      Then the response should have the "Expires" header containing "0"

        