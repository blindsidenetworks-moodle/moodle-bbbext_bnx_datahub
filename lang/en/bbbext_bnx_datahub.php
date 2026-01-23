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

$string['pluginname'] = 'BigBlueButton BN Experience - Data Hub';
$string['privacy:metadata'] = 'The BigBlueButton BN Experience - Data Hub plugin stores meeting attendance and engagement data.';
$string['privacy:metadata:bbbext_bnx_datahub_meeting_user'] = 'Stores user attendance and engagement data for BigBlueButton meetings.';
$string['privacy:metadata:userid'] = 'The Moodle user ID of the meeting participant.';
$string['privacy:metadata:fullname'] = 'The full name of the user as displayed in the meeting.';
$string['privacy:metadata:role'] = 'The role of the user in the meeting (e.g., moderator, viewer).';
$string['privacy:metadata:timejoin'] = 'The time when the user joined the meeting.';
$string['privacy:metadata:timeleave'] = 'The time when the user left the meeting.';
$string['privacy:metadata:elapsedtime'] = 'The total time the user spent in the meeting.';
$string['privacy:metadata:score'] = 'The engagement score of the user.';
$string['privacy:metadata:engchats'] = 'The number of chat messages sent by the user.';
$string['privacy:metadata:engtalks'] = 'The number of times the user talked.';
$string['privacy:metadata:engraisehand'] = 'The number of times the user raised their hand.';
$string['privacy:metadata:engemojis'] = 'The number of emojis used by the user.';
$string['privacy:metadata:engpollvotes'] = 'The number of poll votes submitted by the user.';
$string['privacy:metadata:engtalktime'] = 'The total talk time of the user in seconds.';
$string['privacy:metadata:rawdata'] = 'Raw analytics data for the user.';

// Capabilities.
$string['bnx_datahub:viewreports'] = 'View BigBlueButton BNX reports';

// Report entities.
$string['attendee'] = 'BigBlueButton Attendee';
$string['entity:attendee'] = 'Attendee';
$string['entity:meeting'] = 'Meeting';
$string['entity:room'] = 'Room';

// Attendee entity columns and filters.
$string['attendees'] = 'Attendees';
$string['attendee:fullname'] = 'Attendee full name';
$string['attendee:role'] = 'Role';
$string['attendee:role:moderator'] = 'Moderator';
$string['attendee:role:viewer'] = 'Viewer';
$string['attendee:timejoin'] = 'Time joined';
$string['attendee:timeleave'] = 'Time left';
$string['attendee:elapsedtime'] = 'Elapsed time';
$string['attendee:score'] = 'Score';
$string['attendee:engchats'] = 'Chats';
$string['attendee:engtalks'] = 'Talks';
$string['attendee:engraisehand'] = 'Raised hand';
$string['attendee:engemojis'] = 'Emojis';
$string['attendee:engpollvotes'] = 'Poll votes';
$string['attendee:engtalktime'] = 'Talk time';

// Meeting entity columns and filters.
$string['meeting'] = 'BigBlueButton Meeting';
$string['meetings'] = 'Meetings';
$string['meeting:id'] = 'Meeting ID';
$string['meeting:internalmeetingid'] = 'Internal meeting ID';
$string['meeting:name'] = 'Meeting name';
$string['meeting:timestart'] = 'Start time';
$string['meeting:timeend'] = 'End time';
$string['meetingid'] = 'Meeting ID';
$string['internalmeetingid'] = 'Internal meeting ID';
$string['meetingname'] = 'Meeting name';
$string['meetingtimestart'] = 'Meeting time start';
$string['meetingtimeend'] = 'Meeting time end';

// Room entity columns and filters.
$string['room'] = 'BigBlueButton Room';
$string['rooms'] = 'Rooms';
$string['room:id'] = 'Room ID';
$string['room:name'] = 'Room name';
$string['room:timecreated'] = 'Time created';
$string['room:timemodified'] = 'Time modified';
$string['roomid'] = 'Room ID';
$string['roomname'] = 'Room name';
$string['roomtimecreated'] = 'Room time created';
$string['roomtimemodified'] = 'Room time modified';

// Report names.
$string['attendancereport'] = 'Attendance report';
$string['engagementreport'] = 'Engagement report';
$string['engagement'] = 'Engagement';
$string['reportattendance'] = 'Attendance report';
$string['reportengagement'] = 'Engagement report';

// Navigation links.
$string['attendance'] = 'Attendance';
$string['view_report_attendance'] = 'Attendance';
$string['view_report_engagement'] = 'Engagement';
