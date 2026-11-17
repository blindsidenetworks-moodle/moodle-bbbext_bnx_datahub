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
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
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
     * Activity reports respect group access while retaining complete session attendance.
     */
    public function test_activity_reports_respect_group_access(): void {
        global $DB, $PAGE;

        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $this->cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $modulecontext = \context_module::instance($this->cmid);
        $groupone = $generator->create_group(['courseid' => $course->id]);
        $grouptwo = $generator->create_group(['courseid' => $course->id]);
        $firstlearner = $generator->create_user(['firstname' => 'Firstgroup', 'lastname' => 'Learner']);
        $secondlearner = $generator->create_user(['firstname' => 'Secondgroup', 'lastname' => 'Learner']);
        $otherlearner = $generator->create_user(['firstname' => 'Othergroup', 'lastname' => 'Learner']);
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
        $systemcontext = \context_system::instance();
        assign_capability('bbbext/bnx_datahub:viewreports', CAP_ALLOW, $roleid, $systemcontext->id);
        role_assign($roleid, $restrictedviewer->id, $systemcontext->id);
        role_assign($roleid, $allgroupsviewer->id, $systemcontext->id);
        $instance = \mod_bigbluebuttonbn\instance::get_from_cmid($this->cmid);
        $firstmeeting = $this->insert_meeting(1000);
        $secondmeeting = $this->insert_meeting(2000);
        $unknownmeeting = $this->insert_meeting(3000);
        foreach ([$firstmeeting->id => $groupone->id, $secondmeeting->id => $grouptwo->id] as $meetingid => $groupid) {
            $DB->set_field(
                'bbbext_bnx_datahub_meeting',
                'externalmeetingid',
                $instance->get_meeting_id($groupid),
                ['id' => $meetingid]
            );
        }
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'First group session', ['id' => $firstmeeting->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'Second group session', ['id' => $secondmeeting->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'Unknown group session', ['id' => $unknownmeeting->id]);
        $this->insert_attendee($firstmeeting->id, $firstlearner->id);
        $this->insert_attendee($firstmeeting->id, $secondlearner->id);
        $this->insert_attendee($firstmeeting->id, 0);
        $this->insert_attendee($secondmeeting->id, $otherlearner->id);
        $this->insert_attendee($unknownmeeting->id, $otherlearner->id);
        $PAGE->set_url(new \moodle_url('/'));

        foreach ([SEPARATEGROUPS, VISIBLEGROUPS] as $groupmode) {
            $DB->set_field('course_modules', 'groupmode', $groupmode, ['id' => $this->cmid]);
            rebuild_course_cache($course->id, true);
            $currentcm = get_fast_modinfo($course)->get_cm($this->cmid);
            $this->assertSame($groupmode, (int) groups_get_activity_groupmode($currentcm));
            foreach ([[$restrictedviewer, false], [$allgroupsviewer, true]] as [$viewer, $accessallgroups]) {
                $this->setUser($viewer);
                \core_reportbuilder\manager::reset_caches();
                $this->assertTrue(has_capability('bbbext/bnx_datahub:viewreports', $modulecontext));
                $this->assertSame($accessallgroups, has_capability('moodle/site:accessallgroups', $modulecontext));
                $data = dashboard_service::get_by_cmid($this->cmid);
                $expectedcount = $accessallgroups ? 3 : ($groupmode === VISIBLEGROUPS ? 2 : 1);
                $this->assertSame($expectedcount, $data->meetingcount);
                $this->assertSame($expectedcount === 1 ? 2 : 3, $data->uniquelearnercount);
                $selected = dashboard_service::get_by_cmid($this->cmid, $firstmeeting->id);
                $this->assertCount(3, $selected->selectedlearners);
                if ($expectedcount === 1) {
                    $forgedselection = dashboard_service::get_by_cmid($this->cmid, $secondmeeting->id);
                    $this->assertSame($firstmeeting->id, $forgedselection->selectedmeeting->id);
                }
                foreach ([$modulecontext, $systemcontext] as $reportcontext) {
                    foreach (
                        [
                            \bbbext_bnx_datahub\reportbuilder\local\systemreports\attendance::class,
                            \bbbext_bnx_datahub\reportbuilder\local\systemreports\engagement::class,
                        ] as $reportclass
                    ) {
                        $PAGE = new \moodle_page();
                        $PAGE->set_url(new \moodle_url('/'));
                        $report = \core_reportbuilder\system_report_factory::create($reportclass, $reportcontext);
                        $content = $report->output();
                        $this->assertStringContainsString('Firstgroup', $content);
                        $this->assertStringContainsString('Secondgroup', $content);
                        $this->assertStringContainsString('First group session', $content);
                        if ($expectedcount >= 2) {
                            $this->assertStringContainsString('Second group session', $content);
                        } else {
                            $this->assertStringNotContainsString('Second group session', $content);
                        }
                        if ($accessallgroups) {
                            $this->assertStringContainsString('Unknown group session', $content);
                        } else {
                            $this->assertStringNotContainsString('Unknown group session', $content);
                        }
                    }
                }
            }
        }

        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $this->cmid]);
        groups_remove_member($groupone->id, $restrictedviewer->id);
        groups_add_member($grouptwo->id, $restrictedviewer->id);
        rebuild_course_cache($course->id, true);
        $this->setUser($restrictedviewer);
        \core_reportbuilder\manager::reset_caches();
        $data = dashboard_service::get_by_cmid($this->cmid);
        $this->assertSame(1, $data->meetingcount);
        $this->assertSame($secondmeeting->id, $data->selectedmeeting->id);
        foreach ([$modulecontext, $systemcontext] as $reportcontext) {
            foreach (
                [
                    \bbbext_bnx_datahub\reportbuilder\local\systemreports\attendance::class,
                    \bbbext_bnx_datahub\reportbuilder\local\systemreports\engagement::class,
                ] as $reportclass
            ) {
                $PAGE = new \moodle_page();
                $PAGE->set_url(new \moodle_url('/'));
                $report = \core_reportbuilder\system_report_factory::create($reportclass, $reportcontext);
                $content = $report->output();
                $this->assertStringNotContainsString('First group session', $content);
                $this->assertStringContainsString('Second group session', $content);
                $this->assertStringNotContainsString('Unknown group session', $content);
            }
        }
        $DB->set_field('course_modules', 'groupmode', NOGROUPS, ['id' => $this->cmid]);
        rebuild_course_cache($course->id, true);
        \core_reportbuilder\manager::reset_caches();
        $data = dashboard_service::get_by_cmid($this->cmid);
        $this->assertSame(3, $data->meetingcount);
        foreach (
            [
                \bbbext_bnx_datahub\reportbuilder\local\systemreports\attendance::class,
                \bbbext_bnx_datahub\reportbuilder\local\systemreports\engagement::class,
            ] as $reportclass
        ) {
            $PAGE = new \moodle_page();
            $PAGE->set_url(new \moodle_url('/'));
            $report = \core_reportbuilder\system_report_factory::create($reportclass, $systemcontext);
            $this->assertStringContainsString('Unknown group session', $report->output());
        }
    }

    /**
     * Grouping and exact meeting IDs constrain visibility, including deleted groups.
     */
    public function test_group_visibility_respects_grouping_and_exact_identifiers(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $this->cmid, 0, false, MUST_EXIST);
        $courseid = (int) $cm->course;
        $viewer = $generator->create_user();
        $generator->enrol_user($viewer->id, $courseid, 'student');
        $allowedgroup = $generator->create_group(['courseid' => $courseid]);
        $othergroup = $generator->create_group(['courseid' => $courseid]);
        $generator->create_group_member(['groupid' => $allowedgroup->id, 'userid' => $viewer->id]);
        $generator->create_group_member(['groupid' => $othergroup->id, 'userid' => $viewer->id]);
        $grouping = $generator->create_grouping(['courseid' => $courseid]);
        $generator->create_grouping_group(['groupid' => $allowedgroup->id, 'groupingid' => $grouping->id]);
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $this->cmid]);
        $DB->set_field('course_modules', 'groupingid', $grouping->id, ['id' => $this->cmid]);
        $instance = \mod_bigbluebuttonbn\instance::get_from_cmid($this->cmid);
        $allowedmeeting = $this->insert_meeting(1000);
        $othermeeting = $this->insert_meeting(2000);
        $malformedmeeting = $this->insert_meeting(3000);
        foreach (
            [
                $allowedmeeting->id => $instance->get_meeting_id($allowedgroup->id),
                $othermeeting->id => $instance->get_meeting_id($othergroup->id),
                $malformedmeeting->id => $instance->get_meeting_id($allowedgroup->id) . ' ',
            ] as $meetingid => $externalid
        ) {
            $DB->set_field('bbbext_bnx_datahub_meeting', 'externalmeetingid', $externalid, ['id' => $meetingid]);
        }
        rebuild_course_cache($courseid, true);
        $this->setUser($viewer);
        $this->assertFalse(has_capability('moodle/site:accessallgroups', \context_module::instance($this->cmid)));
        $this->assertSame(SEPARATEGROUPS, (int) groups_get_activity_groupmode(get_fast_modinfo($courseid)->get_cm($this->cmid)));
        [$visibilitysql, $visibilityparams] = meeting_service::get_report_visibility_sql('m', 'bbb', $this->cmid);
        $visibilityparams['meetingid'] = $malformedmeeting->id;
        $this->assertFalse($DB->record_exists_sql(
            "SELECT m.id FROM {bbbext_bnx_datahub_meeting} m
               JOIN {bbbext_bnx} bnx ON bnx.id = m.bnxid
               JOIN {bigbluebuttonbn} bbb ON bbb.id = bnx.bigbluebuttonbnid
              WHERE m.id = :meetingid AND $visibilitysql",
            $visibilityparams
        ));
        $data = dashboard_service::get_by_cmid($this->cmid);
        $this->assertSame(1, $data->meetingcount);
        $this->assertSame($allowedmeeting->id, $data->selectedmeeting->id);

        $DB->set_field('course_modules', 'groupmode', VISIBLEGROUPS, ['id' => $this->cmid]);
        rebuild_course_cache($courseid, true);
        $data = dashboard_service::get_by_cmid($this->cmid);
        $this->assertSame(1, $data->meetingcount);
        $this->assertSame($allowedmeeting->id, $data->selectedmeeting->id);

        groups_delete_group($allowedgroup);
        $data = dashboard_service::get_by_cmid($this->cmid);
        $this->assertFalse($data->hasdata);
    }

    /**
     * Exclusion actions cannot target sessions outside the viewer's groups.
     */
    public function test_exclusion_actions_respect_group_access(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $this->cmid, 0, false, MUST_EXIST);
        $viewer = $generator->create_user();
        $generator->enrol_user($viewer->id, $cm->course, 'student');
        $allowedgroup = $generator->create_group(['courseid' => $cm->course]);
        $othergroup = $generator->create_group(['courseid' => $cm->course]);
        $generator->create_group_member(['groupid' => $allowedgroup->id, 'userid' => $viewer->id]);
        $context = \context_module::instance($this->cmid);
        $roleid = create_role('Group exclusion manager', 'groupexclusionmanager', 'Group exclusion manager');
        assign_capability('bbbext/bnx_datahub:manageexclusions', CAP_ALLOW, $roleid, $context->id);
        role_assign($roleid, $viewer->id, $context->id);
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $this->cmid]);
        $instance = \mod_bigbluebuttonbn\instance::get_from_cmid($this->cmid);
        $allowedmeeting = $this->insert_meeting(1000);
        $othermeeting = $this->insert_meeting(2000);
        foreach ([$allowedmeeting->id => $allowedgroup->id, $othermeeting->id => $othergroup->id] as $meetingid => $groupid) {
            $DB->set_field(
                'bbbext_bnx_datahub_meeting',
                'externalmeetingid',
                $instance->get_meeting_id($groupid),
                ['id' => $meetingid]
            );
        }
        rebuild_course_cache($cm->course, true);
        $this->setUser($viewer);
        $this->assertTrue(exclusion_service::set_excluded($this->cmid, $allowedmeeting->id, true));
        try {
            exclusion_service::set_excluded($this->cmid, $othermeeting->id, true);
            $this->fail('A viewer must not change another group\'s session.');
        } catch (\moodle_exception $exception) {
            $this->assertSame('invalidrecord', $exception->errorcode);
        }
        $this->assertSame('0', $DB->get_field('bbbext_bnx_datahub_meeting', 'excluded', ['id' => $othermeeting->id]));
    }

    /**
     * Site-wide reports apply each activity's group access independently.
     */
    public function test_sitewide_reports_apply_group_access_per_activity(): void {
        global $DB, $PAGE;

        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $this->cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $viewer = $generator->create_user();
        $attendee = $generator->create_user();
        $generator->enrol_user($viewer->id, $course->id, 'student');
        $systemcontext = \context_system::instance();
        $roleid = create_role('Site report viewer', 'sitegroupreportviewer', 'Site report viewer');
        assign_capability('bbbext/bnx_datahub:viewreports', CAP_ALLOW, $roleid, $systemcontext->id);
        role_assign($roleid, $viewer->id, $systemcontext->id);
        $modulecontext = \context_module::instance($this->cmid);
        $allgroupsrole = create_role('Activity all groups', 'activityallgroups', 'Activity all groups');
        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $allgroupsrole, $modulecontext->id);
        role_assign($allgroupsrole, $viewer->id, $modulecontext->id);
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $this->cmid]);
        $firstmeeting = $this->insert_meeting(1000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'Allowed activity session', ['id' => $firstmeeting->id]);
        $this->insert_attendee($firstmeeting->id, $attendee->id);

        $othercourse = $generator->create_course();
        $otheractivity = $generator->create_module('bigbluebuttonbn', ['course' => $othercourse->id]);
        $othercm = get_coursemodule_from_instance('bigbluebuttonbn', $otheractivity->id, 0, false, MUST_EXIST);
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $othercm->id]);
        $othergroup = $generator->create_group(['courseid' => $othercourse->id]);
        $otherinstance = \mod_bigbluebuttonbn\instance::get_from_cmid($othercm->id);
        $this->bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $otheractivity->id], '*', MUST_EXIST);
        $othermeeting = $this->insert_meeting(2000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'Restricted activity session', ['id' => $othermeeting->id]);
        $DB->set_field(
            'bbbext_bnx_datahub_meeting',
            'externalmeetingid',
            $otherinstance->get_meeting_id($othergroup->id),
            ['id' => $othermeeting->id]
        );
        $this->insert_attendee($othermeeting->id, $attendee->id);
        rebuild_course_cache($course->id, true);
        rebuild_course_cache($othercourse->id, true);
        $this->setUser($viewer);
        $this->assertTrue(has_capability('moodle/site:accessallgroups', $modulecontext));
        $this->assertFalse(has_capability('moodle/site:accessallgroups', \context_module::instance($othercm->id)));

        foreach (
            [
                \bbbext_bnx_datahub\reportbuilder\local\systemreports\attendance::class,
                \bbbext_bnx_datahub\reportbuilder\local\systemreports\engagement::class,
            ] as $reportclass
        ) {
            $PAGE = new \moodle_page();
            $PAGE->set_url(new \moodle_url('/'));
            $report = \core_reportbuilder\system_report_factory::create($reportclass, $systemcontext);
            $content = $report->output();
            $this->assertStringContainsString('Allowed activity session', $content);
            $this->assertStringNotContainsString('Restricted activity session', $content);
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
     * Custom report audiences need the DataHub reporting capability to see activity data.
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
        $this->assertCount(0, $rows);
        $this->assertStringNotContainsString('User ' . $firstuser->id, $content);
        $this->assertStringNotContainsString('User ' . $seconduser->id, $content);
    }

    /**
     * Query the visible rows of a custom report as the current user.
     *
     * @param int $reportid Report ID.
     * @return array Formatted report rows.
     */
    private function get_custom_report_rows(int $reportid): array {
        global $PAGE;

        $PAGE = new \moodle_page();
        $PAGE->set_url(new \moodle_url('/'));
        $table = \core_reportbuilder\table\custom_report_table_view::create($reportid);
        $table->setup();
        $table->query_db(30, false);
        $rows = [];
        foreach ($table->rawdata as $row) {
            $rows[] = implode(' ', $table->format_row($row));
        }
        $table->close_recordset();
        return $rows;
    }

    /**
     * Custom datasources enforce activity permissions and group session visibility.
     */
    public function test_custom_datasources_filter_per_viewer(): void {
        global $DB;

        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_id('bigbluebuttonbn', $this->cmid, 0, false, MUST_EXIST);
        $DB->set_field('bigbluebuttonbn', 'name', 'Allowed room', ['id' => $cm->instance]);
        $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $this->cmid]);
        $groupone = $generator->create_group(['courseid' => $cm->course]);
        $grouptwo = $generator->create_group(['courseid' => $cm->course]);
        $viewerone = $generator->create_user();
        $viewertwo = $generator->create_user();
        $unprivileged = $generator->create_user();
        foreach ([$viewerone, $viewertwo, $unprivileged] as $viewer) {
            $generator->enrol_user($viewer->id, $cm->course, 'student');
        }
        $generator->create_group_member(['groupid' => $groupone->id, 'userid' => $viewerone->id]);
        $generator->create_group_member(['groupid' => $grouptwo->id, 'userid' => $viewertwo->id]);
        $generator->create_group_member(['groupid' => $groupone->id, 'userid' => $unprivileged->id]);
        $roleid = create_role('Custom data viewer', 'customdataviewer', 'Custom data viewer');
        $systemcontext = \context_system::instance();
        assign_capability('moodle/reportbuilder:view', CAP_ALLOW, $roleid, $systemcontext->id);
        foreach ([$viewerone, $viewertwo, $unprivileged] as $viewer) {
            role_assign($roleid, $viewer->id, $systemcontext->id);
        }
        $reportrole = create_role('Activity data viewer', 'activitydataviewer', 'Activity data viewer');
        $modulecontext = \context_module::instance($this->cmid);
        assign_capability('bbbext/bnx_datahub:viewreports', CAP_ALLOW, $reportrole, $modulecontext->id);
        role_assign($reportrole, $viewerone->id, $modulecontext->id);
        role_assign($reportrole, $viewertwo->id, $modulecontext->id);
        $prohibitrole = create_role('Prohibited data viewer', 'prohibiteddataviewer', 'Prohibited data viewer');
        assign_capability('bbbext/bnx_datahub:viewreports', CAP_PROHIBIT, $prohibitrole, $modulecontext->id);

        $instance = \mod_bigbluebuttonbn\instance::get_from_cmid($this->cmid);
        $firstmeeting = $this->insert_meeting(1000);
        $secondmeeting = $this->insert_meeting(2000);
        foreach ([$firstmeeting->id => $groupone->id, $secondmeeting->id => $grouptwo->id] as $meetingid => $groupid) {
            $DB->set_field(
                'bbbext_bnx_datahub_meeting',
                'externalmeetingid',
                $instance->get_meeting_id($groupid),
                ['id' => $meetingid]
            );
        }
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'First group session', ['id' => $firstmeeting->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'excluded', 1, ['id' => $firstmeeting->id]);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'Second group session', ['id' => $secondmeeting->id]);
        $this->insert_attendee($firstmeeting->id, $viewerone->id);
        $this->insert_attendee($secondmeeting->id, $viewertwo->id);

        $othercourse = $generator->create_course();
        $otheractivity = $generator->create_module('bigbluebuttonbn', ['course' => $othercourse->id, 'name' => 'Forbidden room']);
        $this->bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $otheractivity->id], '*', MUST_EXIST);
        $othermeeting = $this->insert_meeting(3000);
        $DB->set_field('bbbext_bnx_datahub_meeting', 'meetingname', 'Forbidden session', ['id' => $othermeeting->id]);
        $this->insert_attendee($othermeeting->id, $unprivileged->id);

        $emptyactivity = $generator->create_module('bigbluebuttonbn', ['course' => $cm->course, 'name' => 'Empty room']);
        $emptycm = get_coursemodule_from_instance('bigbluebuttonbn', $emptyactivity->id, 0, false, MUST_EXIST);
        $emptycontext = \context_module::instance($emptycm->id);
        assign_capability('bbbext/bnx_datahub:viewreports', CAP_ALLOW, $reportrole, $emptycontext->id);
        role_assign($reportrole, $viewerone->id, $emptycontext->id);
        role_assign($reportrole, $viewertwo->id, $emptycontext->id);
        rebuild_course_cache($cm->course, true);

        $reportgenerator = $generator->get_plugin_generator('core_reportbuilder');
        foreach (
            [
                \bbbext_bnx_datahub\reportbuilder\datasource\attendees::class => 'meeting:name',
                \bbbext_bnx_datahub\reportbuilder\datasource\engagement::class => 'meeting:name',
                \bbbext_bnx_datahub\reportbuilder\datasource\meetings::class => 'meeting:name',
                \bbbext_bnx_datahub\reportbuilder\datasource\rooms::class => 'room:name',
            ] as $sourceclass => $column
        ) {
            $this->setAdminUser();
            $report = $reportgenerator->create_report([
                'name' => 'DataHub custom report',
                'source' => $sourceclass,
                'default' => false,
            ]);
            $reportgenerator->create_column(['reportid' => $report->get('id'), 'uniqueidentifier' => $column]);
            $reportgenerator->create_audience([
                'reportid' => $report->get('id'),
                'classname' => \core_reportbuilder\reportbuilder\audience\manual::class,
                'configdata' => ['users' => [$viewerone->id, $viewertwo->id, $unprivileged->id]],
            ]);
            foreach ([$viewerone, $viewertwo, $unprivileged] as $viewer) {
                $this->setUser($viewer);
                $this->assertTrue(\core_reportbuilder\permission::can_view_report($report));
                $rows = $this->get_custom_report_rows($report->get('id'));
                $content = implode(' ', $rows);
                $this->assertStringNotContainsString('Forbidden', $content);
                if ($viewer->id === $unprivileged->id) {
                    $this->assertCount(0, $rows);
                } else if ($sourceclass === \bbbext_bnx_datahub\reportbuilder\datasource\rooms::class) {
                    $this->assertCount(2, $rows);
                    $this->assertStringContainsString('Allowed room', $content);
                    $this->assertStringContainsString('Empty room', $content);
                } else {
                    $this->assertCount(1, $rows);
                    $this->assertStringContainsString(
                        $viewer->id === $viewerone->id ? 'First group session' : 'Second group session',
                        $content
                    );
                }
            }

            $this->setAdminUser();
            role_assign($prohibitrole, $viewerone->id, $modulecontext->id);
            \core_reportbuilder\manager::reset_caches();
            $this->setUser($viewerone);
            $rows = $this->get_custom_report_rows($report->get('id'));
            if ($sourceclass === \bbbext_bnx_datahub\reportbuilder\datasource\rooms::class) {
                $this->assertCount(1, $rows);
                $this->assertStringContainsString('Empty room', implode(' ', $rows));
            } else {
                $this->assertCount(0, $rows);
            }
            $this->setAdminUser();
            role_unassign($prohibitrole, $viewerone->id, $modulecontext->id);
            \core_reportbuilder\manager::reset_caches();

            if ($sourceclass !== \bbbext_bnx_datahub\reportbuilder\datasource\rooms::class) {
                foreach ([VISIBLEGROUPS, NOGROUPS] as $groupmode) {
                    $DB->set_field('course_modules', 'groupmode', $groupmode, ['id' => $this->cmid]);
                    rebuild_course_cache($cm->course, true);
                    \core_reportbuilder\manager::reset_caches();
                    $this->setUser($viewerone);
                    $rows = $this->get_custom_report_rows($report->get('id'));
                    $this->assertCount(2, $rows);
                    $this->assertStringContainsString('First group session', implode(' ', $rows));
                    $this->assertStringContainsString('Second group session', implode(' ', $rows));
                }
                $this->setAdminUser();
                $DB->set_field('course_modules', 'groupmode', SEPARATEGROUPS, ['id' => $this->cmid]);
                rebuild_course_cache($cm->course, true);
                \core_reportbuilder\manager::reset_caches();
                $this->setUser($viewerone);
                $this->assertCount(1, $this->get_custom_report_rows($report->get('id')));
                $this->setAdminUser();
                groups_remove_member($groupone->id, $viewerone->id);
                $this->setUser($viewerone);
                $this->assertCount(0, $this->get_custom_report_rows($report->get('id')));
                $this->setAdminUser();
                groups_add_member($groupone->id, $viewerone->id);
                $this->setUser($viewerone);
                $this->assertCount(1, $this->get_custom_report_rows($report->get('id')));
            }
        }
    }

    /**
     * Scheduled sends must not deliver DataHub reports in any view-as mode.
     */
    public function test_custom_report_schedule_send_is_blocked(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $recipient = $generator->create_user();
        $viewasuser = $generator->create_user();
        $reportgenerator = $generator->get_plugin_generator('core_reportbuilder');
        $report = $reportgenerator->create_report([
            'name' => 'Scheduled DataHub report',
            'source' => \bbbext_bnx_datahub\reportbuilder\datasource\meetings::class,
        ]);
        $audience = $reportgenerator->create_audience([
            'reportid' => $report->get('id'),
            'classname' => \core_reportbuilder\reportbuilder\audience\manual::class,
            'configdata' => ['users' => [$recipient->id]],
        ]);
        $sink = $this->redirectEmails();
        $this->expectOutputRegex('/Sending schedule: Scheduled DataHub/');
        foreach (
            [
                \core_reportbuilder\local\models\schedule::REPORT_VIEWAS_CREATOR,
                \core_reportbuilder\local\models\schedule::REPORT_VIEWAS_RECIPIENT,
                $viewasuser->id,
            ] as $viewas
        ) {
            $this->setAdminUser();
            $schedule = $reportgenerator->create_schedule([
                'reportid' => $report->get('id'),
                'name' => 'Scheduled DataHub',
                'userviewas' => $viewas,
                'audiences' => json_encode([$audience->get_persistent()->get('id')]),
                'configdata' => json_encode(['subject' => 'Report', 'message' => ['text' => 'Report', 'format' => 1]]),
            ]);
            $task = new \core_reportbuilder\task\send_schedule();
            $task->set_custom_data(['reportid' => $report->get('id'), 'scheduleid' => $schedule->get('id')]);
            \core\task\manager::$runningtask = $task;
            try {
                $task->execute();
                $this->fail('Scheduled DataHub reports must not be sent.');
            } catch (\moodle_exception $exception) {
                $this->assertSame('scheduledreportsnotavailable', $exception->errorcode);
            } finally {
                \core\task\manager::$runningtask = null;
                $this->setAdminUser();
            }
        }
        $this->assertCount(0, $sink->get_messages());
        $sink->close();
    }

    /**
     * Scheduled DataHub reports must fail closed even when their datasource is cached.
     */
    public function test_custom_report_schedules_are_blocked(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_reportbuilder');
        foreach (
            [
                \bbbext_bnx_datahub\reportbuilder\datasource\attendees::class,
                \bbbext_bnx_datahub\reportbuilder\datasource\engagement::class,
                \bbbext_bnx_datahub\reportbuilder\datasource\meetings::class,
                \bbbext_bnx_datahub\reportbuilder\datasource\rooms::class,
            ] as $sourceclass
        ) {
            $report = $generator->create_report(['name' => 'Scheduled report', 'source' => $sourceclass]);
            $source = \core_reportbuilder\manager::get_report_from_persistent($report);
            $source->get_active_columns();
            foreach (
                [\core_reportbuilder\task\send_schedule::class, \core_reportbuilder\task\send_schedules::class] as $taskclass
            ) {
                \core\task\manager::$runningtask = new $taskclass();
                try {
                    \core_reportbuilder\table\custom_report_table_view::create($report->get('id'));
                    $this->fail('Scheduled DataHub reports must not be generated.');
                } catch (\moodle_exception $exception) {
                    $this->assertSame('scheduledreportsnotavailable', $exception->errorcode);
                } finally {
                    \core\task\manager::$runningtask = null;
                }
            }
            $source->get_active_columns();
        }
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
     * A report viewer without the write capability cannot change exclusion state.
     */
    public function test_exclusion_requires_capability(): void {
        global $DB;

        $meeting = $this->insert_meeting(1000);
        $viewer = $this->getDataGenerator()->create_user();
        $context = \context_module::instance($this->cmid);
        $roleid = create_role('Report viewer', 'datahubreportviewer', 'Report viewer');
        assign_capability('bbbext/bnx_datahub:viewreports', CAP_ALLOW, $roleid, $context->id);
        role_assign($roleid, $viewer->id, $context->id);
        $this->setUser($viewer);
        $this->assertTrue(has_capability('bbbext/bnx_datahub:viewreports', $context));
        $this->assertFalse(has_capability('bbbext/bnx_datahub:manageexclusions', $context));

        foreach ([true, false] as $excluded) {
            $initialstate = (int) !$excluded;
            $DB->set_field('bbbext_bnx_datahub_meeting', 'excluded', $initialstate, ['id' => $meeting->id]);
            try {
                exclusion_service::set_excluded($this->cmid, $meeting->id, $excluded);
                $this->fail('A report viewer must not change exclusion state.');
            } catch (\core\exception\required_capability_exception $exception) {
                $this->assertSame(
                    $initialstate,
                    (int) $DB->get_field('bbbext_bnx_datahub_meeting', 'excluded', ['id' => $meeting->id])
                );
            }
        }
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
