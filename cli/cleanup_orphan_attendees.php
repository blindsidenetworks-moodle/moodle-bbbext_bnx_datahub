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
 * Preview or delete DataHub attendees whose meeting no longer exists.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use bbbext_bnx_datahub\local\services\orphan_cleanup_service;

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'apply' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($unrecognized)) {
    $unrecognized = implode(' ', $unrecognized);
    cli_error("Unrecognised option(s): {$unrecognized}");
}

if (!is_bool($options['apply'])) {
    cli_error('The --apply option does not accept a value.');
}

if (!empty($options['help'])) {
    $help = <<<TXT
Preview or delete DataHub attendee records whose meeting no longer exists.

The default is a read-only preview. Deletion is irreversible; back up the site first.
Run this from the Moodle root as: php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/cleanup_orphan_attendees.php

Options:
  --apply     Delete orphan attendee records
  -h, --help  Print this help
TXT;
    cli_writeln($help);
    exit(0);
}

cli_heading('BNX DataHub orphan attendee cleanup');
$apply = $options['apply'];
$count = orphan_cleanup_service::clean($apply);
cli_writeln("Orphan attendee records found: {$count}");
if ($apply) {
    cli_writeln('Cleanup completed.');
} else {
    cli_writeln('Preview only; no records were deleted. Use --apply to delete these records.');
}
