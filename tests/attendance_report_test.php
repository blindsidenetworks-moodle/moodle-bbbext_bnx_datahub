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

namespace bbbext_bnx_datahub\reportbuilder\local\systemreports;

use advanced_testcase;
use context_course;
use context_module;
use context_system;
use core_reportbuilder\exception\report_access_exception;

/**
 * Tests for the attendance system report.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\reportbuilder\local\systemreports\attendance
 */
final class attendance_report_test extends advanced_testcase {
    /**
     * Set up for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Sidecar-contract bootstrap (CC-2).
        set_config('bigbluebuttonbn_meetingevents_enabled', 1);
        $this->setAdminUser();
    }

    /**
     * Test that attendance report can be instantiated.
     */
    public function test_report_instantiation(): void {
        $report = \core_reportbuilder\system_report_factory::create(
            attendance::class,
            context_system::instance()
        );

        $this->assertInstanceOf(attendance::class, $report);
        [$where, $params] = $report->get_base_condition();
        $this->assertStringContainsString('.excluded', $where);
        $this->assertContains(0, $params);
    }

    /**
     * Test that attendance report has expected columns.
     */
    public function test_report_columns(): void {
        $report = \core_reportbuilder\system_report_factory::create(
            attendance::class,
            context_system::instance()
        );

        $columns = $report->get_active_columns();

        $this->assertNotEmpty($columns);

        // Check for expected columns from the report.
        $columnidentifiers = array_map(function ($col) {
            return $col->get_unique_identifier();
        }, $columns);

        $this->assertContains('course:coursefullnamewithlink', $columnidentifiers);
        $this->assertContains('room:id', $columnidentifiers);
        $this->assertContains('room:name', $columnidentifiers);
        $this->assertContains('meeting:id', $columnidentifiers);
        $this->assertContains('meeting:name', $columnidentifiers);
        $this->assertContains('meeting:timestart', $columnidentifiers);
        $this->assertContains('user:fullnamewithpicturelink', $columnidentifiers);
        $this->assertContains('attendee:role', $columnidentifiers);
        $this->assertContains('attendee:timejoin', $columnidentifiers);
        $this->assertContains('attendee:timeleave', $columnidentifiers);
        $this->assertContains('attendee:elapsedtime', $columnidentifiers);
    }

    /**
     * Test that attendance report has expected filters.
     */
    public function test_report_filters(): void {
        $report = \core_reportbuilder\system_report_factory::create(
            attendance::class,
            context_system::instance()
        );

        $filters = $report->get_active_filters();

        $this->assertNotEmpty($filters);

        $filteridentifiers = array_map(function ($filter) {
            return $filter->get_unique_identifier();
        }, $filters);

        $this->assertContains('course:fullname', $filteridentifiers);
        $this->assertContains('room:id', $filteridentifiers);
        $this->assertContains('room:name', $filteridentifiers);
        $this->assertContains('meeting:id', $filteridentifiers);
        $this->assertContains('meeting:name', $filteridentifiers);
        $this->assertContains('meeting:timestart', $filteridentifiers);
        $this->assertContains('attendee:fullname', $filteridentifiers);
        $this->assertContains('attendee:role', $filteridentifiers);
    }

    /**
     * Test that attendance report is downloadable.
     */
    public function test_report_downloadable(): void {
        $report = \core_reportbuilder\system_report_factory::create(
            attendance::class,
            context_system::instance()
        );

        $this->assertTrue($report->is_downloadable());
    }

    /**
     * Test attendance report with no data returns empty result.
     */
    public function test_report_empty_data(): void {
        global $PAGE;

        $report = \core_reportbuilder\system_report_factory::create(
            attendance::class,
            context_system::instance()
        );

        // Set page URL to avoid debugging notice when rendering.
        $PAGE->set_url(new \moodle_url('/'));

        // With no data, the report should still work.
        $content = $report->output();
        $this->assertIsString($content);
    }

    /**
     * Test that a user with the report capability can view the report.
     */
    public function test_can_view_with_capability(): void {
        [$context, $user] = $this->create_report_context_and_user(true);
        $this->setUser($user);

        $report = \core_reportbuilder\system_report_factory::create(
            attendance::class,
            $context
        );

        $reflection = new \ReflectionClass($report);
        $method = $reflection->getMethod('can_view');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($report));
    }

    /**
     * Test that a user without the report capability cannot view the report.
     */
    public function test_can_view_without_capability(): void {
        [$context, $user] = $this->create_report_context_and_user(false);
        $this->setUser($user);

        $this->expectException(report_access_exception::class);
        \core_reportbuilder\system_report_factory::create(
            attendance::class,
            $context
        );
    }

    /**
     * Test attendance report with module context parameter.
     */
    public function test_report_with_bigbluebuttonbnid_parameter(): void {
        $generator = $this->getDataGenerator();

        // Create a course and BigBlueButton activity.
        $course = $generator->create_course();
        $bbb = $generator->create_module('bigbluebuttonbn', ['course' => $course->id, 'name' => 'Test BBB']);

        // Create report with bigbluebuttonbnid parameter.
        $report = \core_reportbuilder\system_report_factory::create(
            attendance::class,
            context_system::instance(),
            '',
            '',
            0,
            ['bigbluebuttonbnid' => $bbb->id]
        );

        $this->assertInstanceOf(attendance::class, $report);
        [$where, $params] = $report->get_base_condition();
        $this->assertStringContainsString('bbbextbnx.bigbluebuttonbnid', $where);
        $this->assertContains((int) $bbb->id, $params);
    }

    /**
     * Create a module context and test user for report access tests.
     *
     * @param bool $allow Whether to grant the DataHub reports capability.
     * @return array{0: context_module, 1: \stdClass}
     */
    private function create_report_context_and_user(bool $allow): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $bbb = $generator->create_module('bigbluebuttonbn', ['course' => $course->id, 'name' => 'Test BBB']);
        $cm = get_coursemodule_from_instance('bigbluebuttonbn', $bbb->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        $user = $generator->create_user();

        if ($allow) {
            $coursecontext = context_course::instance($course->id);
            $roleid = create_role('DataHub report viewer', 'datahubreportviewer', 'DataHub report viewer');
            assign_capability('bbbext/bnx_datahub:viewreports', CAP_ALLOW, $roleid, $coursecontext->id);
            role_assign($roleid, $user->id, $coursecontext->id);
        }

        return [$context, $user];
    }
}
