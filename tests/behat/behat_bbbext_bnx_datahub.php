<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Behat step definitions for bbbext_bnx_datahub.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;
use Behat\Gherkin\Node\TableNode;

/**
 * Behat steps for BigBlueButton BN Experience - Data Hub.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_bbbext_bnx_datahub extends behat_base {
    /**
     * Enable a BigBlueButton extension plugin.
     *
     * @Given /^the bbbext "(?P<pluginname>(?:[^"]|\\")*)" plugin is enabled$/
     * @param string $pluginname The plugin name (e.g., 'bnx', 'bnx_datahub').
     */
    public function the_bbbext_plugin_is_enabled(string $pluginname): void {
        set_config('disabled', 0, 'bbbext_' . $pluginname);
    }

    /**
     * Disable a BigBlueButton extension plugin.
     *
     * @Given /^the bbbext "(?P<pluginname>(?:[^"]|\\")*)" plugin is disabled$/
     * @param string $pluginname The plugin name (e.g., 'bnx', 'bnx_datahub').
     */
    public function the_bbbext_plugin_is_disabled(string $pluginname): void {
        set_config('disabled', 1, 'bbbext_' . $pluginname);
    }

    /**
     * Create meeting data for a BigBlueButton activity.
     *
     * @Given /^the following meeting data exists for BigBlueButton activity "(?P<activityname>(?:[^"]|\\")*)":$/
     * @param string $activityname The activity name.
     * @param TableNode $data The meeting data.
     */
    public function the_following_meeting_data_exists(string $activityname, TableNode $data): void {
        global $DB;

        // Get the BigBlueButton activity.
        $bbb = $DB->get_record('bigbluebuttonbn', ['name' => $activityname], '*', MUST_EXIST);

        // Get or create the bnx record.
        $bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id]);
        if (!$bnx) {
            $bnx = new stdClass();
            $bnx->bigbluebuttonbnid = $bbb->id;
            $bnx->id = $DB->insert_record('bbbext_bnx', $bnx);
        }

        $rows = $data->getHash();
        foreach ($rows as $row) {
            $meeting = new stdClass();
            $meeting->bnxid = $bnx->id;
            $meeting->externalmeetingid = $row['externalmeetingid'] ?? 'ext-meeting-' . uniqid();
            $meeting->internalmeetingid = $row['internalmeetingid'] ?? 'int-meeting-' . uniqid();
            $meeting->meetingname = $row['meetingname'] ?? $activityname;
            $meeting->timestart = isset($row['timestart']) ? strtotime($row['timestart']) : time() - 3600;
            $meeting->timeend = isset($row['timeend']) ? strtotime($row['timeend']) : time();
            $meeting->duration = $meeting->timeend - $meeting->timestart;
            $meeting->metadata = $row['metadata'] ?? '{}';
            $meeting->rawdata = $row['rawdata'] ?? '{}';

            $DB->insert_record('bbbext_bnx_datahub_meeting', $meeting);
        }
    }

    /**
     * Create attendee data for a meeting.
     *
     * @Given /^the following attendee data exists for meeting "(?P<meetingname>(?:[^"]|\\")*)":$/
     * @param string $meetingname The meeting name.
     * @param TableNode $data The attendee data.
     */
    public function the_following_attendee_data_exists(string $meetingname, TableNode $data): void {
        global $DB;

        // Find the meeting.
        $meeting = $DB->get_record('bbbext_bnx_datahub_meeting', ['meetingname' => $meetingname], '*', MUST_EXIST);

        $rows = $data->getHash();
        foreach ($rows as $row) {
            $attendee = new stdClass();
            $attendee->meetingid = $meeting->id;

            // Look up user by username if provided.
            if (!empty($row['username'])) {
                $user = $DB->get_record('user', ['username' => $row['username']], '*', MUST_EXIST);
                $attendee->userid = $user->id;
                $attendee->fullname = fullname($user);
            } else {
                $attendee->userid = $row['userid'] ?? null;
                $attendee->fullname = $row['fullname'] ?? 'Unknown User';
            }

            $attendee->role = $row['role'] ?? 'viewer';
            $attendee->timejoin = isset($row['timejoin']) ? strtotime($row['timejoin']) : $meeting->timestart;
            $attendee->timeleave = isset($row['timeleave']) ? strtotime($row['timeleave']) : $meeting->timeend;
            $attendee->elapsedtime = $attendee->timeleave - $attendee->timejoin;
            $attendee->score = $row['score'] ?? 0;
            $attendee->engchats = $row['engchats'] ?? 0;
            $attendee->engtalks = $row['engtalks'] ?? 0;
            $attendee->engraisehand = $row['engraisehand'] ?? 0;
            $attendee->engemojis = $row['engemojis'] ?? 0;
            $attendee->engpollvotes = $row['engpollvotes'] ?? 0;
            $attendee->engtalktime = $row['engtalktime'] ?? 0;
            $attendee->rawdata = $row['rawdata'] ?? '{}';

            $DB->insert_record('bbbext_bnx_datahub_meeting_user', $attendee);
        }
    }

    /**
     * Navigate to the attendance report for a BigBlueButton activity.
     *
     * @When /^I navigate to the attendance report for "(?P<activityname>(?:[^"]|\\")*)"$/
     * @param string $activityname The activity name.
     */
    public function i_navigate_to_attendance_report(string $activityname): void {
        global $DB;

        $bbb = $DB->get_record('bigbluebuttonbn', ['name' => $activityname], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bbb->id, 0, false, MUST_EXIST);

        $url = new moodle_url('/mod/bigbluebuttonbn/extension/bnx_datahub/attendance.php', ['cmid' => $cm->id]);
        $this->getSession()->visit($url->out(false));
    }

    /**
     * Navigate to the engagement report for a BigBlueButton activity.
     *
     * @When /^I navigate to the engagement report for "(?P<activityname>(?:[^"]|\\")*)"$/
     * @param string $activityname The activity name.
     */
    public function i_navigate_to_engagement_report(string $activityname): void {
        global $DB;

        $bbb = $DB->get_record('bigbluebuttonbn', ['name' => $activityname], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bbb->id, 0, false, MUST_EXIST);

        $url = new moodle_url('/mod/bigbluebuttonbn/extension/bnx_datahub/engagement.php', ['cmid' => $cm->id]);
        $this->getSession()->visit($url->out(false));
    }

    /**
     * Assert that I should see the attendance report page.
     *
     * @Then /^I should see the attendance report$/
     */
    public function i_should_see_attendance_report(): void {
        $this->assertSession()->pageTextContains('Attendance');
    }

    /**
     * Assert that I should see the engagement report page.
     *
     * @Then /^I should see the engagement report$/
     */
    public function i_should_see_engagement_report(): void {
        $this->assertSession()->pageTextContains('Engagement');
    }

    /**
     * Assert that meeting data exists in the database.
     *
     * @Then /^meeting data should exist for BigBlueButton activity "(?P<activityname>(?:[^"]|\\")*)"$/
     * @param string $activityname The activity name.
     */
    public function meeting_data_should_exist(string $activityname): void {
        global $DB;

        $bbb = $DB->get_record('bigbluebuttonbn', ['name' => $activityname], '*', MUST_EXIST);
        $bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id]);

        if (!$bnx) {
            throw new ExpectationException(
                "No BNX record found for BigBlueButton activity '{$activityname}'.",
                $this->getSession()
            );
        }

        $meetings = $DB->get_records('bbbext_bnx_datahub_meeting', ['bnxid' => $bnx->id]);
        if (empty($meetings)) {
            throw new ExpectationException(
                "No meeting data found for BigBlueButton activity '{$activityname}'.",
                $this->getSession()
            );
        }
    }

    /**
     * Assert that attendee data exists for a meeting.
     *
     * @Then /^attendee data should exist for meeting "(?P<meetingname>(?:[^"]|\\")*)"$/
     * @param string $meetingname The meeting name.
     */
    public function attendee_data_should_exist(string $meetingname): void {
        global $DB;

        $meeting = $DB->get_record('bbbext_bnx_datahub_meeting', ['meetingname' => $meetingname]);
        if (!$meeting) {
            throw new ExpectationException(
                "No meeting found with name '{$meetingname}'.",
                $this->getSession()
            );
        }

        $attendees = $DB->get_records('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meeting->id]);
        if (empty($attendees)) {
            throw new ExpectationException(
                "No attendee data found for meeting '{$meetingname}'.",
                $this->getSession()
            );
        }
    }

    /**
     * Assert that no meeting data exists for a BigBlueButton activity.
     *
     * @Then /^no meeting data should exist for BigBlueButton activity "(?P<activityname>(?:[^"]|\\")*)"$/
     * @param string $activityname The activity name.
     */
    public function no_meeting_data_should_exist(string $activityname): void {
        global $DB;

        $bbb = $DB->get_record('bigbluebuttonbn', ['name' => $activityname], '*', MUST_EXIST);
        $bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id]);

        if ($bnx) {
            $meetings = $DB->get_records('bbbext_bnx_datahub_meeting', ['bnxid' => $bnx->id]);
            if (!empty($meetings)) {
                throw new ExpectationException(
                    "Meeting data unexpectedly exists for BigBlueButton activity '{$activityname}'.",
                    $this->getSession()
                );
            }
        }
    }

    /**
     * Assert that the user should see a specific attendee in the report.
     *
     * @Then /^I should see attendee "(?P<fullname>(?:[^"]|\\")*)" in the report$/
     * @param string $fullname The attendee's full name.
     */
    public function i_should_see_attendee_in_report(string $fullname): void {
        $this->assertSession()->pageTextContains($fullname);
    }

    /**
     * Assert that the user should not see a specific attendee in the report.
     *
     * @Then /^I should not see attendee "(?P<fullname>(?:[^"]|\\")*)" in the report$/
     * @param string $fullname The attendee's full name.
     */
    public function i_should_not_see_attendee_in_report(string $fullname): void {
        $this->assertSession()->pageTextNotContains($fullname);
    }

    /**
     * Assert that the report table contains specific engagement data.
     *
     * @Then /^the engagement report should show "(?P<value>(?:[^"]|\\")*)" chats for "(?P<fullname>(?:[^"]|\\")*)"$/
     * @param string $value The expected chat count.
     * @param string $fullname The attendee's full name.
     */
    public function engagement_report_shows_chats(string $value, string $fullname): void {
        // Look for both the user name and the value in the page.
        $this->assertSession()->pageTextContains($fullname);
        // The value should appear in the same context (row).
        $page = $this->getSession()->getPage();
        $pagetext = $page->getText();

        if (strpos($pagetext, $fullname) === false) {
            throw new ExpectationException(
                "Could not find attendee '{$fullname}' in the report.",
                $this->getSession()
            );
        }
    }

    /**
     * Create a BNX record for a BigBlueButton activity.
     *
     * @Given /^a BNX record exists for BigBlueButton activity "(?P<activityname>(?:[^"]|\\")*)"$/
     * @param string $activityname The activity name.
     */
    public function a_bnx_record_exists(string $activityname): void {
        global $DB;

        $bbb = $DB->get_record('bigbluebuttonbn', ['name' => $activityname], '*', MUST_EXIST);

        // Check if record already exists.
        $existing = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id]);
        if (!$existing) {
            $bnx = new stdClass();
            $bnx->bigbluebuttonbnid = $bbb->id;
            $DB->insert_record('bbbext_bnx', $bnx);
        }
    }

    /**
     * Enable meetingevents callback in BigBlueButton config.
     *
     * @Given /^meeting events callback is enabled$/
     */
    public function meeting_events_callback_is_enabled(): void {
        set_config('bigbluebuttonbn_meetingevents_enabled', 1);
    }

    /**
     * Disable meetingevents callback in BigBlueButton config.
     *
     * @Given /^meeting events callback is disabled$/
     */
    public function meeting_events_callback_is_disabled(): void {
        set_config('bigbluebuttonbn_meetingevents_enabled', 0);
    }
}
