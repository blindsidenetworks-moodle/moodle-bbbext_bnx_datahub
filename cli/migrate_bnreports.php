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
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
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
    cli_error(get_string('cli:unknown_option', 'bbbext_bnx_datahub', $unrecognized));
}

if (!empty($options['help'])) {
    cli_writeln(get_string('cli:migrate_help', 'bbbext_bnx_datahub'));
    exit(0);
}

cli_heading(get_string('cli:migrate_heading', 'bbbext_bnx_datahub'));
cli_writeln(get_string('cli:migrate_start', 'bbbext_bnx_datahub'));
bbbext_bnx_datahub_migrate_bnreports_data();
cli_writeln(get_string('cli:migrate_done', 'bbbext_bnx_datahub'));
