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
 * Excluded sessions and reversible exclusion actions.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use bbbext_bnx_datahub\local\services\exclusion_service;
use bbbext_bnx_datahub\local\services\meeting_service;

require(__DIR__ . '/../../../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('bigbluebuttonbn', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('bbbext/bnx_datahub:viewreports', $context);

$dashboardurl = new moodle_url('/mod/bigbluebuttonbn/extension/bnx_datahub/dashboard.php', ['cmid' => $cmid]);
$listurl = new moodle_url('/mod/bigbluebuttonbn/extension/bnx_datahub/excluded.php', ['cmid' => $cmid]);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_url($listurl);
$PAGE->set_title(get_string('dashboard:excludedtitle', 'bbbext_bnx_datahub'));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    $meetingid = required_param('meetingid', PARAM_INT);
    if (!in_array($action, ['exclude', 'restore'], true)) {
        throw new moodle_exception('invalidparameter');
    }
    $meeting = exclusion_service::get_meeting($cmid, $meetingid);
    if ($action === 'exclude' && !optional_param('confirmed', 0, PARAM_BOOL)) {
        $confirmurl = new moodle_url($listurl, [
            'meetingid' => $meetingid,
            'action' => 'exclude',
            'confirmed' => 1,
            'sesskey' => sesskey(),
        ]);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($meeting->meetingname, true, ['context' => $context]));
        echo $OUTPUT->confirm(
            get_string('dashboard:excludeconfirm', 'bbbext_bnx_datahub'),
            new single_button($confirmurl, get_string('dashboard:excludesession', 'bbbext_bnx_datahub'), 'post'),
            $dashboardurl
        );
        echo $OUTPUT->footer();
        exit;
    }
    exclusion_service::set_excluded($cmid, $meetingid, $action === 'exclude');
    $message = get_string($action === 'exclude' ? 'dashboard:excluded' : 'dashboard:restored', 'bbbext_bnx_datahub');
    redirect($action === 'exclude' ? $dashboardurl : $listurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('dashboard:excludedtitle', 'bbbext_bnx_datahub'));
echo html_writer::link($dashboardurl, get_string('dashboard:back', 'bbbext_bnx_datahub'));
$meetings = array_filter(meeting_service::get_all_by_cmid($cmid), static fn(stdClass $meeting): bool => (bool) $meeting->excluded);
if (!$meetings) {
    echo $OUTPUT->notification(get_string('dashboard:noexcluded', 'bbbext_bnx_datahub'), 'info');
} else {
    $canmanage = has_capability('bbbext/bnx_datahub:manageexclusions', $context);
    $table = new html_table();
    $table->head = [get_string('meetingname', 'bbbext_bnx_datahub'), get_string('date'),
        get_string('dashboard:duration', 'bbbext_bnx_datahub')];
    if ($canmanage) {
        $table->head[] = get_string('actions');
    }
    foreach ($meetings as $meeting) {
        $row = [format_string($meeting->meetingname, true, ['context' => $context]),
            userdate((int) $meeting->timestart), format_time((int) $meeting->duration)];
        if ($canmanage) {
            $restoreurl = new moodle_url($listurl, [
                'meetingid' => $meeting->id,
                'action' => 'restore',
                'sesskey' => sesskey(),
            ]);
            $row[] = $OUTPUT->single_button($restoreurl, get_string('dashboard:restore', 'bbbext_bnx_datahub'), 'post');
        }
        $table->data[] = $row;
    }
    echo html_writer::div(html_writer::table($table), 'table-responsive mt-3');
}
echo $OUTPUT->footer();
