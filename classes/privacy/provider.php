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

namespace bbbext_bnx_datahub\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy class for requesting user data.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class provider implements
    // This plugin does store personal user data locally.
    \core_privacy\local\metadata\provider,

    // This plugin implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Provides metadata about the personal data stored by the plugin.
     *
     * @param collection $collection The metadata collection to be updated.
     * @return collection The updated metadata collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('bbbext_bnx_datahub_meeting_user', [
            'userid' => 'privacy:metadata:userid',
            'fullname' => 'privacy:metadata:fullname',
            'role' => 'privacy:metadata:role',
            'timejoin' => 'privacy:metadata:timejoin',
            'timeleave' => 'privacy:metadata:timeleave',
            'elapsedtime' => 'privacy:metadata:elapsedtime',
            'score' => 'privacy:metadata:score',
            'engchats' => 'privacy:metadata:engchats',
            'engtalks' => 'privacy:metadata:engtalks',
            'engraisehand' => 'privacy:metadata:engraisehand',
            'engemojis' => 'privacy:metadata:engemojis',
            'engpollvotes' => 'privacy:metadata:engpollvotes',
            'engtalktime' => 'privacy:metadata:engtalktime',
            'rawdata' => 'privacy:metadata:rawdata',
        ], 'privacy:metadata:bbbext_bnx_datahub_meeting_user');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user ID.
     * @return contextlist The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {bigbluebuttonbn} bbb ON bbb.id = cm.instance
                  JOIN {bbbext_bnx} bnx ON bnx.bigbluebuttonbnid = bbb.id
                  JOIN {bbbext_bnx_datahub_meeting} mtg ON mtg.bnxid = bnx.id
                  JOIN {bbbext_bnx_datahub_meeting_user} mu ON mu.meetingid = mtg.id
                 WHERE mu.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'bigbluebuttonbn',
            'userid' => $userid,
        ];

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist.
     *
     * @param approved_contextlist $contextlist A list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $sql = "SELECT mu.*, mtg.meetingname, mtg.externalmeetingid
                      FROM {bbbext_bnx_datahub_meeting_user} mu
                      JOIN {bbbext_bnx_datahub_meeting} mtg ON mtg.id = mu.meetingid
                      JOIN {bbbext_bnx} bnx ON bnx.id = mtg.bnxid
                      JOIN {bigbluebuttonbn} bbb ON bbb.id = bnx.bigbluebuttonbnid
                      JOIN {course_modules} cm ON cm.instance = bbb.id
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                     WHERE cm.id = :cmid AND mu.userid = :userid";

            $params = [
                'modname' => 'bigbluebuttonbn',
                'cmid' => $context->instanceid,
                'userid' => $userid,
            ];

            $records = $DB->get_records_sql($sql, $params);

            foreach ($records as $record) {
                $exportpath = [
                    get_string('pluginname', 'bbbext_bnx_datahub'),
                    $record->meetingname,
                ];
                writer::with_context($context)->export_data($exportpath, (object)[
                    'meetingname' => $record->meetingname,
                    'externalmeetingid' => $record->externalmeetingid,
                    'fullname' => $record->fullname,
                    'role' => $record->role,
                    'timejoin' => transform::datetime($record->timejoin),
                    'timeleave' => transform::datetime($record->timeleave),
                    'elapsedtime' => $record->elapsedtime,
                    'score' => $record->score,
                    'engchats' => $record->engchats,
                    'engtalks' => $record->engtalks,
                    'engraisehand' => $record->engraisehand,
                    'engemojis' => $record->engemojis,
                    'engpollvotes' => $record->engpollvotes,
                    'engtalktime' => $record->engtalktime,
                ]);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT mu.id
                  FROM {bbbext_bnx_datahub_meeting_user} mu
                  JOIN {bbbext_bnx_datahub_meeting} mtg ON mtg.id = mu.meetingid
                  JOIN {bbbext_bnx} bnx ON bnx.id = mtg.bnxid
                  JOIN {bigbluebuttonbn} bbb ON bbb.id = bnx.bigbluebuttonbnid
                  JOIN {course_modules} cm ON cm.instance = bbb.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";

        $params = [
            'modname' => 'bigbluebuttonbn',
            'cmid' => $context->instanceid,
        ];

        $records = $DB->get_records_sql($sql, $params);
        if (!empty($records)) {
            $ids = array_keys($records);
            $DB->delete_records_list('bbbext_bnx_datahub_meeting_user', 'id', $ids);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist A list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $sql = "SELECT mu.id
                      FROM {bbbext_bnx_datahub_meeting_user} mu
                      JOIN {bbbext_bnx_datahub_meeting} mtg ON mtg.id = mu.meetingid
                      JOIN {bbbext_bnx} bnx ON bnx.id = mtg.bnxid
                      JOIN {bigbluebuttonbn} bbb ON bbb.id = bnx.bigbluebuttonbnid
                      JOIN {course_modules} cm ON cm.instance = bbb.id
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                     WHERE cm.id = :cmid AND mu.userid = :userid";

            $params = [
                'modname' => 'bigbluebuttonbn',
                'cmid' => $context->instanceid,
                'userid' => $userid,
            ];

            $records = $DB->get_records_sql($sql, $params);
            if (!empty($records)) {
                $ids = array_keys($records);
                $DB->delete_records_list('bbbext_bnx_datahub_meeting_user', 'id', $ids);
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing users with data.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $sql = "SELECT DISTINCT mu.userid
                  FROM {bbbext_bnx_datahub_meeting_user} mu
                  JOIN {bbbext_bnx_datahub_meeting} mtg ON mtg.id = mu.meetingid
                  JOIN {bbbext_bnx} bnx ON bnx.id = mtg.bnxid
                  JOIN {bigbluebuttonbn} bbb ON bbb.id = bnx.bigbluebuttonbnid
                  JOIN {course_modules} cm ON cm.instance = bbb.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid AND mu.userid IS NOT NULL";

        $params = [
            'modname' => 'bigbluebuttonbn',
            'cmid' => $context->instanceid,
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $sql = "SELECT mu.id
                  FROM {bbbext_bnx_datahub_meeting_user} mu
                  JOIN {bbbext_bnx_datahub_meeting} mtg ON mtg.id = mu.meetingid
                  JOIN {bbbext_bnx} bnx ON bnx.id = mtg.bnxid
                  JOIN {bigbluebuttonbn} bbb ON bbb.id = bnx.bigbluebuttonbnid
                  JOIN {course_modules} cm ON cm.instance = bbb.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid AND mu.userid $usersql";

        $params = array_merge([
            'modname' => 'bigbluebuttonbn',
            'cmid' => $context->instanceid,
        ], $userparams);

        $records = $DB->get_records_sql($sql, $params);
        if (!empty($records)) {
            $ids = array_keys($records);
            $DB->delete_records_list('bbbext_bnx_datahub_meeting_user', 'id', $ids);
        }
    }
}
