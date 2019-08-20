Feature: User-facing organisation management

  @prof
  Scenario: Without organisation, user cannot access settings pages
    Given I am logged in as "behat-prof-admin@publicguardian.gov.uk" with password "Abcd1234"
    When I go to "/org/settings"
    And I follow "User accounts"
    Then I should be on "/org/settings/user-accounts"
    When I go to "/org/settings/organisation"
    Then the response status code should be 404

  @prof
  Scenario: Set up organisation
    Given I am logged in to admin as "admin@publicguardian.gov.uk" with password "Abcd1234"
    When I go to admin page "/admin/organisations/add"
    And I fill in "organisation_name" with "Leever Partners"
    And I fill in "organisation_emailIdentifierType_0" with "domain"
    And I fill in "organisation_emailDomain" with "leever.example"
    And I fill in "organisation_isActivated_0" with "1"
    And I press "Save organisation"
    And I follow "Leever Partners"
    And I follow "Add someone to this organisation"
    And I fill in "organisation_add_user_email" with "behat-prof-admin@publicguardian.gov.uk"
    And I press "Find user"
    And I press "Add user to organisation"
    Then the URL should match "admin/organisations/\d+"
    And I should see "Leever Partners"
    And I should see "Professional Admin User"

  @prof
  Scenario: User can view their organisation
    Given I am logged in as "behat-prof-admin@publicguardian.gov.uk" with password "Abcd1234"
    When I go to "/org/settings"
    And I follow "User accounts"
    Then the URL should match "/org/settings/organisation/\d+"
    And the response status code should be 200
    And I should see "Leever Partners"
    And I should see "Professional Admin User"
