Feature: provide feedback
        
    @deputy @feedback
    Scenario: I give feedback on all fields and it is emailed to OPG
        Given I am on the feedback page
        And I fill in the following:
            | feedback_difficulty | I found it to be really easy |
            | feedback_ideas | I think it needs an iPhone app |
            | feedback_help | No, I fill in this form myself |
            | feedback_satisfactionLevel | satisfied |
        And I press "feedback_save"
        Then the form should not contain an error
        And an email with subject "Digideps - Feedback" should have been sent 
        And the email should contain "I found it to be really easy"
        And the email should contain "I think it needs an iPhone app"
        And the email should contain "satisfied"
        And the email should contain "No, I fill in this form myself"
        
    @deputy @feedback
    Scenario: When I give feedback I dont have to fill all the fields in
        Given I am on the feedback page
        And I fill in the following:
            | feedback_difficulty | I found it to be really easy |
        And I press "feedback_save"
        Then the form should not contain an error
        And an email with subject "Digideps - Feedback" should have been sent 
        And the email should contain "I found it to be really easy"

    
    @deputy @feedback
    Scenario: After giving feedback I see a thank you
        Given I am on the feedback page
        And I fill in the following:
            | feedback_difficulty | I found it to be really easy |
        And I press "feedback_save"
        Then I should see "Thank you for sending your feedback"

        
    @deputy @feedback
    Scenario: On the feedback screen I can go back to my previous page
        Given I am on the login page
        And I goto the feedback page
        Then the "Back to previous page" link url should contain "/login"
        
        
    @deputy @feedback
    Scenario: On the thank you screen I see a link back to the client home
        Given I am on the login page
        And I goto the feedback page
        And I fill in the following:
            | feedback_difficulty | I found it to be really easy |
        And I press "feedback_save"
        Then the form should not contain an error
        And the "Return to your client page" link url should contain "/client"