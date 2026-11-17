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
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\bbbext_bnx_datahub\logger::class)]
final class logger_test extends advanced_testcase {
    /**
     * Set up for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Sidecar-contract bootstrap (CC-2): the parent setting is no longer
        // auto-enabled by bbbext_bnx_datahub install (OL-3.2.1), so each test
        // that exercises BBB activity flows must seed it explicitly.
        set_config('bigbluebuttonbn_meetingevents_enabled', 1);
        set_config('enable_debug_logging', 1, 'bbbext_bnx_datahub');
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
        $data->data->duration = 3600;
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
     * Test the documented numeric callback duration is stored in seconds.
     */
    public function test_log_meeting_stores_numeric_duration(): void {
        global $DB;

        [$course, $bbb] = $this->create_bbb_with_bnx();
        $meetingdata = $this->create_meeting_data();
        $meetingdata->data->duration = 16;
        $meetingid = logger::log_meeting(instance::get_from_instanceid($bbb->id), $meetingdata);

        $this->assertEquals(16, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $meetingid]));
    }

    /**
     * Meeting names can be absent or exceed the database field length.
     */
    public function test_log_meeting_normalises_names_and_rejects_invalid_identifiers(): void {
        global $DB;

        [$course, $bbb] = $this->create_bbb_with_bnx();
        $instance = instance::get_from_instanceid($bbb->id);
        $data = $this->create_meeting_data();
        unset($data->data->metadata);
        $meetingid = logger::log_meeting($instance, $data);
        $this->assertSame('Test Meeting', $DB->get_field('bbbext_bnx_datahub_meeting', 'meetingname', ['id' => $meetingid]));

        $data = $this->create_meeting_data();
        $data->data->metadata->meeting_name = str_repeat('é', 300);
        $this->assertSame($meetingid, logger::log_meeting($instance, $data));
        $name = $DB->get_field('bbbext_bnx_datahub_meeting', 'meetingname', ['id' => $meetingid]);
        $this->assertSame(str_repeat('é', 255), $name);

        foreach (['meeting_id', 'internal_meeting_id'] as $field) {
            $data = $this->create_meeting_data();
            $data->{$field} = str_repeat('a', 256);
            $this->assertSame(0, logger::log_meeting($instance, $data));
            $this->assertDebuggingCalled(
                '[bbbext_bnx_datahub] logger::log_meeting Invalid meeting identifier.',
                DEBUG_DEVELOPER
            );
        }
        $this->assertSame(1, $DB->count_records('bbbext_bnx_datahub_meeting'));
    }

    /**
     * Only valid callback durations can be used as evidence for historical repairs.
     */
    public function test_parse_callback_duration_for_repair(): void {
        foreach (
            [
                [16, 16],
                ['3600', 3600],
                ['01:02:03', 3723],
                [0, 0],
                [null, null],
                ['invalid', null],
                [-1, null],
                [2147483648, null],
                [1.5, null],
            ] as [$value, $expected]
        ) {
            $this->assertSame($expected, logger::parse_callback_duration($value));
        }
    }

    /**
     * Historical repair uses only retained callbacks matching the meeting.
     */
    public function test_historical_duration_candidates(): void {
        $meeting = (object) [
            'internalmeetingid' => 'internal-123',
            'duration' => 999,
            'rawdata' => json_encode(['internal_meeting_id' => 'internal-123', 'data' => ['duration' => 16]]),
        ];
        $service = \bbbext_bnx_datahub\local\services\duration_repair_service::class;
        $this->assertSame(16, $service::expected_duration($meeting));
        $meeting->duration = 16;
        $this->assertSame(16, $service::expected_duration($meeting));
        $meeting->duration = 999;
        foreach (
            [
                null,
                '{invalid',
                json_encode(['internal_meeting_id' => 'other', 'data' => ['duration' => 16]]),
                json_encode(['internal_meeting_id' => 'internal-123', 'data' => ['duration' => 'invalid']]),
                json_encode(['internal_meeting_id' => 'internal-123', 'data' => []]),
            ] as $rawdata
        ) {
            $meeting->rawdata = $rawdata;
            $this->assertNull($service::expected_duration($meeting));
        }
    }

    /**
     * Preview and repair only records with matching retained callback evidence.
     */
    public function test_historical_duration_preview_and_repair(): void {
        global $DB;

        [, $bbb] = $this->create_bbb_with_bnx();
        $instance = instance::get_from_cmid($bbb->cmid);
        $firstdata = $this->create_meeting_data('external-first', 'internal-first');
        $firstdata->data->duration = 16;
        $firstid = logger::log_meeting($instance, $firstdata);
        $seconddata = $this->create_meeting_data('external-second', 'internal-second');
        $secondid = logger::log_meeting($instance, $seconddata);
        $thirddata = $this->create_meeting_data('external-third', 'internal-third');
        $thirdid = logger::log_meeting($instance, $thirddata);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'duration', 999, ['id' => $firstid]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'duration', 999, ['id' => $thirdid]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'rawdata', '{invalid', ['id' => $thirdid]);

        $service = \bbbext_bnx_datahub\local\services\duration_repair_service::class;
        $check = new \bbbext_bnx_datahub\check\duration_discrepancies();
        $this->assertSame(\core\check\result::WARNING, $check->get_result()->get_status());
        $results = [];
        $collect = static function (stdClass $meeting, ?int $expected, string $status) use (&$results): void {
            $results[] = [$meeting->id, $meeting->duration, $expected, $status];
        };
        $preview = $service::scan($collect);
        $this->assertSame(3, $preview['scanned']);
        $this->assertSame(1, $preview['ambiguous']);
        $this->assertSame(1, $preview['discrepancies']);
        $this->assertEquals([[$firstid, 999, 16, 'preview'], [$thirdid, 999, null, 'ambiguous']], $results);
        $this->assertEquals(999, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $firstid]));

        $results = [];
        $applied = $service::scan($collect, true);
        $this->assertSame(1, $applied['updated']);
        $this->assertEquals([[$firstid, 999, 16, 'updated'], [$thirdid, 999, null, 'ambiguous']], $results);
        $this->assertEquals(16, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $firstid]));
        $this->assertEquals(3600, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $secondid]));
        $this->assertEquals(999, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $thirdid]));
        $this->assertSame(0, $service::scan($collect, true)['updated']);
        $this->assertSame(\core\check\result::OK, $check->get_result()->get_status());
    }

    /**
     * The repair skips a candidate changed after it was scanned.
     */
    public function test_historical_duration_repair_skips_changed_records(): void {
        global $DB;

        [, $bbb] = $this->create_bbb_with_bnx();
        $instance = instance::get_from_cmid($bbb->cmid);
        $firstdata = $this->create_meeting_data('external-first', 'internal-first');
        $seconddata = $this->create_meeting_data('external-second', 'internal-second');
        $firstid = logger::log_meeting($instance, $firstdata);
        $secondid = logger::log_meeting($instance, $seconddata);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'duration', 999, ['id' => $firstid]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'duration', 999, ['id' => $secondid]);
        $results = [];
        $counts = \bbbext_bnx_datahub\local\services\duration_repair_service::scan(
            static function (stdClass $meeting, ?int $expected, string $status) use ($DB, $firstid, $secondid, &$results): void {
                $results[$meeting->id] = $status;
                if ((int) $meeting->id === $firstid) {
                    $DB->set_field('bbbext_bnx_datahub_meeting', 'rawdata', '{changed', ['id' => $secondid]);
                }
            },
            true
        );
        $this->assertSame(1, $counts['updated']);
        $this->assertSame(1, $counts['changed']);
        $this->assertSame('updated', $results[$firstid]);
        $this->assertSame('changed', $results[$secondid]);
        $this->assertEquals(999, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $secondid]));
    }

    /**
     * The administrator check finds discrepancies beyond the first scan batch.
     */
    public function test_historical_duration_scan_crosses_batches(): void {
        global $DB;

        [, , $bnx] = $this->create_bbb_with_bnx();
        $meetings = [];
        for ($number = 1; $number <= 101; $number++) {
            $internalid = 'batch-' . $number;
            $meetings[] = (object) [
                'bnxid' => $bnx->id,
                'externalmeetingid' => $internalid,
                'internalmeetingid' => $internalid,
                'meetingname' => 'Batch meeting',
                'duration' => $number === 101 ? 999 : 16,
                'rawdata' => json_encode(['internal_meeting_id' => $internalid, 'data' => ['duration' => 16]]),
            ];
        }
        $DB->insert_records('bbbext_bnx_datahub_meeting', $meetings);
        $counts = \bbbext_bnx_datahub\local\services\duration_repair_service::scan(null, false, true);
        $this->assertSame(101, $counts['scanned']);
        $this->assertSame(1, $counts['discrepancies']);
        $check = new \bbbext_bnx_datahub\check\duration_discrepancies();
        $this->assertSame(\core\check\result::WARNING, $check->get_result()->get_status());
        $checkclasses = array_map('get_class', \core\check\manager::get_status_checks());
        $this->assertContains(\bbbext_bnx_datahub\check\duration_discrepancies::class, $checkclasses);
    }

    /**
     * Test duration values and fallback timestamps.
     */
    public function test_log_meeting_duration_fallbacks(): void {
        global $DB;

        [$course, $bbb] = $this->create_bbb_with_bnx();
        $instance = instance::get_from_instanceid($bbb->id);
        $cases = [
            [0, 0],
            ['0', 0],
            ['3600', 3600],
            [2147483647, 2147483647],
            ['01:02:03', 3723],
            ['999999:59:59', 3600],
            [null, 3600],
            [-1, 3600],
            ['invalid', 3600],
            ['01:60:00', 3600],
            ['2147483648', 3600],
            [1.5, 3600],
        ];
        foreach ($cases as [$duration, $expected]) {
            $meetingdata = $this->create_meeting_data();
            $meetingdata->data->duration = $duration;
            $meetingid = logger::log_meeting($instance, $meetingdata);
            $this->assertEquals($expected, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $meetingid]));
        }

        $meetingdata = $this->create_meeting_data();
        unset($meetingdata->data->duration);
        $meetingid = logger::log_meeting($instance, $meetingdata);
        $this->assertEquals(3600, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $meetingid]));

        $meetingdata = $this->create_meeting_data();
        unset($meetingdata->data->duration);
        $meetingdata->data->start = 'invalid';
        $meetingid = logger::log_meeting($instance, $meetingdata);
        $this->assertEquals(0, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $meetingid]));

        $meetingdata = $this->create_meeting_data();
        unset($meetingdata->data->duration, $meetingdata->data->finish);
        $meetingid = logger::log_meeting($instance, $meetingdata);
        $this->assertEquals(0, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $meetingid]));

        $meetingdata = $this->create_meeting_data();
        unset($meetingdata->data->duration);
        $meetingdata->data->finish = '2026-01-01T09:00:00Z';
        $meetingid = logger::log_meeting($instance, $meetingdata);
        $this->assertEquals(0, $DB->get_field('bbbext_bnx_datahub_meeting', 'duration', ['id' => $meetingid]));
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

        // Expect the debugging message about missing BNX record.
        $this->assertDebuggingCalled(
            '[bbbext_bnx_datahub] logger::log_meeting No BNX record found for instance.',
            DEBUG_DEVELOPER
        );

        $this->assertEquals(0, $result);
    }

    /**
     * Test attendee logging rejects missing meeting records.
     */
    public function test_log_meeting_users_rejects_missing_meeting(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $attendee = $this->create_attendee_data($user->id);

        foreach ([0, -1, 987654321] as $meetingid) {
            $result = logger::log_meeting_users($meetingid, [$attendee, $attendee]);
            $this->assertSame(['ids' => [], 'skipped' => 2], $result);
            $this->assertSame(0, logger::log_meeting_user($meetingid, $attendee));
        }

        $this->assertSame(0, $DB->count_records('bbbext_bnx_datahub_meeting_user'));
    }

    /**
     * Duplicate attendees must not prevent other attendees from being logged.
     */
    public function test_log_meeting_users_skips_duplicate_ids(): void {
        global $DB;

        [$course, $bbb] = $this->create_bbb_with_bnx();
        $meetingid = logger::log_meeting(instance::get_from_instanceid($bbb->id), $this->create_meeting_data());
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $first = $this->create_attendee_data($user->id);
        $duplicate = $this->create_attendee_data($user->id);
        $duplicate->engagement->chats = 99;

        $result = logger::log_meeting_users($meetingid, [$first, $duplicate, $this->create_attendee_data($otheruser->id)]);

        $this->assertDebuggingCalled(
            '[bbbext_bnx_datahub] logger::log_meeting_users Skipping duplicate participant.',
            DEBUG_DEVELOPER
        );
        $this->assertCount(2, $result['ids']);
        $this->assertSame(1, $result['skipped']);
        $this->assertEquals(10, $DB->get_field('bbbext_bnx_datahub_meeting_user', 'engchats', [
            'meetingid' => $meetingid,
            'userid' => $user->id,
        ]));
    }

    /**
     * Malformed attendees are skipped while other attendees are retained.
     */
    public function test_log_meeting_users_skips_malformed_attendees(): void {
        global $DB;

        [$course, $bbb] = $this->create_bbb_with_bnx();
        $meetingid = logger::log_meeting(instance::get_from_instanceid($bbb->id), $this->create_meeting_data());
        $user = $this->getDataGenerator()->create_user();
        $invalid = $this->create_attendee_data($user->id);
        $invalid->engagement->chats = '2147483648';
        $badtime = $this->create_attendee_data($user->id);
        $badtime->joins = ['not a date'];
        $valid = $this->create_attendee_data($user->id);
        $valid->joins = ['2040-01-01T10:00:00Z'];

        $result = logger::log_meeting_users($meetingid, [$invalid, $badtime, 'invalid', $valid]);

        $this->assertDebuggingCalledCount(3);
        $this->assertSame(3, $result['skipped']);
        $this->assertCount(1, $result['ids']);
        $this->assertEquals(10, $DB->get_field('bbbext_bnx_datahub_meeting_user', 'engchats', [
            'meetingid' => $meetingid,
            'userid' => $user->id,
        ]));
        $this->assertEquals(strtotime('2040-01-01T10:00:00Z'), $DB->get_field('bbbext_bnx_datahub_meeting_user', 'timejoin', [
            'meetingid' => $meetingid,
            'userid' => $user->id,
        ]));
    }

    /**
     * Unknown attendee diagnostics do not include the user ID.
     */
    public function test_log_meeting_users_redacts_unknown_user(): void {
        [$course, $bbb] = $this->create_bbb_with_bnx();
        $meetingid = logger::log_meeting(instance::get_from_instanceid($bbb->id), $this->create_meeting_data());
        $result = logger::log_meeting_users($meetingid, [$this->create_attendee_data(2147483647)]);

        $this->assertDebuggingCalled(
            '[bbbext_bnx_datahub] logger::log_meeting_users Skipping unknown user.',
            DEBUG_DEVELOPER
        );
        $this->assertSame(['ids' => [], 'skipped' => 1], $result);
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

        $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid, 'userid' => $user->id]);
        $this->assertNotFalse($record);
        $this->assertEquals($user->id, $record->userid);
        $this->assertEquals('viewer', $record->attendeerole);
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

        $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid, 'userid' => $user->id]);
        $this->assertEquals('moderator', $record->attendeerole);
    }

    /**
     * Boolean and string moderator values are stored correctly on insert and update.
     *
     * @return void
     */
    public function test_log_meeting_user_normalises_moderator_values(): void {
        global $DB;

        [$course, $bbb] = $this->create_bbb_with_bnx();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        $meetingid = logger::log_meeting(instance::get_from_instanceid($bbb->id), $this->create_meeting_data());
        $attendee = $this->create_attendee_data($user->id);

        $cases = [
            [true, 'moderator'],
            [false, 'viewer'],
            ['true', 'moderator'],
            ['false', 'viewer'],
            [null, 'viewer'],
        ];
        foreach ($cases as [$value, $expected]) {
            $attendee->moderator = $value;
            logger::log_meeting_user($meetingid, $attendee);
            $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', [
                'meetingid' => $meetingid,
                'userid' => $user->id,
            ], '*', MUST_EXIST);
            $this->assertSame($expected, $record->attendeerole);
        }
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
        $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid, 'userid' => $user->id]);
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

        $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid, 'userid' => $user->id]);
        $rawdata = json_decode($record->rawdata);

        $this->assertIsObject($rawdata);
        $this->assertEquals($user->id, $rawdata->ext_user_id);
    }
}
