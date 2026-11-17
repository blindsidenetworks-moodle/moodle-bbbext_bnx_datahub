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
 * Preview or repair historical DataHub meeting durations.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

use bbbext_bnx_datahub\local\services\duration_repair_service;

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    ['help' => false, 'apply' => false],
    ['h' => 'help']
);

if (!empty($unrecognized)) {
    cli_error('Unrecognised option(s): ' . implode(' ', $unrecognized));
}
if (!is_bool($options['apply'])) {
    cli_error('The --apply option does not accept a value.');
}
if ($options['help']) {
    cli_writeln(<<<TXT
Preview or repair historical meeting durations using retained matching callbacks.

The default is read-only. Review the preview and back up the site before applying.
Each run scans current data again; records may differ from an earlier preview.
Run from the Moodle root: php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/repair_durations.php

Options:
    --apply     Apply supported corrections
    -h, --help  Print this help
TXT);
    exit(0);
}

cli_heading('BNX DataHub historical meeting durations');
$counts = duration_repair_service::scan(
    static function (stdClass $meeting, ?int $expected, string $status): void {
        if ($status === 'ambiguous') {
            cli_writeln("Meeting {$meeting->id}: no usable matching callback duration (ambiguous)");
        } else {
            cli_writeln("Meeting {$meeting->id}: {$meeting->duration} -> {$expected} seconds ({$status})");
        }
    },
    $options['apply']
);
cli_writeln("Meetings scanned: {$counts['scanned']}");
cli_writeln("Records without usable callback evidence: {$counts['ambiguous']}");
cli_writeln("Supported discrepancies: {$counts['discrepancies']}");
if ($options['apply']) {
    cli_writeln("Records updated: {$counts['updated']}");
    cli_writeln("Records changed during the scan and skipped: {$counts['changed']}");
} else {
    cli_writeln('Preview only; no records were changed. Use --apply after reviewing the results.');
}
