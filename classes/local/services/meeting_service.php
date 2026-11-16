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
        m.meetingname, m.timestart, m.timeend, m.duration, m.excluded, m.metadata, m.rawdata";

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
     * Return meetings visible to the current report viewer for a course module.
     *
     * @param int $cmid Course module ID.
     * @return array Visible meeting records, ordered by start time descending.
     */
    public static function get_report_meetings_by_cmid(int $cmid): array {
        global $DB;

        [$condition, $params] = self::get_report_visibility_sql('m', 'bbb', $cmid);
        $params['cmid'] = $cmid;
        $sql = 'SELECT ' . self::MEETING_COLUMNS . self::JOIN_SQL .
            "WHERE cm.id = :cmid AND ($condition) ORDER BY m.timestart DESC";

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Build the group visibility condition for built-in reports.
     *
     * @param string $meetingalias Meeting table alias.
     * @param string $roomalias BigBlueButton activity table alias.
     * @param int|null $cmid Restrict activity discovery to this course module, if supplied.
     * @param bool $requirepermission Whether to require the DataHub report capability for each activity.
     * @return array SQL condition and parameters.
     */
    public static function get_report_visibility_sql(
        string $meetingalias,
        string $roomalias,
        ?int $cmid = null,
        bool $requirepermission = false
    ): array {
        global $DB, $USER;

        $params = [];
        $where = '';
        if ($cmid !== null) {
            $where = ' AND cm.id = :datahub_visibility_cmid';
            $params['datahub_visibility_cmid'] = $cmid;
        }
        $modules = $DB->get_records_sql(
            "SELECT DISTINCT cm.id, cm.course
               FROM {course_modules} cm
               JOIN {modules} mdl ON mdl.id = cm.module AND mdl.name = 'bigbluebuttonbn'
               JOIN {bbbext_bnx} bnx ON bnx.bigbluebuttonbnid = cm.instance
              WHERE cm.deletioninprogress = 0
                AND EXISTS (SELECT 1 FROM {bbbext_bnx_datahub_meeting} meeting WHERE meeting.bnxid = bnx.id)
                $where",
            $params
        );
        $unrestricted = [];
        $visible = [];
        $separate = [];
        foreach ($modules as $module) {
            if ($requirepermission && !has_capability('bbbext/bnx_datahub:viewreports', \context_module::instance($module->id))) {
                continue;
            }
            $activity = get_fast_modinfo((int) $module->course)->get_cm((int) $module->id);
            $groupmode = (int) groups_get_activity_groupmode($activity);
            if ($groupmode === NOGROUPS || has_capability('moodle/site:accessallgroups', \context_module::instance($module->id))) {
                $unrestricted[] = (int) $module->id;
            } else if ($groupmode === VISIBLEGROUPS) {
                $visible[] = (int) $module->id;
            } else {
                $separate[] = (int) $module->id;
            }
        }

        $unrestrictedsql = empty($unrestricted) ? '1 = 0' : 'dh_cm.id IN (' . implode(',', $unrestricted) . ')';
        $visiblesql = empty($visible) ? '1 = 0' : 'dh_cm.id IN (' . implode(',', $visible) . ')';
        $separatesql = empty($separate) ? '1 = 0' : 'dh_cm.id IN (' . implode(',', $separate) . ')';
        $expectedid = $DB->sql_concat(
            "$roomalias.meetingid",
            "'-'",
            "$roomalias.course",
            "'-'",
            "$roomalias.id",
            "'['",
            'dh_group.id',
            "']X'"
        );
        $actualid = $DB->sql_concat("$meetingalias.externalmeetingid", "'X'");
        $equalid = $DB->sql_equal($actualid, $expectedid);
        $samelen = $DB->sql_length($actualid) . ' = ' . $DB->sql_length($expectedid);
        $useridparam = \core_reportbuilder\local\helpers\database::generate_param_name();
        $condition = "EXISTS (
            SELECT 1
              FROM {course_modules} dh_cm
              JOIN {modules} dh_module ON dh_module.id = dh_cm.module AND dh_module.name = 'bigbluebuttonbn'
         LEFT JOIN {groups} dh_group ON dh_group.courseid = $roomalias.course AND $equalid AND $samelen
             WHERE dh_cm.instance = $roomalias.id
               AND ($unrestrictedsql OR (
                      dh_group.id IS NOT NULL
                      AND (dh_cm.groupingid = 0 OR EXISTS (
                          SELECT 1 FROM {groupings_groups} dh_grouping
                           WHERE dh_grouping.groupingid = dh_cm.groupingid AND dh_grouping.groupid = dh_group.id
                      ))
                      AND ($visiblesql OR ($separatesql AND EXISTS (
                          SELECT 1 FROM {groups_members} dh_member
                           WHERE dh_member.groupid = dh_group.id AND dh_member.userid = :$useridparam
                      )))
               ))
        )";

        return [$condition, [$useridparam => (int) $USER->id]];
    }

    /**
     * Build the activity permission condition for the custom rooms datasource.
     *
     * @param string $roomalias BigBlueButton activity table alias.
     * @return string SQL condition.
     */
    public static function get_report_room_visibility_sql(string $roomalias): string {
        global $DB;

        $modules = $DB->get_records_sql(
            "SELECT cm.id, cm.instance
               FROM {course_modules} cm
               JOIN {modules} mdl ON mdl.id = cm.module AND mdl.name = 'bigbluebuttonbn'
              WHERE cm.deletioninprogress = 0"
        );
        $activities = [];
        foreach ($modules as $module) {
            if (has_capability('bbbext/bnx_datahub:viewreports', \context_module::instance($module->id))) {
                $activities[] = (int) $module->instance;
            }
        }

        return empty($activities) ? '1 = 0' : "$roomalias.id IN (" . implode(',', $activities) . ')';
    }

    /**
     * Reject scheduled custom reports that cannot enforce access for every recipient.
     */
    public static function require_safe_custom_report_execution(): void {
        $task = \core\task\manager::$runningtask;
        if ($task instanceof \core_reportbuilder\task\send_schedule || $task instanceof \core_reportbuilder\task\send_schedules) {
            throw new \moodle_exception('scheduledreportsnotavailable', 'bbbext_bnx_datahub');
        }
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
