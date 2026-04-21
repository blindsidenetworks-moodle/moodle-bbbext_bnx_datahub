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
 * Tests for the meeting_service class.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\local\services\meeting_service
 */
final class meeting_service_test extends advanced_testcase {
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
            'name' => 'Service Test Meeting',
        ]);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $this->bbb->id);
        $this->cmid = (int) $cm->id;

        // Ensure the bnx record exists (created automatically by bbbext_bnx on module creation).
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
     * Insert a meeting record linked to the current bnx record.
     *
     * @param int $timestart Unix timestamp for meeting start.
     * @param string $internalmeetingid Internal meeting ID.
     * @return stdClass The meeting record with id set.
     */
    private function insert_meeting(int $timestart, string $internalmeetingid = ''): stdClass {
        global $DB;

        if ($internalmeetingid === '') {
            $internalmeetingid = 'int-' . uniqid();
        }

        $meeting = (object) [
            'bnxid' => $this->bnx->id,
            'externalmeetingid' => 'ext-' . uniqid(),
            'internalmeetingid' => $internalmeetingid,
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
     * Test get_by_cmid returns null when no meetings exist.
     */
    public function test_get_by_cmid_returns_null_when_no_meetings(): void {
        $result = meeting_service::get_by_cmid($this->cmid);
        $this->assertNull($result);
    }

    /**
     * Test get_by_cmid returns the most recent meeting.
     */
    public function test_get_by_cmid_returns_most_recent(): void {
        $older = $this->insert_meeting(1000);
        $newer = $this->insert_meeting(2000);

        $result = meeting_service::get_by_cmid($this->cmid);
        $this->assertNotNull($result);
        $this->assertEquals($newer->id, $result->id);
    }

    /**
     * Test get_by_cmid returns null for a non-existent cmid.
     */
    public function test_get_by_cmid_returns_null_for_invalid_cmid(): void {
        $this->insert_meeting(1000);

        $result = meeting_service::get_by_cmid(999999);
        $this->assertNull($result);
    }

    /**
     * Test get_all_by_cmid returns empty array when no meetings exist.
     */
    public function test_get_all_by_cmid_returns_empty_when_no_meetings(): void {
        $result = meeting_service::get_all_by_cmid($this->cmid);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get_all_by_cmid returns all meetings ordered by timestart descending.
     */
    public function test_get_all_by_cmid_returns_all_ordered_desc(): void {
        $oldest = $this->insert_meeting(1000);
        $middle = $this->insert_meeting(2000);
        $newest = $this->insert_meeting(3000);

        $result = meeting_service::get_all_by_cmid($this->cmid);
        $this->assertCount(3, $result);
        $this->assertEquals($newest->id, $result[0]->id);
        $this->assertEquals($middle->id, $result[1]->id);
        $this->assertEquals($oldest->id, $result[2]->id);
    }

    /**
     * Test get_by_internal_meeting_id returns the correct meeting.
     */
    public function test_get_by_internal_meeting_id_found(): void {
        $meeting = $this->insert_meeting(1000, 'known-internal-id');

        $result = meeting_service::get_by_internal_meeting_id('known-internal-id');
        $this->assertNotNull($result);
        $this->assertEquals($meeting->id, $result->id);
    }

    /**
     * Test get_by_internal_meeting_id returns null for unknown ID.
     */
    public function test_get_by_internal_meeting_id_not_found(): void {
        $result = meeting_service::get_by_internal_meeting_id('nonexistent-id');
        $this->assertNull($result);
    }
}
