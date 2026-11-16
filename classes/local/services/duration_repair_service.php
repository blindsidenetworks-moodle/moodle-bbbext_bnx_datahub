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

namespace bbbext_bnx_datahub\local\services;

use bbbext_bnx_datahub\logger;
use stdClass;

/**
 * Identify meeting durations supported by retained callback data.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duration_repair_service {
    /**
     * Parse a matching retained callback without applying timestamp fallbacks.
     *
     * @param stdClass $meeting Stored meeting.
     * @return int|null Callback duration in seconds, or null if unavailable.
     */
    public static function expected_duration(stdClass $meeting): ?int {
        if (!is_string($meeting->rawdata ?? null)) {
            return null;
        }
        $payload = json_decode($meeting->rawdata);
        if (
            !is_object($payload) || !is_string($payload->internal_meeting_id ?? null) ||
            $payload->internal_meeting_id !== $meeting->internalmeetingid || !is_object($payload->data ?? null)
        ) {
            return null;
        }
        return logger::parse_callback_duration($payload->data->duration ?? null);
    }

    /**
     * Scan meeting records and optionally apply supported corrections.
     *
     * @param callable|null $onresult Called with the meeting, expected duration, and outcome.
     * @param bool $apply Whether to update matching records.
     * @param bool $stopafterfirst Whether to stop after finding a discrepancy.
     * @return array Scan counts.
     */
    public static function scan(?callable $onresult = null, bool $apply = false, bool $stopafterfirst = false): array {
        global $DB;

        if ($apply && !in_array($DB->get_dbfamily(), ['mysql', 'postgres', 'mssql'], true)) {
            throw new \coding_exception('Duration repair is not supported on this database.');
        }
        $counts = ['scanned' => 0, 'ambiguous' => 0, 'discrepancies' => 0, 'updated' => 0, 'changed' => 0];
        $lastid = 0;
        do {
            $meetings = $DB->get_records_select(
                'bbbext_bnx_datahub_meeting',
                'id > :lastid',
                ['lastid' => $lastid],
                'id ASC',
                'id, internalmeetingid, duration, rawdata',
                0,
                100
            );
            foreach ($meetings as $meeting) {
                $lastid = (int) $meeting->id;
                $counts['scanned']++;
                $expected = self::expected_duration($meeting);
                if ($expected === null) {
                    $counts['ambiguous']++;
                    if ($onresult !== null) {
                        $onresult($meeting, null, 'ambiguous');
                    }
                    continue;
                }
                if ($expected === (int) $meeting->duration) {
                    continue;
                }
                $counts['discrepancies']++;
                $status = 'preview';
                if ($apply) {
                    $status = self::update_if_unchanged($meeting, $expected) ? 'updated' : 'changed';
                    $counts[$status]++;
                }
                if ($onresult !== null) {
                    $onresult($meeting, $expected, $status);
                }
                if ($stopafterfirst) {
                    return $counts;
                }
            }
        } while (count($meetings) === 100);

        return $counts;
    }

    /**
     * Lock and recheck a candidate before updating its duration.
     *
     * @param stdClass $meeting Scanned meeting.
     * @param int $expected Corrected duration.
     * @return bool Whether the record was updated.
     */
    private static function update_if_unchanged(stdClass $meeting, int $expected): bool {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        if ($DB->get_dbfamily() === 'mssql') {
            $sql = 'SELECT id, internalmeetingid, duration, rawdata
                      FROM {bbbext_bnx_datahub_meeting} WITH (UPDLOCK, ROWLOCK)
                     WHERE id = :id';
        } else {
            $sql = 'SELECT id, internalmeetingid, duration, rawdata
                      FROM {bbbext_bnx_datahub_meeting}
                     WHERE id = :id FOR UPDATE';
        }
        $current = $DB->get_record_sql($sql, ['id' => $meeting->id]);
        $unchanged = $current && (int) $current->duration === (int) $meeting->duration &&
            $current->internalmeetingid === $meeting->internalmeetingid && $current->rawdata === $meeting->rawdata;
        if ($unchanged) {
            $DB->set_field('bbbext_bnx_datahub_meeting', 'duration', $expected, ['id' => $meeting->id]);
        }
        $transaction->allow_commit();
        return (bool) $unchanged;
    }
}
