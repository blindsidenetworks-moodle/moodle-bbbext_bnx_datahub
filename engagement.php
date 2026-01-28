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
 * Engagement report page.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

require(__DIR__ . '/../../../../config.php');

$cmid = optional_param('cmid', 0, PARAM_INT);

// Check if this is being accessed from a module context.
if ($cmid) {
    $cm = get_coursemodule_from_id('bigbluebuttonbn', $cmid, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $bigbluebuttonbn = $DB->get_record('bigbluebuttonbn', ['id' => $cm->instance], '*', MUST_EXIST);
    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    $PAGE->set_context($context);
    $PAGE->set_cm($cm);
    $PAGE->set_url(new moodle_url('/mod/bigbluebuttonbn/extension/bnx_datahub/engagement.php', ['cmid' => $cmid]));
    $PAGE->set_title(format_string($bigbluebuttonbn->name) . ' - ' . get_string('engagementreport', 'bbbext_bnx_datahub'));
    $PAGE->set_heading(format_string($course->fullname));
} else {
    // Site-wide report.
    require_login();
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/mod/bigbluebuttonbn/extension/bnx_datahub/engagement.php'));
    $PAGE->set_title(get_string('engagementreport', 'bbbext_bnx_datahub'));
    $PAGE->set_heading(get_string('engagementreport', 'bbbext_bnx_datahub'));
}

// Check capability.
require_capability('bbbext/bnx_datahub:viewreports', $context);

// Create the report.
$bigbluebuttonbnid = isset($bigbluebuttonbn) ? $bigbluebuttonbn->id : 0;
$report = \bbbext_bnx_datahub\reportbuilder\system_report_factory::create(
    \bbbext_bnx_datahub\reportbuilder\local\systemreports\engagement::class,
    ['bigbluebuttonbnid' => $bigbluebuttonbnid],
    $context
);

// Initialize the nav label override (for BNX branding in secondary nav).
if ($cmid) {
    $navlabel = get_string('navlabel', 'bbbext_bnx');
    $PAGE->requires->js_call_amd('bbbext_bnx/overridenav', 'init');
}

echo $OUTPUT->header();

// Output the hidden span for nav label override.
if ($cmid) {
    echo '<span class="bbbext-bnx-navlabel" data-label="' . s($navlabel) . '" hidden></span>';
}

echo $report->output();
echo $OUTPUT->footer();
