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

namespace bbbext_bnx_datahub\check;

use core\check\check;
use core\check\result;

/**
 * Surface whether mod_bigbluebuttonbn meeting events are enabled.
 *
 * The bnx_datahub sidecar relies on mod_bigbluebuttonbn emitting analytics
 * callbacks, which only fires when the bigbluebuttonbn_meetingevents_enabled
 * config flag is set on the parent plugin. As a sidecar subplugin, bnx_datahub
 * must not write that flag itself (OL-3.2.1); this status check makes the
 * dependency visible to administrators via Site administration / Server / Status.
 *
 * @package    bbbext_bnx_datahub
 * @copyright  2026 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meetingevents_enabled extends check {
    /**
     * Short, stable identifier used by the check manager.
     *
     * @return string
     */
    public function get_ref(): string {
        return 'bbbext_bnx_datahub_meetingevents_enabled';
    }

    /**
     * Display name for this check.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('check_meetingevents_enabled', 'bbbext_bnx_datahub');
    }

    /**
     * Link to the parent plugin settings where the flag is toggled.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php', ['section' => 'modsettingbigbluebuttonbn']),
            get_string('check_meetingevents_enabled_action', 'bbbext_bnx_datahub')
        );
    }

    /**
     * Evaluate the parent setting and report.
     *
     * @return result
     */
    public function get_result(): result {
        $enabled = (int) get_config('mod_bigbluebuttonbn', 'meetingevents_enabled') === 1
            || (int) get_config(null, 'bigbluebuttonbn_meetingevents_enabled') === 1;

        if ($enabled) {
            return new result(
                result::OK,
                get_string('check_meetingevents_enabled_ok', 'bbbext_bnx_datahub'),
                get_string('check_meetingevents_enabled_ok_details', 'bbbext_bnx_datahub')
            );
        }

        return new result(
            result::WARNING,
            get_string('check_meetingevents_enabled_warning', 'bbbext_bnx_datahub'),
            get_string('check_meetingevents_enabled_warning_details', 'bbbext_bnx_datahub')
        );
    }
}
