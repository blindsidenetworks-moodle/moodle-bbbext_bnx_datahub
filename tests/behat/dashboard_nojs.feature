@mod @mod_bigbluebuttonbn @bbbext @bbbext_bnx_datahub
Feature: Select a dashboard session without JavaScript
  As a teacher
  I need to select a session when JavaScript is unavailable

  Scenario: Show a selected session without JavaScript
    Given I enable "bigbluebuttonbn" "mod" plugin
    And the bbbext "bnx" plugin is enabled
    And the bbbext "bnx_datahub" plugin is enabled
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | bigbluebuttonbn_meetingevents_enabled | 1 |
    And the following "activities" exist:
      | activity        | course | name         | type |
      | bigbluebuttonbn | C1     | Test Meeting | 0    |
    And the following meeting data exists for BigBlueButton activity "Test Meeting":
      | meetingname   | timestart           | timeend             |
      | Older session | 2026-01-01 10:00:00 | 2026-01-01 11:00:00 |
      | Newer session | 2026-01-02 10:00:00 | 2026-01-02 11:00:00 |
    And I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    When I click on "Dashboard +" "link"
    Then I should see "Newer session"
    And "Show" "button" should exist
    When I select the dashboard session "Older session"
    And I click on "Show" "button" in the ".bbbext-bnx-datahub-dashboard__selector" "css_element"
    Then I should see "Older session"
