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

use context_module;
use moodle_url;
use navigation_node;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\extension\navigation_override_addon as navigation_override_addon_interface;

/**
 * Override implementation for the bbbext_bnx_datahub subplugin.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class navigation_override_addon implements navigation_override_addon_interface {
    /**
     * Overrides the settings navigation for BigBlueButtonBN.
     *
     * @param \settings_navigation $settingsnav The settings navigation object.
     * @param \navigation_node $nodenav The current navigation node.
     * @return void
     */
    public function override_settings_navigation(\settings_navigation $settingsnav, \navigation_node $nodenav): void {
        // Check if BNX is enabled.
        $enabledplugins = \core_plugin_manager::instance()->get_enabled_plugins('bbbext');
        if (!isset($enabledplugins['bnx'])) {
            return;
        }

        // Get Course Module.
        $cm = $settingsnav->get_page()->cm;
        // Get the instance.
        $instance = instance::get_from_instanceid($cm->instance);
        // Go further only if the instance is not recordings-only type.
        if (!$instance->is_type_recordings_only()) {
            $context = context_module::instance($cm->id);
            // Add these options only if the callback for meetingevents is enabled.
            if ((bool) \mod_bigbluebuttonbn\local\config::get('meetingevents_enabled')) {
                global $USER;
                if (has_capability('moodle/course:manageactivities', $context, $USER->id)) {
                    // Validate completion link.
                    $completionvalidate = '#action=completion_validate&bigbluebuttonbn=' . $cm->instance;
                    $nodenav->add(
                        get_string('completionvalidatestate', 'bigbluebuttonbn'),
                        $completionvalidate,
                        navigation_node::TYPE_CONTAINER
                    );
                    $nodenav->set_force_into_more_menu(true);
                }
                if (has_capability('bbbext/bnx_datahub:viewreports', $context)) {
                    // Attendance report link.
                    $attendancereporturl = new moodle_url(
                        '/mod/bigbluebuttonbn/extension/bnx_datahub/attendance.php', ['cmid' => $cm->id]);
                    $nodenav->add(
                        get_string('reportattendance', 'bbbext_bnx_datahub'),
                        $attendancereporturl,
                        navigation_node::TYPE_CONTAINER
                    );
                    // Engagement report link.
                    $engagementreporturl = new moodle_url(
                        '/mod/bigbluebuttonbn/extension/bnx_datahub/engagement.php', ['cmid' => $cm->id]);
                    $nodenav->add(
                        get_string('reportengagement', 'bbbext_bnx_datahub'),
                        $engagementreporturl,
                        navigation_node::TYPE_CONTAINER
                    );
                }
            }
        }
    }
}
