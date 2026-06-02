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
 * Plugin-level callback registrations for bbbext_bnx_datahub.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Register plugin status checks with the core check manager.
 *
 * Surfaces the dependency on the parent mod_bigbluebuttonbn meeting events
 * setting without mutating it (OL-3.2.1).
 *
 * @return \core\check\check[]
 */
function bbbext_bnx_datahub_status_checks(): array {
    return [
        new \bbbext_bnx_datahub\check\meetingevents_enabled(),
    ];
}
