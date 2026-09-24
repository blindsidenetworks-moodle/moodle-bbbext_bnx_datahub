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
 * Tests for the dashboard_service class.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\local\services\dashboard_service
 */
final class dashboard_service_test extends advanced_testcase {
    /** @var stdClass The bbbext_bnx record. */
    private stdClass $bnx;

    /** @var int The BigBlueButton course module ID. */
    private int $cmid;

    /**
     * Set up a BigBlueButton activity with its BNX record.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();
        set_config('bigbluebuttonbn_meetingevents_enabled', 1);

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bbb->id);
        $this->cmid = (int) $cm->id;
        $this->bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id]);
        if (!$this->bnx) {
            $this->bnx = (object) [
                'bigbluebuttonbnid' => $bbb->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ];
            $this->bnx->id = $DB->insert_record('bbbext_bnx', $this->bnx);
        }
    }

    /**
     * Insert a meeting record.
     *
     * @param int $timestart Meeting start time.
     * @return stdClass Inserted meeting.
     */
    private function insert_meeting(int $timestart): stdClass {
        global $DB;

        $meeting = (object) [
            'bnxid' => $this->bnx->id,
            'externalmeetingid' => 'ext-' . uniqid(),
            'internalmeetingid' => 'int-' . uniqid(),
            'meetingname' => 'Dashboard meeting',
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
     * Insert an attendee row.
     *
     * @param int $meetingid Meeting ID.
     * @param int $userid Moodle user ID.
     * @param string $role DataHub attendee role.
     * @param int $elapsedtime Attendance duration in seconds.
     * @param int $pollvotes Poll vote count.
     * @return void
     */
    private function insert_attendee(
        int $meetingid,
        int $userid,
        string $role = 'viewer',
        int $elapsedtime = 1800,
        int $pollvotes = 0
    ): void {
        global $DB;

        $DB->insert_record('bbbext_bnx_datahub_meeting_user', (object) [
            'meetingid' => $meetingid,
            'userid' => $userid,
            'fullname' => 'User ' . $userid,
            'attendeerole' => $role,
            'timejoin' => 100,
            'timeleave' => 100 + $elapsedtime,
            'elapsedtime' => $elapsedtime,
            'attendeescore' => 0,
            'engchats' => 2,
            'engtalks' => 3,
            'engraisehand' => 1,
            'engemojis' => 4,
            'engpollvotes' => $pollvotes,
            'engtalktime' => 90,
            'rawdata' => '{}',
        ]);
    }

    /**
     * Empty activities return the expected dashboard shape.
     */
    public function test_get_by_cmid_returns_empty_data_without_meetings(): void {
        $data = dashboard_service::get_by_cmid($this->cmid);

        $this->assertFalse($data->hasdata);
        $this->assertSame(0, $data->meetingcount);
        $this->assertEmpty($data->sessions);
        $this->assertEmpty($data->latestlearners);
    }

    /**
     * Learner aggregates exclude moderators and use all captured meetings.
     */
    public function test_get_by_cmid_aggregates_learners_and_metrics(): void {
        $oldermeeting = $this->insert_meeting(1000);
        $latestmeeting = $this->insert_meeting(2000);
        $this->insert_attendee($oldermeeting->id, 10, 'viewer', 1200, 1);
        $this->insert_attendee($oldermeeting->id, 11, 'moderator', 3600, 4);
        $this->insert_attendee($latestmeeting->id, 10, 'viewer', 1800, 2);
        $this->insert_attendee($latestmeeting->id, 12, 'viewer', 2400, 0);

        $data = dashboard_service::get_by_cmid($this->cmid);

        $this->assertTrue($data->hasdata);
        $this->assertSame(2, $data->meetingcount);
        $this->assertSame(2, $data->uniquelearnercount);
        $this->assertSame(1800, $data->medianattendance);
        $this->assertSame(1, $data->pollparticipantcount);
        $this->assertSame(6, $data->metrics->chats);
        $this->assertSame(9, $data->metrics->talks);
        $this->assertSame(3, $data->metrics->raisedhands);
        $this->assertSame(3, $data->metrics->pollvotes);
        $this->assertCount(2, $data->sessions);
        $this->assertSame($oldermeeting->id, $data->sessions[0]->id);
        $this->assertSame($latestmeeting->id, $data->sessions[1]->id);
        $this->assertCount(2, $data->latestlearners);
        $this->assertSame('User 10', $data->latestlearners[0]->fullname);
    }
}
