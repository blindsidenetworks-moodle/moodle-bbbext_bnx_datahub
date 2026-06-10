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

namespace bbbext_bnx_datahub\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\entities\user as user_entity;
use bbbext_bnx_datahub\reportbuilder\local\entities\meeting;
use bbbext_bnx_datahub\reportbuilder\local\entities\room;
use bbbext_bnx_datahub\reportbuilder\local\entities\attendee;

/**
 * Engagement datasource
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class engagement extends datasource {
    /** @var string Alias for the BNX bridge table join. */
    private const BNX_ALIAS = 'bbbextbnx';

    /**
     * Return the friendly name of the report source
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('engagement', 'bbbext_bnx_datahub');
    }

    /**
     * Initialise the report
     */
    protected function initialise(): void {
        $attendeeentity = new attendee();
        $attendeealias = $attendeeentity->get_table_alias('bbbext_bnx_datahub_meeting_user');
        $this->set_main_table('bbbext_bnx_datahub_meeting_user', $attendeealias);
        $this->add_entity($attendeeentity);

        // Join the user entity for attendees.
        $userentity = new user_entity();
        $userentityalias = $userentity->get_table_alias('user');
        $userentity->add_join("
            LEFT JOIN {user} {$userentityalias}
                ON {$userentityalias}.id = {$attendeealias}.userid");
        $this->add_entity($userentity);

        // Join the meeting entity for attendees.
        $meetingentity = new meeting();
        $meetingalias = $meetingentity->get_table_alias('bbbext_bnx_datahub_meeting');
        $this->add_entity($meetingentity);
        $this->add_join("
            LEFT JOIN {bbbext_bnx_datahub_meeting} {$meetingalias}
                ON {$meetingalias}.id = {$attendeealias}.meetingid");

        // Join bbbext_bnx to get bigbluebuttonbnid.
        $bnxalias = self::BNX_ALIAS;
        $this->add_join("
            LEFT JOIN {bbbext_bnx} {$bnxalias}
                ON {$bnxalias}.id = {$meetingalias}.bnxid");

        // Join the room (bigbluebuttonbn) entity for meetings.
        $roomentity = new room();
        $roomalias = $roomentity->get_table_alias('bigbluebuttonbn');
        $this->add_entity($roomentity);
        $this->add_join("
            LEFT JOIN {bigbluebuttonbn} {$roomalias}
                ON {$roomalias}.id = {$bnxalias}.bigbluebuttonbnid");

        // Join the course entity for rooms.
        $courseentity = new course();
        $coursealias = $courseentity->get_table_alias('course');
        $this->add_entity($courseentity);
        $this->add_join("
            LEFT JOIN {course} {$coursealias}
                ON {$coursealias}.id = {$roomalias}.course");

        // Add report elements from each of the entities we added to the report.
        $this->add_all_from_entities();
    }

    /**
     * Return the default columns for the engagement report
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'course:fullname',
            'room:name',
            'meeting:id',
            'meeting:name',
            'user:fullnamewithpicturelink',
            'attendee:engchats',
            'attendee:engemojis',
            'attendee:engpollvotes',
            'attendee:engraisehand',
            'attendee:engtalks',
            'attendee:engtalktime',
        ];
    }

    /**
     * Return the default column sorting
     *
     * @return int[]
     */
    public function get_default_column_sorting(): array {
        return [
            'room:name' => SORT_ASC,
            'meeting:name' => SORT_ASC,
        ];
    }

    /**
     * Return the default filters for the report
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'course:fullname',
            'room:name',
            'meeting:name',
            'attendee:fullname',
        ];
    }

    /**
     * Return the default conditions for the engagement report
     *
     * @return array
     */
    public function get_default_conditions(): array {
        return [];
    }
}
