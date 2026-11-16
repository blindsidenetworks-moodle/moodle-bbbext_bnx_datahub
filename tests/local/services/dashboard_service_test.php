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
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\bbbext_bnx_datahub\local\services\dashboard_service::class)]
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
        update_capabilities('bbbext_bnx_datahub');
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
            'attendeescore' => 42,
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
        $this->assertSame($latestmeeting->id, $data->selectedmeeting->id);
        $this->assertSame(2, $data->selectedmetrics->learnercount);
        $this->assertSame(2100, $data->selectedmetrics->medianattendance);
        $this->assertSame(42, $data->selectedlearners[0]->score);
    }

    /**
     * Current activity reports expose all groups to viewers with the reporting capability.
     */
    public function test_activity_reports_currently_include_all_groups(): void {
        global $DB, $PAGE;

        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $this->cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);
        $modulecontext = \context_module::instance($this->cmid);
        $groupone = $generator->create_group(['courseid' => $course->id]);
        $grouptwo = $generator->create_group(['courseid' => $course->id]);
        $firstlearner = $generator->create_user(['firstname' => 'Firstgroup', 'lastname' => 'Learner']);
        $secondlearner = $generator->create_user(['firstname' => 'Secondgroup', 'lastname' => 'Learner']);
        $restrictedviewer = $generator->create_user();
        $allgroupsviewer = $generator->create_user();
        foreach ([$firstlearner, $secondlearner, $restrictedviewer] as $user) {
            $generator->enrol_user($user->id, $course->id, 'student');
        }
        $generator->enrol_user($allgroupsviewer->id, $course->id, 'editingteacher');
        $generator->create_group_member(['groupid' => $groupone->id, 'userid' => $firstlearner->id]);
        $generator->create_group_member(['groupid' => $groupone->id, 'userid' => $restrictedviewer->id]);
        $generator->create_group_member(['groupid' => $grouptwo->id, 'userid' => $secondlearner->id]);
        $roleid = create_role('Group report viewer', 'groupreportviewer', 'Group report viewer');
        assign_capability('bbbext/bnx_datahub:viewreports', CAP_ALLOW, $roleid, $coursecontext->id);
        role_assign($roleid, $restrictedviewer->id, $coursecontext->id);
        $meeting = $this->insert_meeting(1000);
        $this->insert_attendee($meeting->id, $firstlearner->id);
        $this->insert_attendee($meeting->id, $secondlearner->id);
        $PAGE->set_url(new \moodle_url('/'));

        foreach ([SEPARATEGROUPS, VISIBLEGROUPS] as $groupmode) {
            $DB->set_field('course_modules', 'groupmode', $groupmode, ['id' => $this->cmid]);
            rebuild_course_cache($course->id, true);
            $currentcm = get_fast_modinfo($course)->get_cm($this->cmid);
            $this->assertSame($groupmode, (int) groups_get_activity_groupmode($currentcm));
            foreach ([[$restrictedviewer, false], [$allgroupsviewer, true]] as [$viewer, $accessallgroups]) {
                $this->setUser($viewer);
                $this->assertTrue(has_capability('bbbext/bnx_datahub:viewreports', $modulecontext));
                $this->assertSame($accessallgroups, has_capability('moodle/site:accessallgroups', $modulecontext));
                $data = dashboard_service::get_by_cmid($this->cmid);
                $this->assertSame(2, $data->uniquelearnercount);
                $this->assertCount(2, $data->selectedlearners);
                foreach (
                    [
                        \bbbext_bnx_datahub\reportbuilder\local\systemreports\attendance::class,
                        \bbbext_bnx_datahub\reportbuilder\local\systemreports\engagement::class,
                    ] as $reportclass
                ) {
                    $report = \core_reportbuilder\system_report_factory::create($reportclass, $modulecontext);
                    $content = $report->output();
                    $this->assertStringContainsString('Firstgroup', $content);
                    $this->assertStringContainsString('Secondgroup', $content);
                }
            }
        }
    }

    /**
     * Built-in reports scope module views to the activity and system views to the site.
     */
    public function test_builtin_report_activity_and_system_scope(): void {
        global $DB, $PAGE;

        $this->setAdminUser();
        $firstmeeting = $this->insert_meeting(1000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'First activity session', ['id' => $firstmeeting->id]);
        $firstuser = $this->getDataGenerator()->create_user();
        $this->insert_attendee($firstmeeting->id, $firstuser->id);
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $this->bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $other->id], '*', MUST_EXIST);
        $secondmeeting = $this->insert_meeting(2000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'Second activity session', ['id' => $secondmeeting->id]);
        $seconduser = $this->getDataGenerator()->create_user();
        $this->insert_attendee($secondmeeting->id, $seconduser->id);
        $PAGE->set_url(new \moodle_url('/'));

        foreach ([\context_system::instance(), \context_module::instance($this->cmid)] as $context) {
            foreach (
                [
                    \bbbext_bnx_datahub\reportbuilder\local\systemreports\attendance::class,
                    \bbbext_bnx_datahub\reportbuilder\local\systemreports\engagement::class,
                ] as $reportclass
            ) {
                $report = \core_reportbuilder\system_report_factory::create($reportclass, $context);
                $content = $report->output();
                $this->assertStringContainsString('First activity session', $content);
                if ($context->contextlevel === CONTEXT_SYSTEM) {
                    $this->assertStringContainsString('Second activity session', $content);
                } else {
                    $this->assertStringNotContainsString('Second activity session', $content);
                }
            }
        }
    }

    /**
     * Custom report audiences can see cross-activity data without the DataHub reporting capability.
     */
    public function test_custom_report_datasource_access_and_audience(): void {
        global $DB, $PAGE;

        $this->setAdminUser();
        $firstmeeting = $this->insert_meeting(1000);
        $firstuser = $this->getDataGenerator()->create_user();
        $this->insert_attendee($firstmeeting->id, $firstuser->id);
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $this->bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $other->id], '*', MUST_EXIST);
        $secondmeeting = $this->insert_meeting(2000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'excluded', 1, ['id' => $secondmeeting->id]);
        $seconduser = $this->getDataGenerator()->create_user();
        $this->insert_attendee($secondmeeting->id, $seconduser->id);

        $editor = $this->getDataGenerator()->create_user();
        $recipient = $this->getDataGenerator()->create_user();
        $outsider = $this->getDataGenerator()->create_user();
        $blockedrecipient = $this->getDataGenerator()->create_user();
        $systemcontext = \context_system::instance();
        $editorrole = create_role('Report editor', 'datahubreporteditor', 'Report editor');
        assign_capability('moodle/reportbuilder:edit', CAP_ALLOW, $editorrole, $systemcontext->id);
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $editorrole, $systemcontext->id);
        role_assign($editorrole, $editor->id, $systemcontext->id);
        $viewerrole = create_role('Report viewer', 'datahubcustomviewer', 'Report viewer');
        assign_capability('moodle/reportbuilder:view', CAP_ALLOW, $viewerrole, $systemcontext->id);
        role_assign($viewerrole, $recipient->id, $systemcontext->id);
        role_assign($viewerrole, $outsider->id, $systemcontext->id);
        $blockedrole = create_role('Blocked report viewer', 'datahubblockedviewer', 'Blocked report viewer');
        assign_capability('moodle/reportbuilder:view', CAP_PROHIBIT, $blockedrole, $systemcontext->id);
        role_assign($blockedrole, $blockedrecipient->id, $systemcontext->id);
        $PAGE->set_url(new \moodle_url('/'));
        $this->setUser($editor);
        $this->assertFalse(has_capability('bbbext/bnx_datahub:viewreports', $systemcontext));
        $this->assertTrue(\core_reportbuilder\permission::can_create_report());
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');
        $report = $generator->create_report([
            'name' => 'DataHub attendees',
            'source' => \bbbext_bnx_datahub\reportbuilder\datasource\attendees::class,
        ]);
        $generator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => 'attendee:fullname']);
        $audience = $generator->create_audience([
            'reportid' => $report->get('id'),
            'classname' => \core_reportbuilder\reportbuilder\audience\manual::class,
            'configdata' => ['users' => [$recipient->id, $blockedrecipient->id]],
        ]);
        $this->assertTrue($audience->user_can_add());
        $this->assertTrue(\core_reportbuilder\permission::can_edit_report($report));
        $this->setUser($outsider);
        $this->assertFalse(\core_reportbuilder\permission::can_create_report());
        $this->assertFalse(\core_reportbuilder\permission::can_edit_report($report));
        $this->assertFalse(\core_reportbuilder\permission::can_view_report($report));
        $this->setUser($blockedrecipient);
        $this->assertFalse(\core_reportbuilder\permission::can_view_report($report));
        $this->setUser($recipient);
        $this->assertFalse(has_capability('bbbext/bnx_datahub:viewreports', $systemcontext));
        $this->assertFalse(has_capability('bbbext/bnx_datahub:viewreports', \context_module::instance($this->cmid)));
        $othercm = get_coursemodule_from_instance('bigbluebuttonbn', $other->id, 0, false, MUST_EXIST);
        $this->assertFalse(has_capability('bbbext/bnx_datahub:viewreports', \context_module::instance($othercm->id)));
        $this->assertTrue(\core_reportbuilder\permission::can_view_report($report));

        $table = \core_reportbuilder\table\custom_report_table_view::create($report->get('id'));
        $this->assertTrue($table->has_capability());
        $table->setup();
        $table->query_db(30, false);
        $rows = [];
        foreach ($table->rawdata as $row) {
            $rows[] = implode(' ', $table->format_row($row));
        }
        $table->close_recordset();
        $content = implode(' ', $rows);
        $this->assertCount(2, $rows);
        $this->assertStringContainsString('User ' . $firstuser->id, $content);
        $this->assertStringContainsString('User ' . $seconduser->id, $content);
    }

    /**
     * A meeting can be selected only when it belongs to the activity.
     */
    public function test_get_by_cmid_selects_activity_meeting(): void {
        $oldermeeting = $this->insert_meeting(1000);
        $latestmeeting = $this->insert_meeting(2000);
        $this->insert_attendee($oldermeeting->id, 10, 'viewer', 1200, 1);
        $this->insert_attendee($latestmeeting->id, 12, 'viewer', 2400, 0);

        $selected = dashboard_service::get_by_cmid($this->cmid, (int) $oldermeeting->id);
        $invalid = dashboard_service::get_by_cmid($this->cmid, 999999);

        $this->assertSame($oldermeeting->id, $selected->selectedmeeting->id);
        $this->assertSame('User 10', $selected->selectedlearners[0]->fullname);
        $this->assertSame($latestmeeting->id, $invalid->selectedmeeting->id);
    }

    /**
     * Test reversible exclusion and its effect on dashboard aggregation.
     */
    public function test_exclusion_and_restore(): void {
        $this->setAdminUser();
        $oldermeeting = $this->insert_meeting(1000);
        $latestmeeting = $this->insert_meeting(2000);
        $this->insert_attendee($oldermeeting->id, 10);
        $this->insert_attendee($latestmeeting->id, 12);
        $sink = $this->redirectEvents();

        $this->assertTrue(exclusion_service::set_excluded($this->cmid, $latestmeeting->id, true));
        $this->assertFalse(exclusion_service::set_excluded($this->cmid, $latestmeeting->id, true));
        $data = dashboard_service::get_by_cmid($this->cmid, $latestmeeting->id);
        $this->assertSame(1, $data->meetingcount);
        $this->assertSame(1, $data->excludedcount);
        $this->assertSame($oldermeeting->id, $data->selectedmeeting->id);
        $this->assertSame(2, $data->metrics->chats);
        $this->assertCount(2, meeting_service::get_all_by_cmid($this->cmid));

        exclusion_service::set_excluded($this->cmid, $oldermeeting->id, true);
        $data = dashboard_service::get_by_cmid($this->cmid);
        $this->assertFalse($data->hasdata);
        $this->assertSame(2, $data->excludedcount);
        $this->assertTrue(exclusion_service::set_excluded($this->cmid, $latestmeeting->id, false));
        $this->assertSame(1, dashboard_service::get_by_cmid($this->cmid)->meetingcount);
        $this->assertCount(3, $sink->get_events());
        $sink->close();
    }

    /**
     * Meetings from another activity cannot be changed through this activity.
     */
    public function test_exclusion_rejects_other_activity(): void {
        $this->setAdminUser();
        $meeting = $this->insert_meeting(1000);
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $other->id);
        $this->expectException(\moodle_exception::class);
        exclusion_service::set_excluded((int) $cm->id, $meeting->id, true);
    }

    /**
     * A user without the write capability cannot change exclusion state.
     */
    public function test_exclusion_requires_capability(): void {
        $meeting = $this->insert_meeting(1000);
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core\exception\required_capability_exception::class);
        exclusion_service::set_excluded($this->cmid, $meeting->id, true);
    }

    /**
     * Built-in reports omit excluded meetings in both system and module contexts.
     */
    public function test_builtin_reports_omit_excluded_meetings(): void {
        global $DB, $PAGE;

        $this->setAdminUser();
        $included = $this->insert_meeting(1000);
        $excluded = $this->insert_meeting(2000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'Included session', ['id' => $included->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'Excluded session', ['id' => $excluded->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'excluded', 1, ['id' => $excluded->id]);
        $this->insert_attendee($included->id, 10);
        $this->insert_attendee($excluded->id, 12);
        $PAGE->set_url(new \moodle_url('/'));

        foreach ([\context_system::instance(), \context_module::instance($this->cmid)] as $context) {
            foreach (
                [
                \bbbext_bnx_datahub\reportbuilder\local\systemreports\attendance::class,
                \bbbext_bnx_datahub\reportbuilder\local\systemreports\engagement::class,
                ] as $reportclass
            ) {
                $report = \core_reportbuilder\system_report_factory::create($reportclass, $context);
                $content = $report->output();
                $this->assertStringContainsString('Included session', $content);
                $this->assertStringNotContainsString('Excluded session', $content);
            }
        }
    }

    /**
     * Restricted backups must not include meeting analytics.
     */
    public function test_backup_without_users_omits_analytics(): void {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        $this->setAdminUser();
        $meeting = $this->insert_meeting(1000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'rawdata', 'private-backup-marker', ['id' => $meeting->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'metadata', 'private-metadata-marker', ['id' => $meeting->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'internalmeetingid', 'private-meeting-id', ['id' => $meeting->id]);
        $user = $this->getDataGenerator()->create_user();
        $this->insert_attendee($meeting->id, $user->id);
        $DB->set_field('bbbext_bnx_datahub_meeting_user', 'fullname', 'Private attendee name', ['meetingid' => $meeting->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting_user', 'rawdata', 'private-attendee-marker', ['meetingid' => $meeting->id]);
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $this->cmid, 0, false, MUST_EXIST);
        foreach (['without-users', 'anonymized', 'activity-without-users'] as $mode) {
            $controller = new \backup_controller(
                \backup::TYPE_1COURSE,
                $cm->course,
                \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL,
                $USER->id
            );
            $controller->get_plan()->get_setting('users')->set_value($mode !== 'without-users');
            if ($mode === 'anonymized') {
                $controller->get_plan()->get_setting('anonymize')->set_value(true);
            } else if ($mode === 'activity-without-users') {
                $controller->get_plan()->get_setting('bigbluebuttonbn_' . $cm->id . '_userinfo')->set_value(false);
            }
            $controller->execute_plan();
            $backupfile = $controller->get_results()['backup_destination'];
            $path = $CFG->dataroot . '/temp/backup/datahub-' . $mode . '-test';
            $backupfile->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $path);
            $controller->destroy();

            $files = glob($path . '/activities/bigbluebuttonbn_*/bigbluebuttonbn.xml');
            $this->assertCount(1, $files, $mode);
            $document = new \DOMDocument();
            $this->assertTrue($document->load($files[0]), $mode);
            $this->assertCount(0, (new \DOMXPath($document))->query('//bbbext_bnx_datahub_meetings/meeting'), $mode);
            $this->assertStringNotContainsString('private-backup-marker', file_get_contents($files[0]), $mode);
            $this->assertStringNotContainsString('private-metadata-marker', file_get_contents($files[0]), $mode);
            $this->assertStringNotContainsString('private-meeting-id', file_get_contents($files[0]), $mode);
            $this->assertStringNotContainsString('Private attendee name', file_get_contents($files[0]), $mode);
            $this->assertStringNotContainsString('private-attendee-marker', file_get_contents($files[0]), $mode);
        }
    }

    /**
     * Legacy archives respect restore settings and missing user mappings.
     */
    public function test_legacy_restore_respects_privacy_settings(): void {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $this->setAdminUser();
        $meeting = $this->insert_meeting(1000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'excluded', 1, ['id' => $meeting->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'metadata', 'private-metadata-marker', ['id' => $meeting->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'rawdata', 'private-backup-marker', ['id' => $meeting->id]);
        $user = $this->getDataGenerator()->create_user();
        $unmappeduser = $this->getDataGenerator()->create_user();
        $seconduser = $this->getDataGenerator()->create_user();
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $this->cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->getDataGenerator()->enrol_user($unmappeduser->id, $course->id);
        $this->getDataGenerator()->enrol_user($seconduser->id, $course->id);
        $this->insert_attendee($meeting->id, $user->id);
        $this->insert_attendee($meeting->id, $unmappeduser->id);
        $this->insert_attendee($meeting->id, $seconduser->id);
        $DB->set_field('bbbext_bnx_datahub_meeting_user', 'rawdata', 'private-attendee-marker', [
            'meetingid' => $meeting->id,
            'userid' => $user->id,
        ]);
        $DB->insert_record('bbbext_bnx_datahub_meeting_user', (object) [
            'meetingid' => $meeting->id,
            'userid' => null,
            'fullname' => 'Private guest name',
            'attendeerole' => 'viewer',
        ]);

        $controller = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $controller->get_plan()->get_setting('users')->set_value(true);
        $controller->execute_plan();
        $backupfile = $controller->get_results()['backup_destination'];
        $controller->destroy();

        foreach (['fully-mapped', 'unmapped', 'without-users', 'activity-without-users', 'anonymized'] as $mode) {
            $directory = 'datahub-legacy-' . $mode;
            $path = $CFG->dataroot . '/temp/backup/' . $directory;
            $backupfile->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $path);
            $files = glob($path . '/activities/bigbluebuttonbn_*/bigbluebuttonbn.xml');
            $this->assertCount(1, $files);
            $document = new \DOMDocument();
            $this->assertTrue($document->load($files[0]));
            $xpath = new \DOMXPath($document);
            $this->assertCount(1, $xpath->query('//bbbext_bnx_datahub_meetings/meeting'));
            $this->assertStringContainsString('private-backup-marker', file_get_contents($files[0]));
            $this->assertStringContainsString('private-metadata-marker', file_get_contents($files[0]));
            $this->assertStringContainsString('private-attendee-marker', file_get_contents($files[0]));
            $this->assertStringContainsString('Private guest name', file_get_contents($files[0]));

            if ($mode === 'fully-mapped') {
                $nodes = $xpath->query('//meeting_user[userid!="' . $user->id . '" and userid!="' . $seconduser->id . '"]');
                $this->assertCount(2, $nodes);
                foreach (iterator_to_array($nodes) as $node) {
                    $node->parentNode->removeChild($node);
                }
                $document->save($files[0]);
            } else if ($mode === 'unmapped') {
                $nodes = $xpath->query('//meeting_user[userid="' . $unmappeduser->id . '"]/userid');
                $this->assertCount(1, $nodes);
                $nodes->item(0)->nodeValue = '987654321';
                $document->save($files[0]);
            } else if ($mode === 'anonymized') {
                $backupinfo = new \DOMDocument();
                $this->assertTrue($backupinfo->load($path . '/moodle_backup.xml'));
                $settings = (new \DOMXPath($backupinfo))->query('//setting[name="anonymize"]/value');
                $this->assertCount(1, $settings);
                $settings->item(0)->nodeValue = '1';
                $backupinfo->save($path . '/moodle_backup.xml');
            }

            $newcourseid = \restore_dbops::create_new_course(
                $course->fullname,
                $course->shortname . '_' . $mode,
                $course->category
            );
            $restore = new \restore_controller(
                $directory,
                $newcourseid,
                \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL,
                $USER->id,
                \backup::TARGET_NEW_COURSE
            );
            if ($mode === 'without-users') {
                $restore->get_plan()->get_setting('users')->set_value(false);
            } else if ($mode === 'activity-without-users') {
                $restore->get_plan()->get_setting('bigbluebuttonbn_' . $cm->id . '_userinfo')->set_value(false);
            }
            $this->assertTrue($restore->execute_precheck());
            $restore->execute_plan();
            $restore->destroy();
            $restoredcms = array_filter(
                get_fast_modinfo($newcourseid)->get_cms(),
                static fn($candidate) => $candidate->modname === 'bigbluebuttonbn'
            );
            $this->assertCount(1, $restoredcms);
            $restoredcm = reset($restoredcms);
            $meetings = meeting_service::get_all_by_cmid($restoredcm->id);
            if ($mode !== 'fully-mapped' && $mode !== 'unmapped') {
                $this->assertCount(0, $meetings, $mode);
                continue;
            }
            $this->assertCount(1, $meetings);
            $restoredmeeting = reset($meetings);
            $this->assertSame(1, (int) $restoredmeeting->excluded);
            $record = $DB->get_record('bbbext_bnx_datahub_meeting', ['id' => $restoredmeeting->id], '*', MUST_EXIST);
            $this->assertSame($mode === 'fully-mapped' ? 'private-metadata-marker' : null, $record->metadata);
            $this->assertSame($mode === 'fully-mapped' ? 'private-backup-marker' : null, $record->rawdata);
            $attendees = $DB->get_records('bbbext_bnx_datahub_meeting_user', ['meetingid' => $restoredmeeting->id]);
            $this->assertCount(2, $attendees);
            $attendeesbyuser = [];
            foreach ($attendees as $attendee) {
                $attendeesbyuser[$attendee->userid] = $attendee;
            }
            $this->assertArrayHasKey($user->id, $attendeesbyuser);
            $this->assertArrayHasKey($seconduser->id, $attendeesbyuser);
            $this->assertSame('User ' . $user->id, $attendeesbyuser[$user->id]->fullname);
            $this->assertSame($mode === 'fully-mapped' ? 'private-attendee-marker' : null, $attendeesbyuser[$user->id]->rawdata);
            $this->assertSame($mode === 'fully-mapped' ? '{}' : null, $attendeesbyuser[$seconduser->id]->rawdata);
        }
    }

    /**
     * Exclusion state survives a course backup and restore.
     */
    public function test_exclusion_survives_backup_restore(): void {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $this->setAdminUser();
        unset_config('disabled', 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx_datahub');
        $meeting = $this->insert_meeting(1000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'excluded', 1, ['id' => $meeting->id]);
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $this->cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        $controller = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $controller->execute_plan();
        $backupfile = $controller->get_results()['backup_destination'];
        $backupfile->extract_to_pathname(
            get_file_packer('application/vnd.moodle.backup'),
            $CFG->dataroot . '/temp/backup/datahub-exclusion-test'
        );
        $controller->destroy();

        $newcourseid = \restore_dbops::create_new_course($course->fullname, $course->shortname . '_copy', $course->category);
        $restore = new \restore_controller(
            'datahub-exclusion-test',
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($restore->execute_precheck());
        $restore->execute_plan();
        $restore->destroy();
        $restoredcm = null;
        foreach (get_fast_modinfo($newcourseid)->get_cms() as $candidate) {
            if ($candidate->modname === 'bigbluebuttonbn') {
                $restoredcm = $candidate;
            }
        }
        $this->assertNotNull($restoredcm);
        $restored = meeting_service::get_all_by_cmid($restoredcm->id);
        $this->assertCount(1, $restored);
        $this->assertSame(1, (int) $restored[0]->excluded);

        $oldpath = $CFG->dataroot . '/temp/backup/datahub-old-exclusion-test';
        $backupfile->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $oldpath);
        $files = glob($oldpath . '/activities/bigbluebuttonbn_*/bigbluebuttonbn.xml');
        $this->assertCount(1, $files);
        $document = new \DOMDocument();
        $this->assertTrue($document->load($files[0]));
        $nodes = (new \DOMXPath($document))->query('//bbbext_bnx_datahub_meetings/meeting/excluded');
        $this->assertCount(1, $nodes);
        $nodes->item(0)->parentNode->removeChild($nodes->item(0));
        $document->save($files[0]);

        $oldcourseid = \restore_dbops::create_new_course($course->fullname, $course->shortname . '_old', $course->category);
        $oldrestore = new \restore_controller(
            'datahub-old-exclusion-test',
            $oldcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($oldrestore->execute_precheck());
        $oldrestore->execute_plan();
        $oldrestore->destroy();
        $oldcm = null;
        foreach (get_fast_modinfo($oldcourseid)->get_cms() as $candidate) {
            if ($candidate->modname === 'bigbluebuttonbn') {
                $oldcm = $candidate;
            }
        }
        $this->assertNotNull($oldcm);
        $oldrestored = meeting_service::get_all_by_cmid($oldcm->id);
        $this->assertCount(1, $oldrestored);
        $this->assertSame(0, (int) $oldrestored[0]->excluded);
    }
}
