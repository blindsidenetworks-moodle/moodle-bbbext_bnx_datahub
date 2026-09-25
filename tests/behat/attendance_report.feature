@mod @mod_bigbluebuttonbn @bbbext @bbbext_bnx_datahub @javascript
Feature: BigBlueButton Data Hub attendance report
  As a teacher
  I should be able to view attendance reports for BigBlueButton activities
  So that I can track student participation in meetings

  Background:
    Given I enable "bigbluebuttonbn" "mod" plugin
    And the bbbext "bnx" plugin is enabled
    And the bbbext "bnx_datahub" plugin is enabled
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following config values are set as admin:
      | bigbluebuttonbn_meetingevents_enabled | 1 |
    And the following "activities" exist:
      | activity        | course | name         | type |
      | bigbluebuttonbn | C1     | Test Meeting | 0    |
    And a BNX record exists for BigBlueButton activity "Test Meeting"

  Scenario: Teacher can view attendance report with meeting data
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role      | timejoin            | timeleave           |
      | student1 | viewer    | 2026-01-01 10:05:00 | 2026-01-01 10:55:00 |
      | student2 | viewer    | 2026-01-01 10:10:00 | 2026-01-01 11:00:00 |
      | teacher1 | moderator | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And I change window size to "large"
    When I navigate to the attendance report for "Test Meeting"
    And I log in as "teacher1"
    Then I should see the attendance report
    And I should see attendee "Student 1" in the report
    And I should see attendee "Student 2" in the report
    And I should see attendee "Teacher 1" in the report

  Scenario: Teacher can view attendance report with no meeting data
    Given I change window size to "large"
    When I navigate to the attendance report for "Test Meeting"
    And I log in as "teacher1"
    Then I should see the attendance report

  Scenario: Attendance report shows correct user roles
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role      | timejoin            | timeleave           |
      | teacher1 | moderator | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
      | student1 | viewer    | 2026-01-01 10:05:00 | 2026-01-01 10:55:00 |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I navigate to the attendance report for "Test Meeting"
    Then I should see the attendance report
    And I should see "Moderator"
    And I should see "Viewer"

  Scenario: Attendance report shows multiple meetings
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname    | internalmeetingid | timestart           | timeend             |
      | Test Meeting 1 | meeting-001       | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
      | Test Meeting 2 | meeting-002       | 2026-01-02 10:00:00 | 2026-01-02 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting 1":
      | username | role   | timejoin            | timeleave           |
      | student1 | viewer | 2026-01-01 10:05:00 | 2026-01-01 10:55:00 |
    And the following attendee data exists for meeting "Test Meeting 2":
      | username | role   | timejoin            | timeleave           |
      | student2 | viewer | 2026-01-02 10:10:00 | 2026-01-02 11:00:00 |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I navigate to the attendance report for "Test Meeting"
    Then I should see the attendance report
    And I should see attendee "Student 1" in the report
    And I should see attendee "Student 2" in the report

  Scenario: Students that did not attend are not shown
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role   | timejoin            | timeleave           |
      | student1 | viewer | 2026-01-01 10:05:00 | 2026-01-01 10:55:00 |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I navigate to the attendance report for "Test Meeting"
    Then I should see the attendance report
    And I should see attendee "Student 1" in the report
    And I should not see attendee "Student 3" in the report
