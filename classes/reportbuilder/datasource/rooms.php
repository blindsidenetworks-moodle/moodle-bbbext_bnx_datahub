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
use bbbext_bnx_datahub\reportbuilder\local\entities\room;

/**
 * Rooms datasource.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class rooms extends datasource {
    /**
     * Return the friendly name of the report source
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('rooms', 'bbbext_bnx_datahub');
    }

    /**
     * Initialise the report
     */
    protected function initialise(): void {
        $roomentity = new room();
        $roomalias = $roomentity->get_table_alias('bigbluebuttonbn');
        $this->set_main_table('bigbluebuttonbn', $roomalias);
        $this->add_entity($roomentity);

        // Join the course entity for rooms.
        $courseentity = new course();
        $coursealias = $courseentity->get_table_alias('course');
        $this->add_entity($courseentity);
        $this->add_join("
            LEFT JOIN {course} {$coursealias}
                ON {$coursealias}.id = {$roomalias}.course");

        $this->add_all_from_entities();
    }

    /**
     * Return the default columns
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'course:fullname',
            'room:id',
            'room:name',
            'room:timecreated',
            'room:timemodified',
        ];
    }

    /**
     * Return the default column sorting
     *
     * @return int[]
     */
    public function get_default_column_sorting(): array {
        return [
            'course:fullname' => SORT_ASC,
            'room:name' => SORT_ASC,
        ];
    }

    /**
     * Return the default filters
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'course:fullname',
            'room:name',
        ];
    }

    /**
     * Return the default conditions
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }
}
