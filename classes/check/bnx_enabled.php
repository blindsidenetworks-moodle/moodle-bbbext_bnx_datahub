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
 * Surface whether the required BNX sidecar is enabled.
 *
 * @package    bbbext_bnx_datahub
 * @copyright  2026 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bnx_enabled extends check {
    /**
     * Return the stable identifier for this prerequisite check.
     *
     * @return string
     */
    public function get_ref(): string {
        return 'bbbext_bnx_datahub_bnx_enabled';
    }

    /**
     * Return the administrator-facing name for this prerequisite check.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('check_bnx_enabled', 'bbbext_bnx_datahub');
    }

    /**
     * Return the action link that helps administrators resolve the prerequisite.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/plugins.php'),
            get_string('check_bnx_enabled_action', 'bbbext_bnx_datahub')
        );
    }

    /**
     * Evaluate whether the required BNX sidecar is currently enabled.
     *
     * @return result
     */
    public function get_result(): result {
        require_once(__DIR__ . '/../../db/migration.php');

        if (bbbext_bnx_datahub_is_bnx_enabled()) {
            return new result(
                result::OK,
                get_string('check_bnx_enabled_ok', 'bbbext_bnx_datahub'),
                get_string('check_bnx_enabled_ok_details', 'bbbext_bnx_datahub')
            );
        }

        return new result(
            result::WARNING,
            get_string('check_bnx_enabled_warning', 'bbbext_bnx_datahub'),
            get_string('check_bnx_enabled_warning_details', 'bbbext_bnx_datahub')
        );
    }
}
