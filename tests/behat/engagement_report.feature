@mod @mod_bigbluebuttonbn @bbbext @bbbext_bnx_datahub @javascript
Feature: BigBlueButton Data Hub engagement report
  As a teacher
  I should be able to view engagement reports for BigBlueButton activities
  So that I can track student participation and interaction levels

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

  Scenario: Teacher can view engagement report with engagement data
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role   | engchats | engtalks | engraisehand | engemojis | engpollvotes | engtalktime |
      | student1 | viewer | 15       | 3        | 2            | 5         | 4            | 120         |
      | student2 | viewer | 8        | 1        | 0            | 2         | 4            | 30          |
    And I change window size to "large"
    When I navigate to the engagement report for "Test Meeting"
    And I log in as "teacher1"
    Then I should see the engagement report
    And I should see attendee "Student 1" in the report
    And I should see attendee "Student 2" in the report

  Scenario: Teacher can view engagement report with no data
    Given I change window size to "large"
    When I navigate to the engagement report for "Test Meeting"
    And I log in as "teacher1"
    Then I should see the engagement report

  Scenario: Engagement report shows chat activity
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role   | engchats | engtalks | engraisehand | engemojis |
      | student1 | viewer | 25       | 5        | 3            | 10        |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I click on "Engagement report" "link"
    Then I should see the engagement report
    And I should see attendee "Student 1" in the report

  Scenario: Engagement report shows talk time metrics
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role      | engtalks | engtalktime |
      | teacher1 | moderator | 20       | 1800        |
      | student1 | viewer    | 5        | 300         |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I click on "Engagement report" "link"
    Then I should see the engagement report
    And I should see attendee "Teacher 1" in the report
    And I should see attendee "Student 1" in the report

  Scenario: Engagement report shows poll participation
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role   | engpollvotes |
      | student1 | viewer | 5            |
      | student2 | viewer | 3            |
      | student3 | viewer | 0            |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I click on "Engagement report" "link"
    Then I should see the engagement report
    And I should see attendee "Student 1" in the report
    And I should see attendee "Student 2" in the report

  Scenario: Engagement report shows raised hand activity
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role   | engraisehand |
      | student1 | viewer | 10           |
      | student2 | viewer | 2            |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I click on "Engagement report" "link"
    Then I should see the engagement report

  Scenario: Engagement report shows emoji reactions
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role   | engemojis |
      | student1 | viewer | 15        |
      | student2 | viewer | 8         |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I click on "Engagement report" "link"
    Then I should see the engagement report

  Scenario: Engagement report shows engagement scores
    Given the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    And the following attendee data exists for meeting "Test Meeting":
      | username | role   | score |
      | student1 | viewer | 95    |
      | student2 | viewer | 75    |
      | student3 | viewer | 50    |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I click on "Engagement report" "link"
    Then I should see the engagement report
