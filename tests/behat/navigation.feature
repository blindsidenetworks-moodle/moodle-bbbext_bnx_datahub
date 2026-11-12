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

  Scenario: Dashboard is not visible when meeting events disabled
    Given the following config values are set as admin:
      | bigbluebuttonbn_meetingevents_enabled | 0 |
    And I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "teacher1"
    Then I should not see "Dashboard +"

  Scenario: Student cannot see the dashboard in activity navigation
    Given I change window size to "large"
    When I am on the "Test Meeting" "bigbluebuttonbn activity" page logged in as "student1"
    Then I should not see "Dashboard +"
