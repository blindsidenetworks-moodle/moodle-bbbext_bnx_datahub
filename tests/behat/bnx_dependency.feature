@mod @mod_bigbluebuttonbn @bbbext @bbbext_bnx_datahub @javascript
Feature: BNX Data Hub requires BNX extension
  As an administrator
  I should only be able to use Data Hub features when the BNX extension is enabled
  So that data collection depends on the parent extension

  Background:
    Given I enable "bigbluebuttonbn" "mod" plugin
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

  Scenario: Data Hub plugin is listed when BNX is enabled
    Given the bbbext "bnx" plugin is enabled
    And the bbbext "bnx_datahub" plugin is enabled
    And I log in as "admin"
    When I navigate to "Plugins > Activity modules > BigBlueButton > Manage BigBlueButton extension plugins" in site administration
    Then I should see "BigBlueButton BN Experience - Data Hub"

  Scenario: Data Hub reports are accessible when BNX is enabled
    Given the bbbext "bnx" plugin is enabled
    And the bbbext "bnx_datahub" plugin is enabled
    And the following "activities" exist:
      | activity        | course | name         | type |
      | bigbluebuttonbn | C1     | Test Meeting | 0    |
    And a BNX record exists for BigBlueButton activity "Test Meeting"
    And the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname  | timestart           | timeend             |
      | Test Meeting | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    And I navigate to the attendance report for "Test Meeting"
    Then I should see the attendance report

  Scenario: Standard BigBlueButton activity works without BNX
    Given the bbbext "bnx" plugin is disabled
    And the bbbext "bnx_datahub" plugin is disabled
    And the following "activities" exist:
      | activity        | course | name             | type |
      | bigbluebuttonbn | C1     | Standard Meeting | 0    |
    And I change window size to "large"
    And I am on the "Standard Meeting" "bigbluebuttonbn activity editing" page logged in as "teacher1"
    And I expand all fieldsets
    When I press "Save and return to course"
    Then I should see "Standard Meeting"
