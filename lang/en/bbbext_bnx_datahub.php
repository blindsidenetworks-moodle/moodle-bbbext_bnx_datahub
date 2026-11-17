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
 * English language pack for BigBlueButton BN Experience - Data Hub
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

$string['attendance'] = 'Attendance';
$string['attendee'] = 'BigBlueButton attendee';
$string['attendee:elapsedtime'] = 'Elapsed time';
$string['attendee:engchats'] = 'Chats';
$string['attendee:engemojis'] = 'Emojis';
$string['attendee:engpollvotes'] = 'Poll votes';
$string['attendee:engraisehand'] = 'Raised hand';
$string['attendee:engtalks'] = 'Talks';
$string['attendee:engtalktime'] = 'Talk time';
$string['attendee:fullname'] = 'Attendee full name';
$string['attendee:role'] = 'Role';
$string['attendee:role:moderator'] = 'Moderator';
$string['attendee:role:viewer'] = 'Viewer';
$string['attendee:score'] = 'Score';
$string['attendee:timejoin'] = 'Time joined';
$string['attendee:timeleave'] = 'Time left';
$string['attendees'] = 'Attendees';
$string['bnx_datahub:manageexclusions'] = 'Exclude and restore sessions in BigBlueButton BNX reports';
$string['bnx_datahub:viewreports'] = 'View BigBlueButton BNX reports';
$string['check_bnreports_conflict'] = 'Legacy BN Reports conflict';
$string['check_bnreports_conflict_action'] = 'Open BigBlueButton extension management';
$string['check_bnreports_conflict_active_warning'] = 'Conflict detected: Data Hub cannot run while BN Reports is installed and enabled.';
$string['check_bnreports_conflict_active_warning_details'] = 'bbbext_bnx_datahub and the legacy bbbext_bnreports sidecar consume the same BigBlueButton meeting-events stream and would double-process events if they ran together. Disable or remove BN Reports from the Plugins overview page, then re-enable Data Hub from the same page.';
$string['check_bnreports_conflict_ok'] = 'No conflict with the legacy BN Reports plugin.';
$string['check_bnreports_conflict_ok_details'] = 'The legacy bbbext_bnreports sidecar is not installed (or is disabled), so bbbext_bnx_datahub can run normally.';
$string['check_bnreports_conflict_warning'] = 'Data Hub is disabled because BN Reports is installed and enabled.';
$string['check_bnreports_conflict_warning_details'] = 'bbbext_bnx_datahub and the legacy bbbext_bnreports sidecar consume the same BigBlueButton meeting-events stream and cannot run at the same time. Data Hub has disabled itself to avoid double-processing events; it never changes the BN Reports configuration. Disable or remove BN Reports from the Plugins overview page, then re-enable Data Hub from the same page.';
$string['check_bnreports_migration_pending'] = 'Legacy BN Reports migration pending';
$string['check_bnreports_migration_pending_ok'] = 'No BN Reports migration is pending.';
$string['check_bnreports_migration_pending_ok_details'] = 'All legacy BN Reports meetings and attendees that still exist have already been copied into DataHub.';
$string['check_bnreports_migration_pending_warning'] = 'Legacy BN Reports data still needs to be migrated.';
$string['check_bnreports_migration_pending_warning_details'] = 'At least one legacy BN Reports meeting or attendee row still exists in the old tables and has not been copied into DataHub yet. Run the manual migration command from the server shell: php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/migrate_bnreports.php.';
$string['check_bnx_enabled'] = 'BN Experience dependency';
$string['check_bnx_enabled_action'] = 'Open BigBlueButton extension management';
$string['check_bnx_enabled_enable_error'] = 'Data Hub cannot be enabled while BN Experience is disabled or uninstalled. Enable or install BN Experience first, then enable Data Hub again.';
$string['check_bnx_enabled_ok'] = 'BN Experience is enabled.';
$string['check_bnx_enabled_ok_details'] = 'bbbext_bnx is enabled, so Data Hub can use the BNX room records and lifecycle integrations it depends on.';
$string['check_bnx_enabled_warning'] = 'BN Experience is not enabled.';
$string['check_bnx_enabled_warning_details'] = 'bbbext_bnx_datahub requires the main BN Experience subplugin (bbbext_bnx) to be installed and enabled. Data Hub disables itself when BNX is unavailable. Re-enable BN Experience from the Plugins overview page before enabling Data Hub again.';
$string['check_duration_discrepancies'] = 'Meeting duration discrepancies';
$string['check_duration_discrepancies_ok'] = 'No supported meeting duration corrections were found.';
$string['check_duration_discrepancies_ok_details'] = 'Records without a valid matching retained callback cannot be verified by this check.';
$string['check_duration_discrepancies_warning'] = 'Some meeting durations differ from their retained callbacks.';
$string['check_duration_discrepancies_warning_details'] = 'Preview the historical duration repair from the server shell: php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/repair_durations.php. Review the results before applying changes.';
$string['check_meetingevents_enabled'] = 'BigBlueButton meeting events';
$string['check_meetingevents_enabled_action'] = 'Open BigBlueButton settings';
$string['check_meetingevents_enabled_ok'] = 'Meeting events are enabled.';
$string['check_meetingevents_enabled_ok_details'] = 'mod_bigbluebuttonbn is configured to emit meeting analytics callbacks, which bbbext_bnx_datahub consumes to populate attendance and engagement reports.';
$string['check_meetingevents_enabled_warning'] = 'Meeting events are not enabled on mod_bigbluebuttonbn.';
$string['check_meetingevents_enabled_warning_details'] = 'bbbext_bnx_datahub depends on mod_bigbluebuttonbn emitting analytics callbacks. Enable "Meeting events" in the BigBlueButton activity plugin settings; without it, attendance and engagement reports will not be populated. The bnx_datahub sidecar does not modify this parent setting on its own.';
$string['cli:cleanup_attendee_count'] = 'Orphan attendee records found: {$a}';
$string['cli:cleanup_attendees_heading'] = 'BNX DataHub orphan attendee cleanup';
$string['cli:cleanup_done'] = 'Cleanup completed.';
$string['cli:cleanup_help'] = 'Preview or delete orphan DataHub records. By default, select attendees whose meeting no longer exists.

The default is a read-only preview. Deletion is irreversible; back up the site first.
Run this from the Moodle root as: php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/cleanup_orphan_attendees.php

Options:
    --orphan-meetings  Select meetings whose BNX record no longer exists, and their attendees
    --apply            Delete the selected orphan records
    -h, --help         Print this help';
$string['cli:cleanup_meeting_attendees'] = 'Attendee records in those meetings: {$a}';
$string['cli:cleanup_meeting_count'] = 'Orphan meetings found: {$a}';
$string['cli:cleanup_meetings_heading'] = 'BNX DataHub orphan meeting cleanup';
$string['cli:cleanup_option_values'] = 'The --apply and --orphan-meetings options do not accept values.';
$string['cli:cleanup_preview'] = 'Preview only; no records were deleted. Use --apply to delete these records.';
$string['cli:migrate_done'] = 'Migration helper finished.';
$string['cli:migrate_heading'] = 'BNX DataHub BN Reports migration';
$string['cli:migrate_help'] = 'Migrate legacy BN Reports data into BNX DataHub.

This command runs the same idempotent migration helper used by the upgrade path.
If BN Reports is still installed and enabled, DataHub will self-disable after the
migration to avoid double-processing the meeting-events stream.
Run this from the Moodle root as: php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/migrate_bnreports.php

Options:
  -h, --help  Print this help';
$string['cli:migrate_start'] = 'Running legacy BN Reports migration helper...';
$string['cli:repair_ambiguous'] = 'Meeting {$a}: no usable matching callback duration (ambiguous)';
$string['cli:repair_ambiguous_count'] = 'Records without usable callback evidence: {$a}';
$string['cli:repair_apply_value'] = 'The --apply option does not accept a value.';
$string['cli:repair_changed_count'] = 'Records changed during the scan and skipped: {$a}';
$string['cli:repair_discrepancies'] = 'Supported discrepancies: {$a}';
$string['cli:repair_heading'] = 'BNX DataHub historical meeting durations';
$string['cli:repair_help'] = 'Preview or repair historical meeting durations using retained matching callbacks.

The default is read-only. Review the preview and back up the site before applying.
Each run scans current data again; records may differ from an earlier preview.
Run from the Moodle root: php public/mod/bigbluebuttonbn/extension/bnx_datahub/cli/repair_durations.php

Options:
    --apply     Apply supported corrections
    -h, --help  Print this help';
$string['cli:repair_preview'] = 'Preview only; no records were changed. Use --apply after reviewing the results.';
$string['cli:repair_result'] = 'Meeting {$a->id}: {$a->duration} -> {$a->expected} seconds ({$a->status})';
$string['cli:repair_scanned'] = 'Meetings scanned: {$a}';
$string['cli:repair_status_changed'] = 'changed';
$string['cli:repair_status_preview'] = 'preview';
$string['cli:repair_status_updated'] = 'updated';
$string['cli:repair_updated_count'] = 'Records updated: {$a}';
$string['cli:unknown_option'] = 'Unrecognised option(s): {$a}';
$string['config_enable_debug_logging'] = 'Enable debug logging';
$string['config_enable_debug_logging_desc'] = 'If enabled, the plugin writes developer debug messages for datahub processing events.';
$string['dashboard'] = 'DataHub dashboard +';
$string['dashboard:activityscore'] = 'Activity score';
$string['dashboard:allexcluded'] = 'All sessions are excluded from the dashboard. Restore a session to see its insights.';
$string['dashboard:attendance'] = 'Attendance';
$string['dashboard:attendancechart'] = 'Learner participation by session';
$string['dashboard:back'] = 'Back to dashboard';
$string['dashboard:duration'] = 'Duration';
$string['dashboard:emptytext'] = 'Meeting insights will appear here after BigBlueButton sends analytics for a completed session.';
$string['dashboard:emptytitle'] = 'No meeting insights yet';
$string['dashboard:engagementchart'] = 'Engagement activity';
$string['dashboard:excludeconfirm'] = 'Exclude this session from the dashboard and built-in attendance and engagement reports? Its data will be retained and can be restored later.';
$string['dashboard:excluded'] = 'Session excluded.';
$string['dashboard:excludedsessions'] = 'Excluded sessions ({$a})';
$string['dashboard:excludedtitle'] = 'Excluded sessions';
$string['dashboard:excludesession'] = 'Exclude session';
$string['dashboard:learner'] = 'Learner';
$string['dashboard:learnerchart'] = 'Session participation';
$string['dashboard:learners'] = 'Unique learners';
$string['dashboard:medianattendance'] = 'Median attendance';
$string['dashboard:meetings'] = 'Sessions held';
$string['dashboard:navigation'] = 'Dashboard +';
$string['dashboard:noattendees'] = 'No learner attendance was recorded for this session.';
$string['dashboard:noexcluded'] = 'There are no excluded sessions.';
$string['dashboard:overallsummary'] = 'Overall summary';
$string['dashboard:participants'] = 'Participants';
$string['dashboard:pollparticipants'] = 'Poll participants';
$string['dashboard:restore'] = 'Restore';
$string['dashboard:restored'] = 'Session restored.';
$string['dashboard:selectsession'] = 'Session';
$string['dashboard:sessionoverview'] = 'Session summary';
$string['dashboard:show'] = 'Show';
$string['dashboard:viewattendance'] = 'View attendance details';
$string['dashboard:viewengagement'] = 'View engagement details';
$string['dashboardintro'] = 'A visual summary of attendance and participation across completed sessions.';
$string['engagement'] = 'Engagement';
$string['eventexclusionchanged'] = 'Session exclusion changed';
$string['internalmeetingid'] = 'Internal meeting ID';
$string['meeting'] = 'BigBlueButton meeting';
$string['meeting:id'] = 'Meeting ID';
$string['meeting:internalmeetingid'] = 'Internal meeting ID';
$string['meeting:name'] = 'Meeting name';
$string['meeting:timeend'] = 'End time';
$string['meeting:timestart'] = 'Start time';
$string['meetingexcluded'] = 'Excluded from default reports';
$string['meetingid'] = 'Meeting ID';
$string['meetingname'] = 'Meeting name';
$string['meetings'] = 'Meetings';
$string['meetingtimeend'] = 'Meeting time end';
$string['meetingtimestart'] = 'Meeting time start';
$string['pluginname'] = 'BigBlueButton BN Experience - Data Hub';
$string['privacy:metadata'] = 'The BigBlueButton BN Experience - Data Hub plugin stores meeting attendance and engagement data.';
$string['privacy:metadata:attendeerole'] = 'The role of the user in the meeting (e.g., moderator, viewer).';
$string['privacy:metadata:attendeescore'] = 'The engagement score of the user.';
$string['privacy:metadata:bbbext_bnx_datahub_meeting'] = 'Stores BigBlueButton meeting metadata received from analytics callbacks. Webhook payloads can include user-identifying information such as moderator names.';
$string['privacy:metadata:bbbext_bnx_datahub_meeting_user'] = 'Stores user attendance and engagement data for BigBlueButton meetings.';
$string['privacy:metadata:elapsedtime'] = 'The total time the user spent in the meeting.';
$string['privacy:metadata:engchats'] = 'The number of chat messages sent by the user.';
$string['privacy:metadata:engemojis'] = 'The number of emojis used by the user.';
$string['privacy:metadata:engpollvotes'] = 'The number of poll votes submitted by the user.';
$string['privacy:metadata:engraisehand'] = 'The number of times the user raised their hand.';
$string['privacy:metadata:engtalks'] = 'The number of times the user talked.';
$string['privacy:metadata:engtalktime'] = 'The total talk time of the user in seconds.';
$string['privacy:metadata:fullname'] = 'The full name of the user as displayed in the meeting.';
$string['privacy:metadata:meeting:externalmeetingid'] = 'The external (BBB) meeting ID for the recorded meeting.';
$string['privacy:metadata:meeting:internalmeetingid'] = 'The internal (BBB) meeting ID for the recorded meeting.';
$string['privacy:metadata:meeting:meetingname'] = 'The name of the meeting as recorded by the analytics callback.';
$string['privacy:metadata:meeting:metadata'] = 'Meeting metadata captured from the analytics callback. May contain user-identifying information.';
$string['privacy:metadata:meeting:rawdata'] = 'Raw meeting payload captured from the analytics callback (with attendee details stripped before persistence).';
$string['privacy:metadata:meeting:timeend'] = 'The time the meeting ended.';
$string['privacy:metadata:meeting:timestart'] = 'The time the meeting started.';
$string['privacy:metadata:rawdata'] = 'Raw analytics data for the user.';
$string['privacy:metadata:timejoin'] = 'The time when the user joined the meeting.';
$string['privacy:metadata:timeleave'] = 'The time when the user left the meeting.';
$string['privacy:metadata:userid'] = 'The Moodle user ID of the meeting participant.';
$string['reportattendance'] = 'Attendance report +';
$string['reportengagement'] = 'Engagement report +';
$string['room'] = 'BigBlueButton room';
$string['room:id'] = 'Room ID';
$string['room:name'] = 'Room name';
$string['room:timecreated'] = 'Time created';
$string['room:timemodified'] = 'Time modified';
$string['roomid'] = 'Room ID';
$string['roomname'] = 'Room name';
$string['rooms'] = 'Rooms';
$string['roomtimecreated'] = 'Room time created';
$string['roomtimemodified'] = 'Room time modified';
$string['scheduledreportsnotavailable'] = 'Scheduled DataHub custom reports are unavailable because recipient access cannot be enforced.';
