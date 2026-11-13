@mod @mod_bigbluebuttonbn @bbbext @bbbext_bnx_datahub @javascript
Feature: BigBlueButton Data Hub permissions
  As an administrator
  I should be able to control access to Data Hub reports via capabilities
  So that only authorized users can view sensitive meeting data

  Background:
    Given I enable "bigbluebuttonbn" "mod" plugin
    And the bbbext "bnx" plugin is enabled
    And the bbbext "bnx_datahub" plugin is enabled
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | teacher1  | Teacher   | 1        | teacher1@example.com  |
      | teacher2  | Teacher   | 2        | teacher2@example.com  |
      | student1  | Student   | 1        | student1@example.com  |
      | manager1  | Manager   | 1        | manager1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | teacher        |
      | student1 | C1     | student        |
      | manager1 | C1     | manager        |
    And the following config values are set as admin:
      | bigbluebuttonbn_meetingevents_enabled | 1 |
    And the following "activities" exist:
      | activity        | course | name         | type |
      | bigbluebuttonbn | C1     | Test Meeting | 0    |
    And a BNX record exists for BigBlueButton activity "Test Meeting"
    And the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role      | engchats | engtalks |
      | teacher1 | moderator | 10       | 15       |
      | student1 | viewer    | 5        | 2        |

  Scenario: Editing teacher can view attendance report
    Given I change window size to "large"
    When I navigate to the attendance report for "Test Meeting"
    And I log in as "teacher1"
    Then I should see the attendance report

  Scenario: Editing teacher can view engagement report
    Given I change window size to "large"
    When I navigate to the engagement report for "Test Meeting"
    And I log in as "teacher1"
    Then I should see the engagement report

  Scenario: Manager can view attendance report
    Given I change window size to "large"
    When I navigate to the attendance report for "Test Meeting"
    And I log in as "manager1"
    Then I should see the attendance report

  Scenario: Manager can view engagement report
    Given I change window size to "large"
    When I navigate to the engagement report for "Test Meeting"
    And I log in as "manager1"
    Then I should see the engagement report

  Scenario: Student does not see attendance report link
    Given I change window size to "large"
    And I log in as "student1"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page
    Then "Attendance report" "link" should not exist

  Scenario: Student does not see engagement report link
    Given I change window size to "large"
    And I log in as "student1"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page
    Then "Engagement report" "link" should not exist

  Scenario: Admin can view attendance report
    Given I change window size to "large"
    When I navigate to the attendance report for "Test Meeting"
    And I log in as "admin"
    Then I should see the attendance report

  Scenario: Admin can view engagement report
    Given I change window size to "large"
    When I navigate to the engagement report for "Test Meeting"
    And I log in as "admin"
    Then I should see the engagement report

  Scenario: Non-editing teacher without capability does not see report links
    Given the following "role capability" exists:
      | role                          | teacher  |
      | bbbext/bnx_datahub:viewreports | prohibit |
    And I change window size to "large"
    And I log in as "teacher2"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page
    Then "Attendance report" "link" should not exist
    And "Engagement report" "link" should not exist

  Scenario: Report viewer cannot exclude sessions
    Given the following "role capability" exists:
      | role                              | editingteacher |
      | bbbext/bnx_datahub:manageexclusions | prohibit       |
    And I change window size to "large"
    And I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    When I click on "Dashboard +" "link"
    Then I should see "Session summary"
    And "Exclude session" "button" should not exist
    When I click on "Excluded sessions (0)" "link"
    Then I should see "There are no excluded sessions."

  Scenario: Guest user cannot access attendance report
    Given I change window size to "large"
    And I log out
    When I navigate to the attendance report for "Test Meeting"
    Then I should see "Log in"

  Scenario: Guest user cannot access engagement report
    Given I change window size to "large"
    And I log out
    When I navigate to the engagement report for "Test Meeting"
    Then I should see "Log in"
