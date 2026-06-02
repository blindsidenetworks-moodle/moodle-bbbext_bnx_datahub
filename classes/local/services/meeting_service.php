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

/**
 * Service layer for querying DataHub meeting records.
 *
 * This is the only sanctioned way for external plugins to read meeting data
 * from the DataHub tables. All join-chain resolution lives here.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class meeting_service {
    /**
     * Base SQL fragment that resolves the cmid → meeting join chain.
     *
     * Join chain:
     *   course_modules (cm) → bigbluebuttonbn (bbb) → bbbext_bnx (bnx) → bbbext_bnx_datahub_meeting (m)
     */
    private const JOIN_SQL = "
        FROM {bbbext_bnx_datahub_meeting} m
        JOIN {bbbext_bnx} bnx ON bnx.id = m.bnxid
        JOIN {bigbluebuttonbn} bbb ON bbb.id = bnx.bigbluebuttonbnid
        JOIN {course_modules} cm ON cm.instance = bbb.id
        JOIN {modules} mdl ON mdl.id = cm.module AND mdl.name = 'bigbluebuttonbn'
    ";

    /**
     * Explicit column list for bbbext_bnx_datahub_meeting (avoid SELECT m.*).
     *
     * Mirrors db/install.xml so adding a new column is a deliberate change
     * here and downstream consumers do not silently inherit unexpected fields
     * (OL-4.1.6).
     */
    private const MEETING_COLUMNS = "m.id, m.bnxid, m.externalmeetingid, m.internalmeetingid,
        m.meetingname, m.timestart, m.timeend, m.duration, m.metadata, m.rawdata";

    /**
     * Return the most recent meeting for a given course module ID.
     *
     * @param int $cmid Course module ID.
     * @return \stdClass|null The meeting record, or null if none found.
     */
    public static function get_by_cmid(int $cmid): ?\stdClass {
        global $DB;

        $sql = "SELECT " . self::MEETING_COLUMNS . self::JOIN_SQL . "WHERE cm.id = :cmid ORDER BY m.timestart DESC";
        $records = $DB->get_records_sql($sql, ['cmid' => $cmid], 0, 1);

        return !empty($records) ? reset($records) : null;
    }

    /**
     * Return all meetings for a given course module ID, ordered by timestart descending.
     *
     * @param int $cmid Course module ID.
     * @return array Array of meeting record objects (may be empty).
     */
    public static function get_all_by_cmid(int $cmid): array {
        global $DB;

        $sql = "SELECT " . self::MEETING_COLUMNS . self::JOIN_SQL . "WHERE cm.id = :cmid ORDER BY m.timestart DESC";

        return array_values($DB->get_records_sql($sql, ['cmid' => $cmid]));
    }

    /**
     * Return a meeting by its internal meeting ID.
     *
     * @param string $internalmeetingid The BBB internal meeting ID.
     * @return \stdClass|null The meeting record, or null if not found.
     */
    public static function get_by_internal_meeting_id(string $internalmeetingid): ?\stdClass {
        global $DB;

        return $DB->get_record('bbbext_bnx_datahub_meeting', ['internalmeetingid' => $internalmeetingid]) ?: null;
    }
}
