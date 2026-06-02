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

declare(strict_types=1);

namespace bbbext_bnx_datahub\reportbuilder\local\systemreports;

use lang_string;
use core_reportbuilder\system_report;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\entities\user as user_entity;
use bbbext_bnx_datahub\reportbuilder\local\entities\attendee;
use bbbext_bnx_datahub\reportbuilder\local\entities\meeting;
use bbbext_bnx_datahub\reportbuilder\local\entities\room;

/**
 * Engagement report.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class engagement extends system_report {
    /**
     * Initialise the report.
     */
    protected function initialise(): void {
        // Define report structure first so filters can be applied.
        $this->setup_report_structure();

        // Determine the BigBlueButton instance ID.
        $bigbluebuttonbnid = $this->get_parameter('bigbluebuttonbnid', 0, PARAM_INT);

        // Compute default filters.
        $defaults = $this->get_default_filter_values();
        if (!empty($bigbluebuttonbnid)) {
            $defaults['room:id_operator'] = \core_reportbuilder\local\filters\select::EQUAL_TO;
            $defaults['room:id_value'] = (string) $bigbluebuttonbnid;
        }

        // Only set defaults on initial (non-AJAX) loads; do not override user-applied filters.
        if (!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
            $this->set_filter_values($defaults);
        }
    }

    /**
     * Sets up the report structure with entities and joins.
     */
    private function setup_report_structure(): void {
        // Define the main entity.
        $attendeeentity = new attendee();
        $attendeealias = $attendeeentity->get_table_alias('bbbext_bnx_datahub_meeting_user');
        $this->set_main_table('bbbext_bnx_datahub_meeting_user', $attendeealias);
        $this->add_entity($attendeeentity);

        $userentity = new user_entity();
        $userentityalias = $userentity->get_table_alias('user');
        $userentity->add_join("LEFT JOIN {user} {$userentityalias} ON {$userentityalias}.id = {$attendeealias}.userid");
        $this->add_entity($userentity);

        $meetingentity = new meeting();
        $meetingalias = $this->get_unique_table_alias('bbbext_bnx_datahub_meeting', 0);
        $this->add_entity($meetingentity);
        $this->add_join(
            "LEFT JOIN {bbbext_bnx_datahub_meeting} {$meetingalias} ON {$meetingalias}.id = {$attendeealias}.meetingid"
        );

        // Join bbbext_bnx to get bigbluebuttonbnid.
        $bnxalias = $this->get_unique_table_alias('bbbext_bnx', 1);
        $this->add_join("LEFT JOIN {bbbext_bnx} {$bnxalias} ON {$bnxalias}.id = {$meetingalias}.bnxid");

        $roomentity = new room();
        $roomalias = $this->get_unique_table_alias('bigbluebuttonbn', 2);
        $this->add_entity($roomentity);
        $this->add_join("LEFT JOIN {bigbluebuttonbn} {$roomalias} ON {$roomalias}.id = {$bnxalias}.bigbluebuttonbnid");

        $courseentity = new course();
        $coursealias = $this->get_unique_table_alias('course', 3);
        $this->add_entity($courseentity);
        $this->add_join("LEFT JOIN {course} {$coursealias} ON {$coursealias}.id = {$roomalias}.course");

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();
        $this->set_downloadable(true);
    }

    /**
     * Validates access to view this report.
     *
     * Enforces the plugin's `bbbext/bnx_datahub:viewreports` capability against the
     * report's bound context (module context when invoked from an activity, system
     * context when invoked site-wide). Replaces the previous `return true` gate.
     *
     * @return bool
     */
    protected function can_view(): bool {
        $context = $this->get_context() ?? \context_system::instance();
        return has_capability('bbbext/bnx_datahub:viewreports', $context);
    }

    /**
     * Adds the columns we want to display in the report.
     */
    protected function add_columns(): void {
        $columns = [
            'course:coursefullnamewithlink',
            'room:id',
            'room:name',
            'meeting:id',
            'meeting:name',
            'user:fullnamewithpicturelink',
            'attendee:engchats',
            'attendee:engtalks',
            'attendee:engraisehand',
            'attendee:engemojis',
            'attendee:engpollvotes',
            'attendee:engtalktime',
        ];

        $this->add_columns_from_entities($columns);

        // Replace the column name with just "Course".
        $this->get_column('course:coursefullnamewithlink')->set_title(new lang_string('course'));

        // Default sorting.
        $this->set_initial_sort_column('meeting:name', SORT_ASC);
    }

    /**
     * Adds the filters we want to display in the report.
     */
    protected function add_filters(): void {
        $filters = [
            'course:fullname',
            'room:id',
            'room:name',
            'meeting:id',
            'meeting:name',
            'meeting:timestart',
            'attendee:fullname',
        ];

        $this->add_filters_from_entities($filters);
    }

    /**
     * Add the system report actions.
     */
    protected function add_actions(): void {
    }

    /**
     * Set default filter values.
     *
     * @return array
     */
    protected function get_default_filter_values(): array {
        $bigbluebuttonbnid = 0;
        // Prefer the module context when available (also works in AJAX).
        $ctx = $this->get_context();
        if ($ctx && $ctx->contextlevel === CONTEXT_MODULE && !empty($ctx->instanceid)) {
            $cm = \get_coursemodule_from_id('bigbluebuttonbn', $ctx->instanceid, 0, false, IGNORE_MISSING);
            if ($cm && !empty($cm->instance)) {
                $bigbluebuttonbnid = (int)$cm->instance;
            }
        }
        // Fall back to the constructor parameter rather than reading the request directly,
        // so this report class is not coupled to the global request state (OL-4.1.3).
        if (!$bigbluebuttonbnid) {
            $bigbluebuttonbnid = (int) $this->get_parameter('bigbluebuttonbnid', 0, PARAM_INT);
        }

        if ($bigbluebuttonbnid) {
            return [
                'room:id_operator' => \core_reportbuilder\local\filters\select::EQUAL_TO,
                'room:id_value' => (string)$bigbluebuttonbnid,
            ];
        }

        return [];
    }
}
