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
 * Tests for the navigation append addon class.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\bigbluebuttonbn\navigation_append_addon
 */
final class navigation_append_addon_test extends advanced_testcase {
    /**
     * Set up for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that navigation append addon can be instantiated.
     */
    public function test_class_instantiation(): void {
        $addon = new navigation_append_addon();

        $this->assertInstanceOf(navigation_append_addon::class, $addon);
    }

    /**
     * Test that navigation append addon implements the interface.
     */
    public function test_implements_interface(): void {
        $addon = new navigation_append_addon();

        $this->assertInstanceOf(
            \mod_bigbluebuttonbn\local\extension\navigation_append_addon::class,
            $addon
        );
    }

    /**
     * Test that append_settings_navigation method exists.
     */
    public function test_append_settings_navigation_method_exists(): void {
        $addon = new navigation_append_addon();

        $this->assertTrue(
            method_exists($addon, 'append_settings_navigation'),
            'Method append_settings_navigation should exist'
        );
    }

    /**
     * Test that the class checks for BNX plugin enablement.
     */
    public function test_requires_bnx_plugin(): void {
        // The navigation append checks if BNX is enabled.
        // This is a structural test - we verify the check is in place.
        $reflection = new ReflectionClass(navigation_append_addon::class);
        $method = $reflection->getMethod('append_settings_navigation');

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
        $reflection = new ReflectionClass(navigation_append_addon::class);

        // Read the source code to verify meetingevents check is present.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('meetingevents_enabled', $source);
    }

    /**
     * Test that the class checks for viewreports capability.
     */
    public function test_checks_viewreports_capability(): void {
        $reflection = new ReflectionClass(navigation_append_addon::class);

        // Read the source code to verify capability check is present.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('bbbext/bnx_datahub:viewreports', $source);
    }

    /**
     * Test that the dashboard URL is constructed correctly.
     */
    public function test_dashboard_url_format(): void {
        $reflection = new ReflectionClass(navigation_append_addon::class);

        // Read the source code to verify the dashboard URL format.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('/mod/bigbluebuttonbn/extension/bnx_datahub/dashboard.php', $source);
        $this->assertStringContainsString("'cmid' => \$cm->id", $source);
        $this->assertStringNotContainsString('/mod/bigbluebuttonbn/extension/bnx_datahub/attendance.php', $source);
        $this->assertStringNotContainsString('/mod/bigbluebuttonbn/extension/bnx_datahub/engagement.php', $source);
    }

    /**
     * Test that the class adds the dashboard as a navigation node.
     */
    public function test_adds_navigation_nodes(): void {
        $reflection = new ReflectionClass(navigation_append_addon::class);

        // Read the source code to verify navigation node additions.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('$nodenav->add(', $source);
        $this->assertStringContainsString('navigation_node::TYPE_CONTAINER', $source);
    }

    /**
     * Test that recordings-only instances are excluded.
     */
    public function test_excludes_recordings_only_instances(): void {
        $reflection = new ReflectionClass(navigation_append_addon::class);

        // Read the source code to verify recordings-only check.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('is_type_recordings_only', $source);
    }

    /**
     * Test that the completion validate link is NOT added by the append addon.
     *
     * The parent mod_bigbluebuttonbn navigation already provides the completion
     * validate node; appending it here would duplicate it (OL2-3.1.1).
     */
    public function test_does_not_append_completion_validate(): void {
        $reflection = new ReflectionClass(navigation_append_addon::class);

        // Read the source code to verify the completion validate link is absent.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringNotContainsString('action=completion_validate', $source);
        $this->assertStringNotContainsString('completionvalidatestate', $source);
    }

    /**
     * Test that pre-existing nodes are forced into the more menu before the dashboard
     * link is appended, so it becomes the first tab shown (OL2-3.1.1).
     */
    public function test_forces_existing_nodes_into_more_menu(): void {
        $reflection = new ReflectionClass(navigation_append_addon::class);

        // Read the source code to verify the force-into-more-menu call is present.
        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString('set_force_into_more_menu(true)', $source);
    }
}
