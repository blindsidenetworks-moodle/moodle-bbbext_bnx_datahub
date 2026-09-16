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
 * Surface the conflict with the legacy BN Reports sidecar.
 *
 * DataHub and the legacy bbbext_bnreports sidecar consume the same BigBlueButton
 * meeting-events stream, so they must not run concurrently. Rather than mutating a
 * sibling component's configuration (OL-3.2.1, Option D), DataHub self-disables while
 * bnreports is still installed and enabled. This status check explains that state to
 * administrators and points them at the Plugins overview page, where the conflict can
 * be resolved by disabling or removing bnreports and then re-enabling DataHub.
 *
 * @package    bbbext_bnx_datahub
 * @copyright  2026 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bnreports_conflict extends check {
    /**
     * Short, stable identifier used by the check manager.
     *
     * @return string
     */
    public function get_ref(): string {
        return 'bbbext_bnx_datahub_bnreports_conflict';
    }

    /**
     * Display name for this check.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('check_bnreports_conflict', 'bbbext_bnx_datahub');
    }

    /**
     * Link to the Plugins overview where the conflict is resolved.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/mod/bigbluebuttonbn/adminmanageplugins.php'),
            get_string('check_bnreports_conflict_action', 'bbbext_bnx_datahub')
        );
    }

    /**
     * Report whether BN Reports conflicts with DataHub.
     *
     * @return result
     */
    public function get_result(): result {
        $pm = \core_plugin_manager::instance();
        $installed = $pm->get_installed_plugins('bbbext');

        $bnreportspresent = isset($installed['bnreports'])
            && empty(get_config('bbbext_bnreports', 'disabled'));
        $datahubdisabled = !empty(get_config('bbbext_bnx_datahub', 'disabled'));

        if ($bnreportspresent && $datahubdisabled) {
            return new result(
                result::WARNING,
                get_string('check_bnreports_conflict_warning', 'bbbext_bnx_datahub'),
                get_string('check_bnreports_conflict_warning_details', 'bbbext_bnx_datahub')
            );
        }

        if ($bnreportspresent) {
            return new result(
                result::WARNING,
                get_string('check_bnreports_conflict_active_warning', 'bbbext_bnx_datahub'),
                get_string('check_bnreports_conflict_active_warning_details', 'bbbext_bnx_datahub')
            );
        }

        return new result(
            result::OK,
            get_string('check_bnreports_conflict_ok', 'bbbext_bnx_datahub'),
            get_string('check_bnreports_conflict_ok_details', 'bbbext_bnx_datahub')
        );
    }
}
