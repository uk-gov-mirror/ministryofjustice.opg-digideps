@v2 @feedback
Feature: Providing feedback after submitting a report
  Scenario: A user does not provide feedback
    Given a Lay Deputy has submitted a report
    When I visit the report submitted page
    When I press "Your reports"
    Then I should be on the Lay homepage

  Scenario: A user provides satisfaction feedback
    Given a Lay Deputy has submitted a report
    When I provide some post-submission feedback
    Then I should be on the post-submission user research page

  Scenario: A user provides user research feedback
    Given a Lay Deputy has submitted a report
    When I provide valid user research responses
    Then I should be on the user research feedback submitted page
    When I press "Your reports"
    Then I should be on the Lay homepage
