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

namespace bbbext_bnx_datahub;

use advanced_testcase;
use context_module;
use mod_bigbluebuttonbn\instance;
use stdClass;

/**
 * Tests for the logger class.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\logger
 */
final class logger_test extends advanced_testcase {
    /**
     * Set up for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Create a BigBlueButton activity with BNX record.
     *
     * @return array Array containing course, bbb instance, and bnx record.
     */
    private function create_bbb_with_bnx(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', [
            'course' => $course->id,
            'name' => 'Test Meeting',
        ]);

        // Get the BNX record that was automatically created by the bbbext_bnx plugin.
        $bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id], '*', MUST_EXIST);

        return [$course, $bbb, $bnx];
    }

    /**
     * Create sample meeting data object.
     *
     * @param string $meetingid External meeting ID.
     * @param string $internalmeetingid Internal meeting ID.
     * @return stdClass Meeting data object.
     */
    private function create_meeting_data(
        string $meetingid = 'ext-meeting-123',
        string $internalmeetingid = 'int-meeting-456'
    ): stdClass {
        $data = new stdClass();
        $data->meeting_id = $meetingid;
        $data->internal_meeting_id = $internalmeetingid;
        $data->data = new stdClass();
        $data->data->metadata = new stdClass();
        $data->data->metadata->meeting_name = 'Test Meeting';
        $data->data->start = '2026-01-01T10:00:00Z';
        $data->data->finish = '2026-01-01T11:00:00Z';
        $data->data->duration = '01:00:00';
        $data->data->attendees = [];

        return $data;
    }

    /**
     * Create sample attendee data object.
     *
     * @param int $userid User ID.
     * @return stdClass Attendee data object.
     */
    private function create_attendee_data(int $userid): stdClass {
        $attendee = new stdClass();
        $attendee->ext_user_id = $userid;
        $attendee->moderator = 'false';
        $attendee->joins = ['2026-01-01T10:05:00Z'];
        $attendee->leaves = ['2026-01-01T10:55:00Z'];
        $attendee->duration = 3000;
        $attendee->engagement = new stdClass();
        $attendee->engagement->chats = 10;
        $attendee->engagement->talks = 5;
        $attendee->engagement->raisehand = 2;
        $attendee->engagement->emojis = 3;
        $attendee->engagement->poll_votes = 4;
        $attendee->engagement->talk_time = 120;

        return $attendee;
    }

    /**
     * Test log_meeting creates a new meeting record.
     */
    public function test_log_meeting_creates_record(): void {
        global $DB;

        [$course, $bbb, $bnx] = $this->create_bbb_with_bnx();

        $instance = instance::get_from_instanceid($bbb->id);
        $meetingdata = $this->create_meeting_data();

        $meetingid = logger::log_meeting($instance, $meetingdata);

        $this->assertGreaterThan(0, $meetingid);

        $record = $DB->get_record('bbbext_bnx_datahub_meeting', ['id' => $meetingid]);
        $this->assertNotFalse($record);
        $this->assertEquals($bnx->id, $record->bnxid);
        $this->assertEquals('ext-meeting-123', $record->externalmeetingid);
        $this->assertEquals('int-meeting-456', $record->internalmeetingid);
        $this->assertEquals('Test Meeting', $record->meetingname);
    }

    /**
     * Test log_meeting updates existing record with same internal meeting ID.
     */
    public function test_log_meeting_updates_existing_record(): void {
        global $DB;

        [$course, $bbb, $bnx] = $this->create_bbb_with_bnx();

        $instance = instance::get_from_instanceid($bbb->id);
        $meetingdata = $this->create_meeting_data();

        // Create first record.
        $meetingid1 = logger::log_meeting($instance, $meetingdata);

        // Update meeting data.
        $meetingdata->data->metadata->meeting_name = 'Updated Meeting';

        // Log again with same internal meeting ID.
        $meetingid2 = logger::log_meeting($instance, $meetingdata);

        // Should return same ID (updated, not inserted).
        $this->assertEquals($meetingid1, $meetingid2);

        // Verify only one record exists.
        $count = $DB->count_records('bbbext_bnx_datahub_meeting', ['bnxid' => $bnx->id]);
        $this->assertEquals(1, $count);

        // Verify name was updated.
        $record = $DB->get_record('bbbext_bnx_datahub_meeting', ['id' => $meetingid1]);
        $this->assertEquals('Updated Meeting', $record->meetingname);
    }

    /**
     * Test log_meeting returns 0 when no BNX record exists.
     */
    public function test_log_meeting_returns_zero_without_bnx(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', [
            'course' => $course->id,
            'name' => 'Test Meeting Without BNX',
        ]);

        // Delete the auto-created BNX record to simulate missing BNX.
        $DB->delete_records('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id]);

        $instance = instance::get_from_instanceid($bbb->id);
        $meetingdata = $this->create_meeting_data();

        $result = logger::log_meeting($instance, $meetingdata);

        $this->assertEquals(0, $result);
    }

    /**
     * Test log_meeting_user creates a new attendee record.
     */
    public function test_log_meeting_user_creates_record(): void {
        global $DB;

        [$course, $bbb, $bnx] = $this->create_bbb_with_bnx();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        // Create meeting first.
        $instance = instance::get_from_instanceid($bbb->id);
        $meetingdata = $this->create_meeting_data();
        $meetingid = logger::log_meeting($instance, $meetingdata);

        // Log attendee.
        $attendeedata = $this->create_attendee_data($user->id);
        $result = logger::log_meeting_user($meetingid, $attendeedata);

        $this->assertGreaterThan(0, $result);

        $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid]);
        $this->assertNotFalse($record);
        $this->assertEquals($user->id, $record->userid);
        $this->assertEquals('viewer', $record->role);
        $this->assertEquals(10, $record->engchats);
        $this->assertEquals(5, $record->engtalks);
        $this->assertEquals(2, $record->engraisehand);
        $this->assertEquals(3, $record->engemojis);
        $this->assertEquals(4, $record->engpollvotes);
        $this->assertEquals(120, $record->engtalktime);
    }

    /**
     * Test log_meeting_user creates moderator role correctly.
     */
    public function test_log_meeting_user_creates_moderator_role(): void {
        global $DB;

        [$course, $bbb, $bnx] = $this->create_bbb_with_bnx();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');

        $instance = instance::get_from_instanceid($bbb->id);
        $meetingdata = $this->create_meeting_data();
        $meetingid = logger::log_meeting($instance, $meetingdata);

        $attendeedata = $this->create_attendee_data($user->id);
        $attendeedata->moderator = 'true';

        logger::log_meeting_user($meetingid, $attendeedata);

        $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid]);
        $this->assertEquals('moderator', $record->role);
    }

    /**
     * Test log_meeting_user updates existing record for same user.
     */
    public function test_log_meeting_user_updates_existing_record(): void {
        global $DB;

        [$course, $bbb, $bnx] = $this->create_bbb_with_bnx();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $instance = instance::get_from_instanceid($bbb->id);
        $meetingdata = $this->create_meeting_data();
        $meetingid = logger::log_meeting($instance, $meetingdata);

        // Log first attendance.
        $attendeedata = $this->create_attendee_data($user->id);
        $attendeedata->engagement->chats = 5;
        logger::log_meeting_user($meetingid, $attendeedata);

        // Log updated attendance.
        $attendeedata->engagement->chats = 15;
        logger::log_meeting_user($meetingid, $attendeedata);

        // Verify only one record exists.
        $count = $DB->count_records('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid]);
        $this->assertEquals(1, $count);

        // Verify chats count was updated.
        $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid]);
        $this->assertEquals(15, $record->engchats);
    }

    /**
     * Test multiple attendees can be logged for same meeting.
     */
    public function test_log_multiple_attendees(): void {
        global $DB;

        [$course, $bbb, $bnx] = $this->create_bbb_with_bnx();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, 'editingteacher');

        $instance = instance::get_from_instanceid($bbb->id);
        $meetingdata = $this->create_meeting_data();
        $meetingid = logger::log_meeting($instance, $meetingdata);

        // Log all attendees.
        logger::log_meeting_user($meetingid, $this->create_attendee_data($user1->id));
        logger::log_meeting_user($meetingid, $this->create_attendee_data($user2->id));

        $moderatordata = $this->create_attendee_data($user3->id);
        $moderatordata->moderator = 'true';
        logger::log_meeting_user($meetingid, $moderatordata);

        // Verify three records exist.
        $count = $DB->count_records('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid]);
        $this->assertEquals(3, $count);
    }

    /**
     * Test meeting metadata is stored as JSON.
     */
    public function test_meeting_metadata_stored_as_json(): void {
        global $DB;

        [$course, $bbb, $bnx] = $this->create_bbb_with_bnx();

        $instance = instance::get_from_instanceid($bbb->id);
        $meetingdata = $this->create_meeting_data();
        $meetingdata->data->metadata->custom_field = 'custom_value';

        $meetingid = logger::log_meeting($instance, $meetingdata);

        $record = $DB->get_record('bbbext_bnx_datahub_meeting', ['id' => $meetingid]);
        $metadata = json_decode($record->metadata);

        $this->assertIsObject($metadata);
        $this->assertEquals('Test Meeting', $metadata->meeting_name);
        $this->assertEquals('custom_value', $metadata->custom_field);
    }

    /**
     * Test attendee rawdata is stored as JSON.
     */
    public function test_attendee_rawdata_stored_as_json(): void {
        global $DB;

        [$course, $bbb, $bnx] = $this->create_bbb_with_bnx();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $instance = instance::get_from_instanceid($bbb->id);
        $meetingdata = $this->create_meeting_data();
        $meetingid = logger::log_meeting($instance, $meetingdata);

        $attendeedata = $this->create_attendee_data($user->id);
        logger::log_meeting_user($meetingid, $attendeedata);

        $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid]);
        $rawdata = json_decode($record->rawdata);

        $this->assertIsObject($rawdata);
        $this->assertEquals($user->id, $rawdata->ext_user_id);
    }
}
