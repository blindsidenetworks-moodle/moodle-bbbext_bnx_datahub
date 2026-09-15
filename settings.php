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
 * Admin settings for bbbext_bnx_datahub.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/check/bnreports_migration_pending.php');

if ($ADMIN->fulltree) {
    global $OUTPUT;

    // Surface the same prerequisite/conflict use cases as the status checks, directly on
    // the DataHub settings page, without mutating or depending on core mod_bigbluebuttonbn.
    $datahubchecks = [
        new \bbbext_bnx_datahub\check\bnreports_conflict(),
        new \bbbext_bnx_datahub\check\bnx_enabled(),
        new \bbbext_bnx_datahub\check\meetingevents_enabled(),
        new \bbbext_bnx_datahub\check\bnreports_migration_pending(),
    ];
    foreach ($datahubchecks as $datahubcheck) {
        $checkresult = $datahubcheck->get_result();
        if ($checkresult->get_status() === \core\check\result::OK) {
            continue;
        }
        $noticebody = html_writer::tag('strong', $checkresult->get_summary());
        $details = $checkresult->get_details();
        if ($details !== '') {
            $noticebody .= html_writer::div($details);
        }
        $actionlink = $datahubcheck->get_action_link();
        if ($actionlink !== null) {
            $noticebody .= html_writer::div($OUTPUT->render($actionlink));
        }
        $notificationtype = $datahubcheck->get_ref() === 'bbbext_bnx_datahub_bnreports_conflict'
            ? \core\output\notification::NOTIFY_ERROR
            : \core\output\notification::NOTIFY_WARNING;
        $settings->add(new admin_setting_description(
            'bbbext_bnx_datahub/notice_' . $datahubcheck->get_ref(),
            '',
            $OUTPUT->notification($noticebody, $notificationtype)
        ));
    }

    $settings->add(new admin_setting_configcheckbox(
        'bbbext_bnx_datahub/enable_debug_logging',
        new lang_string('config_enable_debug_logging', 'bbbext_bnx_datahub'),
        new lang_string('config_enable_debug_logging_desc', 'bbbext_bnx_datahub'),
        0
    ));
}
