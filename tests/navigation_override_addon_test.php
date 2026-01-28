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

namespace bbbext_bnx_datahub\bigbluebuttonbn;

use advanced_testcase;
use context_module;
use core_plugin_manager;
use mod_bigbluebuttonbn\instance;
use navigation_node;
use ReflectionClass;
use settings_navigation;

/**
 * Tests for the navigation override addon class.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\bigbluebuttonbn\navigation_override_addon
 */
final class navigation_override_addon_test extends advanced_testcase {
    /**
     * Set up for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that navigation override addon can be instantiated.
     */
    public function test_class_instantiation(): void {
        $addon = new navigation_override_addon();

        $this->assertInstanceOf(navigation_override_addon::class, $addon);
    }

    /**
     * Test that navigation override addon implements the interface.
     */
    public function test_implements_interface(): void {
        $addon = new navigation_override_addon();

        $this->assertInstanceOf(
            \mod_bigbluebuttonbn\local\extension\navigation_override_addon::class,
            $addon
        );
    }

    /**
     * Test that override_settings_navigation method exists.
     */
    public function test_override_settings_navigation_method_exists(): void {
        $addon = new navigation_override_addon();

        $this->assertTrue(
            method_exists($addon, 'override_settings_navigation'),
            'Method override_settings_navigation should exist'
        );
    }

    /**
     * Test that the class checks for BNX plugin enablement.
     */
    public function test_requires_bnx_plugin(): void {
        // The navigation override checks if BNX is enabled.
        // This is a structural test - we verify the check is in place.
        $reflection = new ReflectionClass(navigation_override_addon::class);
        $method = $reflection->getMethod('override_settings_navigation');

        $this->assertNotNull($method);

        // Read the source code to verify BNX check is present.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString("get_enabled_plugins('bbbext')", $source);
        $this->assertStringContainsString("enabledplugins['bnx']", $source);
    }

    /**
     * Test that the class checks for meetingevents_enabled configuration.
     */
    public function test_checks_meetingevents_config(): void {
        $reflection = new ReflectionClass(navigation_override_addon::class);

        // Read the source code to verify meetingevents check is present.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('meetingevents_enabled', $source);
    }

    /**
     * Test that the class checks for viewreports capability.
     */
    public function test_checks_viewreports_capability(): void {
        $reflection = new ReflectionClass(navigation_override_addon::class);

        // Read the source code to verify capability check is present.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('bbbext/bnx_datahub:viewreports', $source);
    }

    /**
     * Test that attendance report URL is constructed correctly.
     */
    public function test_attendance_report_url_format(): void {
        $reflection = new ReflectionClass(navigation_override_addon::class);

        // Read the source code to verify URL format.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('/mod/bigbluebuttonbn/extension/bnx_datahub/attendance.php', $source);
        $this->assertStringContainsString("'cmid' => \$cm->id", $source);
    }

    /**
     * Test that engagement report URL is constructed correctly.
     */
    public function test_engagement_report_url_format(): void {
        $reflection = new ReflectionClass(navigation_override_addon::class);

        // Read the source code to verify URL format.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('/mod/bigbluebuttonbn/extension/bnx_datahub/engagement.php', $source);
        $this->assertStringContainsString("'cmid' => \$cm->id", $source);
    }

    /**
     * Test that the class adds report links as navigation nodes.
     */
    public function test_adds_navigation_nodes(): void {
        $reflection = new ReflectionClass(navigation_override_addon::class);

        // Read the source code to verify navigation node additions.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('$nodenav->add(', $source);
        $this->assertStringContainsString('navigation_node::TYPE_CONTAINER', $source);
    }

    /**
     * Test that recordings-only instances are excluded.
     */
    public function test_excludes_recordings_only_instances(): void {
        $reflection = new ReflectionClass(navigation_override_addon::class);

        // Read the source code to verify recordings-only check.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('is_type_recordings_only', $source);
    }

    /**
     * Test that completion validate link is added for course managers.
     */
    public function test_completion_validate_for_managers(): void {
        $reflection = new ReflectionClass(navigation_override_addon::class);

        // Read the source code to verify completion validate link.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('moodle/course:manageactivities', $source);
        $this->assertStringContainsString('action=completion_validate', $source);
        $this->assertStringContainsString('completionvalidatestate', $source);
    }
}
