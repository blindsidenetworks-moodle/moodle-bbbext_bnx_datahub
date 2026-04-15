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

namespace bbbext_bnx_datahub;

use core_user;
use stdClass;
use mod_bigbluebuttonbn\instance;

/**
 * Utility class for logging meeting events into a proper data structure.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class logger {
    /**
     * Logs a meeting with the given instance and data.
     *
     * @param instance $instance An instance of the meeting to be logged.
     * @param stdClass $data     The data associated with the meeting.
     * @return int               The log entry ID or status code.
     */
    public static function log_meeting(instance $instance, stdClass $data): int {
        global $DB;

        // Get the bnx record for this instance.
        $bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $instance->get_instance_id()]);
        if (!$bnx) {
            debugging('[bbbext_bnx_datahub] logger::log_meeting No BNX record found for instance.', DEBUG_DEVELOPER);
            return 0;
        }

        $record = new stdClass();
        $record->bnxid = $bnx->id;
        $record->externalmeetingid = $data->meeting_id;
        $record->internalmeetingid = $data->internal_meeting_id;
        $record->meetingname = $data->data->metadata->meeting_name;
        $record->timestart = strtotime($data->data->start);
        $record->timeend = strtotime($data->data->finish);
        $record->duration = strtotime($data->data->duration);
        $record->metadata = json_encode($data->data->metadata);
        // Remove the attendees section for privacy reasons.
        if (isset($data->data) && isset($data->data->attendees)) {
            unset($data->data->attendees);
        }
        $record->rawdata = json_encode($data);

        // Define the conditions to identify the record.
        $conditions = ['internalmeetingid' => $record->internalmeetingid];

        // Try to retrieve the existing record.
        $existingrecord = $DB->get_record('bbbext_bnx_datahub_meeting', $conditions);

        if ($existingrecord) {
            // Update the existing record.
            $record->id = $existingrecord->id;
            $r = $DB->update_record('bbbext_bnx_datahub_meeting', $record);
            $r = $existingrecord->id;
        } else {
            // Insert a new record.
            $r = $DB->insert_record('bbbext_bnx_datahub_meeting', $record);
        }

        // Optionally check the result.
        if (!$r) {
            debugging('[bbbext_bnx_datahub] logger::log_meeting Error during upsert operation.', DEBUG_DEVELOPER);
        }

        return $r;
    }

    /**
     * Logs a user's participation in a meeting with the given meeting ID and data.
     *
     * @param int      $meetingid The ID of the meeting to log the user's participation.
     * @param stdClass $data      The data associated with the user's participation in the meeting.
     * @return int                The log entry ID or a status code indicating the result.
     */
    public static function log_meeting_user(int $meetingid, stdClass $data): int {
        global $DB;

        // Skip non-Moodle participants (e.g. AI bot) whose ext_user_id is not a
        // numeric Moodle user ID. The datahub tracks enrolled-user engagement; bots
        // have no Moodle account and cannot be looked up via core_user::get_user().
        if (!is_numeric($data->ext_user_id)) {
            debugging(
                '[bbbext_bnx_datahub] logger::log_meeting_user Skipping non-Moodle participant'
                    . ' (ext_user_id=' . $data->ext_user_id . ')',
                DEBUG_DEVELOPER
            );
            return 0;
        }

        $record = new stdClass();
        $record->meetingid = $meetingid;
        $record->userid = (int) $data->ext_user_id;
        $record->fullname = fullname(core_user::get_user((int) $data->ext_user_id));
        if ($data->moderator == 'true') {
            $record->role = 'moderator';
        } else {
            $record->role = 'viewer';
        }
        $record->timejoin = strtotime($data->joins[0]);
        $record->timeleave = strtotime($data->leaves[0]);
        $record->elapsedtime = $data->duration;
        $record->engchats = $data->engagement->chats;
        $record->engtalks = $data->engagement->talks;
        $record->engraisehand = $data->engagement->raisehand;
        $record->engemojis = $data->engagement->emojis;
        $record->engpollvotes = $data->engagement->poll_votes;
        $record->engtalktime = $data->engagement->talk_time;
        $record->rawdata = json_encode($data);

        // Define the conditions to identify the record.
        $conditions = ['meetingid' => $record->meetingid, 'userid' => $record->userid];

        // Try to retrieve the existing record.
        $existingrecord = $DB->get_record('bbbext_bnx_datahub_meeting_user', $conditions);

        if ($existingrecord) {
            // Update the existing record.
            $record->id = $existingrecord->id;
            $r = $DB->update_record('bbbext_bnx_datahub_meeting_user', $record);
        } else {
            // Insert a new record.
            $r = $DB->insert_record('bbbext_bnx_datahub_meeting_user', $record);
        }

        // Optionally check the result.
        if (!$r) {
            debugging('[bbbext_bnx_datahub] logger::log_meeting_user Error during upsert operation.', DEBUG_DEVELOPER);
        }

        return $r;
    }
}
