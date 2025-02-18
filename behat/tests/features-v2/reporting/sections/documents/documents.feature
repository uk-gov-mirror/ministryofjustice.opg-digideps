@v2 @documents
Feature: Documents - All User Roles

  Scenario: A user has no supporting documents to add
    Given a Lay Deputy has not started a report
    When I view and start the documents report section
    And I have no documents to upload
    Then I should be on the documents summary page
    And the documents summary page should not contain any documents

  Scenario: A user uploads one supporting document that has a valid file type
    Given a Lay Deputy has not started a report
    When I view and start the documents report section
    And I have documents to upload
    And I upload one valid document
    Then the documents uploads page should contain the documents I uploaded
    When I have no further documents to upload
    Then I should be on the documents summary page
    And the documents summary page should contain the documents I uploaded

  Scenario: A user uploads multiple supporting document that have valid file types
    Given a Lay Deputy has not started a report
    When I view and start the documents report section
    And I have documents to upload
    And I upload multiple valid documents
    Then the documents uploads page should contain the documents I uploaded
    When I have no further documents to upload
    Then I should be on the documents summary page
    And the documents summary page should contain the documents I uploaded

  Scenario: A user deletes one supporting document they uploaded from the uploads page
    Given a Lay Deputy has not started a report
    When I view and start the documents report section
    And I have documents to upload
    And I upload multiple valid documents
    And I remove one document I uploaded
    When I have no further documents to upload
    Then I should be on the documents summary page
    And the documents summary page should contain the documents I uploaded

  Scenario: A user deletes one supporting document they uploaded from the summary page
    Given a Lay Deputy has not started a report
    When I view and start the documents report section
    And I have documents to upload
    And I upload multiple valid documents
    When I have no further documents to upload
    Then I should be on the documents summary page
    When I remove one document I uploaded
    Then the documents summary page should contain the documents I uploaded

  Scenario: A user uploads one supporting document that has an invalid file type
    Given a Lay Deputy has not started a report
    When I view and start the documents report section
    And I have documents to upload
    And I upload one document with an unsupported file type
    Then I should see an 'invalid file type' error

  Scenario: A user uploads one supporting document that has a valid file type but is too large
    Given a Lay Deputy has not started a report
    When I view and start the documents report section
    And I have documents to upload
    And I upload one document that is too large
    Then I should see a 'file too large' error

  Scenario: A user uploads one supporting document that has a valid file type then confirms they have no files to upload
    Given a Lay Deputy has not started a report
    When I view and start the documents report section
    And I have documents to upload
    And I upload one valid document
    Then the documents uploads page should contain the documents I uploaded
    When I have no further documents to upload
    Then I should be on the documents summary page
    When I change my mind and confirm I have no documents to upload
    Then I should see an 'answer could not be updated' error
