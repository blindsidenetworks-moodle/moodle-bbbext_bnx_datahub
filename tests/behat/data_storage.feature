@mod @mod_bigbluebuttonbn @bbbext @bbbext_bnx_datahub @javascript
Feature: BigBlueButton Data Hub data storage
  As a system
  Meeting and attendee data should be properly stored and retrieved
  So that reports can display accurate information

  Background:
    Given I enable "bigbluebuttonbn" "mod" plugin
    And the bbbext "bnx" plugin is enabled
    And the bbbext "bnx_datahub" plugin is enabled
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following config values are set as admin:
      | bigbluebuttonbn_meetingevents_enabled | 1 |
    And the following "activities" exist:
      | activity        | course | name         | type |
      | bigbluebuttonbn | C1     | Test Meeting | 0    |
    And a BNX record exists for BigBlueButton activity "Test Meeting"

  Scenario: Meeting data is stored correctly
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | internalmeetingid | timestart           | timeend             |
      | Test Meeting | meeting-12345     | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    Then meeting data should exist for BigBlueButton activity "Test Meeting"

  Scenario: Attendee data is stored correctly
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role   | timejoin            | timeleave           |
      | student1 | viewer | 2026-01-01 10:05:00 | 2026-01-01 10:55:00 |
    Then attendee data should exist for meeting "Test Meeting"

  Scenario: Multiple attendees can be stored for a single meeting
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role      | timejoin            | timeleave           |
      | teacher1 | moderator | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
      | student1 | viewer    | 2026-01-01 10:05:00 | 2026-01-01 10:55:00 |
      | student2 | viewer    | 2026-01-01 10:10:00 | 2026-01-01 11:00:00 |
    Then attendee data should exist for meeting "Test Meeting"

  Scenario: Multiple meetings can be stored for an activity
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname    | internalmeetingid | timestart           | timeend             |
      | First Meeting  | meeting-001       | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
      | Second Meeting | meeting-002       | 2026-01-02 10:00:00 | 2026-01-02 11:00:00 |
      | Third Meeting  | meeting-003       | 2026-01-03 10:00:00 | 2026-01-03 11:00:00 |
    Then meeting data should exist for BigBlueButton activity "Test Meeting"

  Scenario: Engagement metrics are stored correctly
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role   | engchats | engtalks | engraisehand | engemojis | engpollvotes | engtalktime | score |
      | student1 | viewer | 25       | 10       | 5            | 15        | 8            | 600         | 85    |
    Then attendee data should exist for meeting "Test Meeting"

  Scenario: Activity without meetings shows empty report
    Given I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I navigate to the attendance report for "Test Meeting"
    Then I should see the attendance report
    And no meeting data should exist for BigBlueButton activity "Test Meeting"
