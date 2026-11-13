@mod @mod_bigbluebuttonbn @bbbext @bbbext_bnx_datahub @javascript
Feature: BigBlueButton Data Hub navigation
  As a teacher with appropriate permissions
  I should see the DataHub dashboard in the BigBlueButton activity navigation
  So that I can access activity insights and detailed reports

  Background:
    Given I enable "bigbluebuttonbn" "mod" plugin
    And the bbbext "bnx" plugin is enabled
    And the bbbext "bnx_datahub" plugin is enabled
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | bigbluebuttonbn_meetingevents_enabled | 1 |
    And the following "activities" exist:
      | activity        | course | name         | type |
      | bigbluebuttonbn | C1     | Test Meeting | 0    |

  Scenario: Teacher can see dashboard in activity navigation when meeting events enabled
    Given I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    Then I should see "Dashboard +"
    And I should not see "Attendance report +"
    And I should not see "Engagement report +"

  Scenario: Teacher can navigate to dashboard from activity
    Given I change window size to "large"
    And I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    When I click on "Dashboard +" "link"
    Then I should see "DataHub dashboard +"

  Scenario: Session selection updates the learner participation chart
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname       | timestart           | timeend             |
      | Populated session | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
      | Empty session     | 2026-01-02 10:00:00 | 2026-01-02 11:00:00 |
    And the following attendee data exists for meeting "Populated session":
      | username | role   | engchats |
      | student1 | viewer | 2        |
    And I change window size to "large"
    And I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    When I click on "Dashboard +" "link"
    Then I should see "No learner attendance was recorded for this session." in the ".bbbext-bnx-datahub-dashboard__chart--wide" "css_element"
    When I select the dashboard session "Populated session"
    Then I should see "Populated session"
    And I click on "Show chart data" "link" in the ".bbbext-bnx-datahub-dashboard__chart--wide" "css_element"
    And I should see "Student 1" in the ".bbbext-bnx-datahub-dashboard__chart--wide" "css_element"
    When I select the dashboard session "Empty session"
    Then I should see "No learner attendance was recorded for this session." in the ".bbbext-bnx-datahub-dashboard__chart--wide" "css_element"

  Scenario: Dashboard is not visible when meeting events disabled
    Given the following config values are set as admin:
      | bigbluebuttonbn_meetingevents_enabled | 0 |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    Then I should not see "Dashboard +"

  Scenario: Session chart and controls remain available on mobile
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname       | timestart           | timeend             |
      | Populated session | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Populated session":
      | username | role   | engchats |
      | student1 | viewer | 2        |
    And I change window size to "large"
    And I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    When I click on "Dashboard +" "link"
    Then ".bbbext-bnx-datahub-dashboard__chart--wide canvas" "css_element" should exist
    And I should see "Exclude session"
    When I change window size to "375x812"
    Then ".bbbext-bnx-datahub-dashboard__chart--wide canvas" "css_element" should exist
    And I should see "Session summary"
    And I should see "Exclude session"

  Scenario: Teacher can exclude and restore the only session
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname | timestart           | timeend             |
      | One session | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And I change window size to "large"
    And I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    When I click on "Dashboard +" "link"
    And I press "Exclude session"
    Then I should see "Its data will be retained"
    When I press "Exclude session"
    Then I should see "All sessions are excluded"
    When I click on "Excluded sessions (1)" "link"
    Then I should see "One session"
    And I should see "Duration" in the "table" "css_element"
    When I press "Restore"
    Then I should see "Session restored."
    And I should see "There are no excluded sessions."
    When I click on "Back to dashboard" "link"
    Then I should see "One session"

  Scenario: Exclusion requires a valid session key
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname | timestart           | timeend             |
      | One session | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And I change window size to "large"
    And I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    When I click on "Dashboard +" "link"
    And I submit the exclusion form with an invalid session key
    Then I should see "Invalid session key rejected: true"
    And I should see "Excluded sessions (0)"

  Scenario: Student cannot see the dashboard in activity navigation
    Given I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "student1"
    Then I should not see "Dashboard +"
