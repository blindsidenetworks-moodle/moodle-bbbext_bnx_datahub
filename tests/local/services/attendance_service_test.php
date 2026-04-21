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

namespace bbbext_bnx_datahub\local\services;

use advanced_testcase;
use stdClass;

/**
 * Tests for the attendance_service class.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\local\services\attendance_service
 */
final class attendance_service_test extends advanced_testcase {
    /** @var stdClass Course object. */
    private stdClass $course;

    /** @var stdClass The BBB course module record. */
    private stdClass $bbb;

    /** @var stdClass The bbbext_bnx record. */
    private stdClass $bnx;

    /** @var int The course module ID. */
    private int $cmid;

    /**
     * Set up for each test.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $this->bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', [
            'course' => $this->course->id,
            'name' => 'Attendance Test Meeting',
        ]);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $this->bbb->id);
        $this->cmid = (int) $cm->id;

        // Ensure the bnx record exists.
        $this->bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $this->bbb->id]);
        if (!$this->bnx) {
            $this->bnx = (object) [
                'bigbluebuttonbnid' => $this->bbb->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $this->bnx->id = $DB->insert_record('bbbext_bnx', $this->bnx);
        }
    }

    /**
     * Insert a meeting record.
     *
     * @param int $timestart Unix timestamp.
     * @return stdClass The meeting record with id set.
     */
    private function insert_meeting(int $timestart = 1000): stdClass {
        global $DB;

        $meeting = (object) [
            'bnxid' => $this->bnx->id,
            'externalmeetingid' => 'ext-' . uniqid(),
            'internalmeetingid' => 'int-' . uniqid(),
            'meetingname' => 'Test Meeting',
            'timestart' => $timestart,
            'timeend' => $timestart + 3600,
            'duration' => 3600,
            'metadata' => '{}',
            'rawdata' => '{}',
        ];
        $meeting->id = $DB->insert_record('bbbext_bnx_datahub_meeting', $meeting);

        return $meeting;
    }

    /**
     * Insert an attendance (meeting_user) record.
     *
     * @param int $meetingid The meeting ID.
     * @param int $userid The Moodle user ID.
     * @param int $elapsedtime Elapsed time in seconds.
     * @return stdClass The meeting_user record with id set.
     */
    private function insert_attendance(int $meetingid, int $userid, int $elapsedtime = 3000): stdClass {
        global $DB;

        $record = (object) [
            'meetingid' => $meetingid,
            'userid' => $userid,
            'fullname' => 'User ' . $userid,
            'role' => 'viewer',
            'timejoin' => time() - $elapsedtime,
            'timeleave' => time(),
            'elapsedtime' => $elapsedtime,
            'score' => 80,
            'engchats' => 10,
            'engtalks' => 5,
            'engraisehand' => 2,
            'engemojis' => 3,
            'engpollvotes' => 4,
            'engtalktime' => 120,
            'rawdata' => '{}',
        ];
        $record->id = $DB->insert_record('bbbext_bnx_datahub_meeting_user', $record);

        return $record;
    }

    /**
     * Test get_attendance_by_cmid returns empty when no meetings exist.
     */
    public function test_get_attendance_by_cmid_empty_no_meetings(): void {
        $result = attendance_service::get_attendance_by_cmid($this->cmid);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get_attendance_by_cmid returns empty when meeting has no attendees.
     */
    public function test_get_attendance_by_cmid_empty_no_attendees(): void {
        $this->insert_meeting(1000);

        $result = attendance_service::get_attendance_by_cmid($this->cmid);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get_attendance_by_cmid returns attendees from the most recent meeting.
     */
    public function test_get_attendance_by_cmid_returns_most_recent_meeting_attendees(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $older = $this->insert_meeting(1000);
        $this->insert_attendance((int) $older->id, (int) $user1->id, 1800);

        $newer = $this->insert_meeting(2000);
        $this->insert_attendance((int) $newer->id, (int) $user2->id, 3000);

        $result = attendance_service::get_attendance_by_cmid($this->cmid);
        $this->assertCount(1, $result);
        $this->assertEquals($user2->id, $result[0]->userid);
    }

    /**
     * Test get_attendance_by_cmid returns all engagement fields.
     */
    public function test_get_attendance_by_cmid_includes_engagement_fields(): void {
        $user = $this->getDataGenerator()->create_user();

        $meeting = $this->insert_meeting(1000);
        $this->insert_attendance((int) $meeting->id, (int) $user->id, 3000);

        $result = attendance_service::get_attendance_by_cmid($this->cmid);
        $this->assertCount(1, $result);

        $record = $result[0];
        $this->assertObjectHasProperty('elapsedtime', $record);
        $this->assertObjectHasProperty('engchats', $record);
        $this->assertObjectHasProperty('engtalks', $record);
        $this->assertObjectHasProperty('engraisehand', $record);
        $this->assertObjectHasProperty('engemojis', $record);
        $this->assertObjectHasProperty('engpollvotes', $record);
        $this->assertObjectHasProperty('engtalktime', $record);
        $this->assertEquals(3000, $record->elapsedtime);
        $this->assertEquals(10, $record->engchats);
    }

    /**
     * Test get_attendance_by_meeting returns empty for nonexistent meeting.
     */
    public function test_get_attendance_by_meeting_empty(): void {
        $result = attendance_service::get_attendance_by_meeting(999999);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get_attendance_by_meeting returns records for the given meeting.
     */
    public function test_get_attendance_by_meeting_returns_records(): void {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $meeting = $this->insert_meeting(1000);
        $this->insert_attendance((int) $meeting->id, (int) $user1->id);
        $this->insert_attendance((int) $meeting->id, (int) $user2->id);

        $result = attendance_service::get_attendance_by_meeting((int) $meeting->id);
        $this->assertCount(2, $result);
    }

    /**
     * Test get_attendance_by_user returns null when no meeting exists.
     */
    public function test_get_attendance_by_user_null_no_meeting(): void {
        $user = $this->getDataGenerator()->create_user();

        $result = attendance_service::get_attendance_by_user((int) $user->id, $this->cmid);
        $this->assertNull($result);
    }

    /**
     * Test get_attendance_by_user returns the correct record.
     */
    public function test_get_attendance_by_user_found(): void {
        $user = $this->getDataGenerator()->create_user();

        $meeting = $this->insert_meeting(1000);
        $attendance = $this->insert_attendance((int) $meeting->id, (int) $user->id, 2500);

        $result = attendance_service::get_attendance_by_user((int) $user->id, $this->cmid);
        $this->assertNotNull($result);
        $this->assertEquals($attendance->id, $result->id);
        $this->assertEquals(2500, $result->elapsedtime);
    }
}
