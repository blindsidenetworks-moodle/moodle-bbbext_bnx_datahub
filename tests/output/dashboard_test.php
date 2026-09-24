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

namespace bbbext_bnx_datahub\output;

use advanced_testcase;

/**
 * Tests for the dashboard output component.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\output\dashboard
 */
final class dashboard_test extends advanced_testcase {
    /**
     * Empty dashboard data renders its empty-state context and detail links.
     */
    public function test_export_empty_dashboard(): void {
        global $OUTPUT;

        $dashboard = new dashboard((object) [
            'hasdata' => false,
        ], 42);
        $data = $dashboard->export_for_template($OUTPUT);

        $this->assertFalse($data->hasdata);
        $this->assertObjectHasProperty('emptytitle', $data);
        $this->assertStringContainsString('attendance.php?cmid=42', $data->attendanceurl);
        $this->assertStringContainsString('engagement.php?cmid=42', $data->engagementurl);
    }

    /**
     * Populated dashboard data renders all visual summary sections.
     */
    public function test_export_populated_dashboard(): void {
        global $OUTPUT;

        $dashboard = new dashboard((object) [
            'hasdata' => true,
            'meetingcount' => 2,
            'uniquelearnercount' => 3,
            'medianattendance' => 1800,
            'pollparticipantcount' => 2,
            'metrics' => (object) [
                'chats' => 8,
                'talks' => 5,
                'raisedhands' => 3,
                'emojis' => 1,
                'pollvotes' => 4,
                'talktime' => 120,
            ],
            'sessions' => [
                (object) ['label' => '1 Jan 2026', 'learnercount' => 2],
                (object) ['label' => '2 Jan 2026', 'learnercount' => 3],
            ],
            'latestlearners' => [
                (object) [
                    'fullname' => 'Ada Lovelace',
                    'chats' => 4,
                    'talks' => 2,
                    'raisedhands' => 1,
                    'pollvotes' => 2,
                ],
            ],
        ], 42);
        $data = $dashboard->export_for_template($OUTPUT);

        $this->assertTrue($data->hasdata);
        $this->assertCount(4, $data->metrics);
        $this->assertNotEmpty($data->attendancechart);
        $this->assertNotEmpty($data->engagementchart);
        $this->assertNotEmpty($data->learnerchart);
    }
}
