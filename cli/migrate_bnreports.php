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
 * CLI script to migrate legacy BN Reports data into BNX DataHub.
 *
 * @package   bbbext_bnx_datahub
 * @copyright  2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once(__DIR__ . '/../db/migration.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($unrecognized)) {
    $unrecognized = implode(' ', $unrecognized);
    cli_error("Unrecognised option(s): {$unrecognized}");
}

if (!empty($options['help'])) {
    $help = <<<TXT
Migrate legacy BN Reports data into BNX DataHub.

This command runs the same idempotent migration helper used by the upgrade path.
If BN Reports is still installed and enabled, DataHub will self-disable after the
migration to avoid double-processing the meeting-events stream.
Run this from the Moodle root as: php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/migrate_bnreports.php

Options:
  -h, --help  Print this help
TXT;
    cli_writeln($help);
    exit(0);
}

cli_heading('BNX DataHub BN Reports migration');
cli_writeln('Running legacy BN Reports migration helper...');
bbbext_bnx_datahub_migrate_bnreports_data();
cli_writeln('Migration helper finished.');
