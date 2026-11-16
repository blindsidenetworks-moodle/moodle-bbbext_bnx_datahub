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

use bbbext_bnx_datahub\local\services\duration_repair_service;
use core\check\check;
use core\check\result;

/**
 * Warn when a retained callback reveals an incorrect meeting duration.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duration_discrepancies extends check {
    /**
     * Return the check identifier.
     *
     * @return string
     */
    public function get_ref(): string {
        return 'bbbext_bnx_datahub_duration_discrepancies';
    }

    /**
     * Return the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('check_duration_discrepancies', 'bbbext_bnx_datahub');
    }

    /**
     * Return the check result.
     *
     * @return result
     */
    public function get_result(): result {
        $found = duration_repair_service::scan(null, false, true)['discrepancies'] > 0;
        $status = $found ? result::WARNING : result::OK;
        $suffix = $found ? 'warning' : 'ok';
        return new result(
            $status,
            get_string('check_duration_discrepancies_' . $suffix, 'bbbext_bnx_datahub'),
            get_string('check_duration_discrepancies_' . $suffix . '_details', 'bbbext_bnx_datahub')
        );
    }
}
