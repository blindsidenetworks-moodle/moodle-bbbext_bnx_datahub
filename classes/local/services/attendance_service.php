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
 * Service layer for querying DataHub attendance (meeting_user) records.
 *
 * This is the only sanctioned way for external plugins to read attendance data
 * from the DataHub tables. All join-chain resolution lives here.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class attendance_service {
    /**
     * Return all attendance records for the most recent meeting of a course module.
     *
     * Resolves the cmid → meeting join chain, picks the most recent meeting,
     * and returns all meeting_user rows for that meeting.
     *
     * @param int $cmid Course module ID.
     * @return array Array of attendance record objects (may be empty).
     */
    public static function get_attendance_by_cmid(int $cmid): array {
        $meeting = meeting_service::get_by_cmid($cmid);
        if ($meeting === null) {
            return [];
        }

        return self::get_attendance_by_meeting((int) $meeting->id);
    }

    /**
     * Return all attendance records for a specific meeting.
     *
     * @param int $meetingid The bbbext_bnx_datahub_meeting.id.
     * @return array Array of attendance record objects (may be empty).
     */
    public static function get_attendance_by_meeting(int $meetingid): array {
        global $DB;

        return array_values(
            $DB->get_records('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meetingid], 'id ASC')
        );
    }

    /**
     * Return the attendance record for a specific user in the most recent meeting of a course module.
     *
     * @param int $userid  The Moodle user ID.
     * @param int $cmid    Course module ID.
     * @return \stdClass|null The attendance record, or null if not found.
     */
    public static function get_attendance_by_user(int $userid, int $cmid): ?\stdClass {
        global $DB;

        $meeting = meeting_service::get_by_cmid($cmid);
        if ($meeting === null) {
            return null;
        }

        $record = $DB->get_record('bbbext_bnx_datahub_meeting_user', [
            'meetingid' => (int) $meeting->id,
            'userid' => $userid,
        ]);

        return $record ?: null;
    }
}
