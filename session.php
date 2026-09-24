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
 * Session overview fragment for the DataHub activity dashboard.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$meetingid = required_param('meetingid', PARAM_INT);
$cm = get_coursemodule_from_id('bigbluebuttonbn', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('bbbext/bnx_datahub:viewreports', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bigbluebuttonbn/extension/bnx_datahub/session.php', [
    'cmid' => $cm->id,
    'meetingid' => $meetingid,
]));

$data = \bbbext_bnx_datahub\local\services\dashboard_service::get_by_cmid((int) $cm->id, $meetingid);
$dashboard = new \bbbext_bnx_datahub\output\dashboard($data, $cm->id);

echo $OUTPUT->render_from_template('bbbext_bnx_datahub/session', $dashboard->export_session_for_template());
