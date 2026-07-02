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
use mod_bigbluebuttonbn\local\extension\navigation_append_addon as navigation_append_addon_interface;

/**
 * Append implementation for the bbbext_bnx_datahub subplugin.
 *
 * DataHub only adds report / completion-validate nodes to the BigBlueButtonBN
 * settings navigation; it never replaces the parent navigation. It therefore
 * implements the additive append contract instead of the exclusive override
 * contract, so it no longer consumes the single override slot and other
 * subplugins' navigation contributions are preserved (OL2-3.1.1).
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class navigation_append_addon implements navigation_append_addon_interface {
    /**
     * Appends DataHub report links to the settings navigation for BigBlueButtonBN.
     *
     * @param \settings_navigation $settingsnav The settings navigation object.
     * @param \navigation_node $nodenav The current navigation node.
     * @return void
     */
    public function append_settings_navigation(\settings_navigation $settingsnav, \navigation_node $nodenav): void {
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
                if (has_capability('bbbext/bnx_datahub:viewreports', $context)) {
                    // Force every node already present on the module settings branch (the parent
                    // module's own nodes plus its completion-validate link) into the "more" menu,
                    // so DataHub's report links, added immediately afterwards, become the first
                    // secondary-navigation tabs shown to the user (OL2-3.1.1).
                    $nodenav->set_force_into_more_menu(true);
                    // Attendance report link.
                    $attendancereporturl = new moodle_url(
                        '/mod/bigbluebuttonbn/extension/bnx_datahub/attendance.php',
                        ['cmid' => $cm->id]
                    );
                    $nodenav->add(
                        get_string('reportattendance', 'bbbext_bnx_datahub'),
                        $attendancereporturl,
                        navigation_node::TYPE_CONTAINER
                    );
                    // Engagement report link.
                    $engagementreporturl = new moodle_url(
                        '/mod/bigbluebuttonbn/extension/bnx_datahub/engagement.php',
                        ['cmid' => $cm->id]
                    );
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
