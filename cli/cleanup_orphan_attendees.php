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
 * Preview or delete orphan DataHub attendees or meetings.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

use bbbext_bnx_datahub\local\services\orphan_cleanup_service;

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'apply' => false,
        'orphan-meetings' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($unrecognized)) {
    $unrecognized = implode(' ', $unrecognized);
    cli_error(get_string('cli:unknown_option', 'bbbext_bnx_datahub', $unrecognized));
}

if (!is_bool($options['apply']) || !is_bool($options['orphan-meetings'])) {
    cli_error(get_string('cli:cleanup_option_values', 'bbbext_bnx_datahub'));
}

if (!empty($options['help'])) {
    cli_writeln(get_string('cli:cleanup_help', 'bbbext_bnx_datahub'));
    exit(0);
}

$apply = $options['apply'];
if ($options['orphan-meetings']) {
    cli_heading(get_string('cli:cleanup_meetings_heading', 'bbbext_bnx_datahub'));
    $counts = orphan_cleanup_service::clean_orphan_meetings($apply);
    cli_writeln(get_string('cli:cleanup_meeting_count', 'bbbext_bnx_datahub', $counts['meetings']));
    cli_writeln(get_string('cli:cleanup_meeting_attendees', 'bbbext_bnx_datahub', $counts['attendees']));
} else {
    cli_heading(get_string('cli:cleanup_attendees_heading', 'bbbext_bnx_datahub'));
    $count = orphan_cleanup_service::clean($apply);
    cli_writeln(get_string('cli:cleanup_attendee_count', 'bbbext_bnx_datahub', $count));
}
if ($apply) {
    cli_writeln(get_string('cli:cleanup_done', 'bbbext_bnx_datahub'));
} else {
    cli_writeln(get_string('cli:cleanup_preview', 'bbbext_bnx_datahub'));
}
