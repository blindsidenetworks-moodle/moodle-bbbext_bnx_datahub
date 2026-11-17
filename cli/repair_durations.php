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
    cli_error(get_string('cli:unknown_option', 'bbbext_bnx_datahub', implode(' ', $unrecognized)));
}
if (!is_bool($options['apply'])) {
    cli_error(get_string('cli:repair_apply_value', 'bbbext_bnx_datahub'));
}
if ($options['help']) {
    cli_writeln(get_string('cli:repair_help', 'bbbext_bnx_datahub'));
    exit(0);
}

cli_heading(get_string('cli:repair_heading', 'bbbext_bnx_datahub'));
$counts = duration_repair_service::scan(
    static function (stdClass $meeting, ?int $expected, string $status): void {
        if ($status === 'ambiguous') {
            cli_writeln(get_string('cli:repair_ambiguous', 'bbbext_bnx_datahub', $meeting->id));
        } else {
            $details = (object) [
                'id' => $meeting->id,
                'duration' => $meeting->duration,
                'expected' => $expected,
                'status' => get_string('cli:repair_status_' . $status, 'bbbext_bnx_datahub'),
            ];
            cli_writeln(get_string('cli:repair_result', 'bbbext_bnx_datahub', $details));
        }
    },
    $options['apply']
);
cli_writeln(get_string('cli:repair_scanned', 'bbbext_bnx_datahub', $counts['scanned']));
cli_writeln(get_string('cli:repair_ambiguous_count', 'bbbext_bnx_datahub', $counts['ambiguous']));
cli_writeln(get_string('cli:repair_discrepancies', 'bbbext_bnx_datahub', $counts['discrepancies']));
if ($options['apply']) {
    cli_writeln(get_string('cli:repair_updated_count', 'bbbext_bnx_datahub', $counts['updated']));
    cli_writeln(get_string('cli:repair_changed_count', 'bbbext_bnx_datahub', $counts['changed']));
} else {
    cli_writeln(get_string('cli:repair_preview', 'bbbext_bnx_datahub'));
}
