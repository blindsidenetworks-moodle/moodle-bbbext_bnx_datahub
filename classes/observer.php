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

/**
 * Event observer class for BNX Data Hub extension.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx_datahub;

/**
 * Event observer class.
 */
class observer {
    /**
     * Handle the plugin_enabled event.
     *
     * When bnx_datahub extension is enabled, ensure that the
     * BigBlueButton meeting events setting is also enabled.
     *
     * @param \core\event\plugin_enabled $event The plugin_enabled event.
     * @return void
     */
    public static function on_plugin_enabled(\core\event\plugin_enabled $event) {
        // Only proceed if the enabled plugin is bnx_datahub.
        if ($event->other['plugin'] !== 'bbbext_bnx_datahub') {
            return;
        }

        // Enable BigBlueButton meeting events when bnx_datahub is enabled.
        set_config('bigbluebuttonbn_meetingevents_enabled', 1, 'mod_bigbluebuttonbn');
    }
}
