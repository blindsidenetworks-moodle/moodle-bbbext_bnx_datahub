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

namespace bbbext_bnx_datahub\reportbuilder\local\entities;

use lang_string;
use stdClass;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{date, select, text};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\{column, filter};

/**
 * Attendee entity
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class attendee extends base {
    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'bbbext_bnx_datahub_meeting_user',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('attendee', 'bbbext_bnx_datahub');
    }

    /**
     * Initialise the entity
     *
     * @return base
     */
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $attendeealias = $this->get_table_alias('bbbext_bnx_datahub_meeting_user');

        // Attendee fullname.
        $columns[] = (new column(
            'fullname',
            new lang_string('attendee:fullname', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_field("{$attendeealias}.fullname", 'fullname')
            ->add_callback(static function (?string $fullname, stdClass $attendee): string {
                if ($fullname === null) {
                    return '';
                }
                return format_text($fullname);
            });

        // Attendee role.
        $columns[] = (new column(
            'role',
            new lang_string('attendee:role', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_field("{$attendeealias}.role", 'role')
            ->set_is_sortable(true)
            ->add_callback(static function (?string $role, stdClass $attendee): string {
                if ($role === null) {
                    return '';
                }
                return get_string('attendee:role:' . $role, 'bbbext_bnx_datahub');
            });

        // Time join.
        $columns[] = (new column(
            'timejoin',
            new lang_string('attendee:timejoin', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$attendeealias}.timejoin")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        // Time leave.
        $columns[] = (new column(
            'timeleave',
            new lang_string('attendee:timeleave', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$attendeealias}.timeleave")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        // Elapsed time.
        $columns[] = (new column(
            'elapsedtime',
            new lang_string('attendee:elapsedtime', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$attendeealias}.elapsedtime")
            ->set_is_sortable(true)
            ->add_callback(function ($value) {
                return gmdate('H:i:s', intval($value));
            });

        // Engagement columns.

        // Engchats.
        $columns[] = (new column(
            'engchats',
            new lang_string('attendee:engchats', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$attendeealias}.engchats");

        // Engtalks.
        $columns[] = (new column(
            'engtalks',
            new lang_string('attendee:engtalks', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$attendeealias}.engtalks");

        // Engraisehand.
        $columns[] = (new column(
            'engraisehand',
            new lang_string('attendee:engraisehand', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$attendeealias}.engraisehand");

        // Engemojis.
        $columns[] = (new column(
            'engemojis',
            new lang_string('attendee:engemojis', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$attendeealias}.engemojis");

        // Engpollvotes.
        $columns[] = (new column(
            'engpollvotes',
            new lang_string('attendee:engpollvotes', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$attendeealias}.engpollvotes");

        // Engtalktime.
        $columns[] = (new column(
            'engtalktime',
            new lang_string('attendee:engtalktime', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$attendeealias}.engtalktime");

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        global $DB;

        $attendeealias = $this->get_table_alias('bbbext_bnx_datahub_meeting_user');

        // Attendee fullname.
        $filters[] = (new filter(
            text::class,
            'fullname',
            new lang_string('attendee:fullname', 'bbbext_bnx_datahub'),
            $this->get_entity_name(),
            $DB->sql_cast_to_char("{$attendeealias}.fullname")
        ))
            ->add_joins($this->get_joins());

        // Attendee role.
        $filters[] = (new filter(
            select::class,
            'role',
            new lang_string('attendee:role', 'bbbext_bnx_datahub'),
            $this->get_entity_name(),
            $DB->sql_cast_to_char("{$attendeealias}.role")
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(function () {
                return [
                    'viewer' => get_string('attendee:role:viewer', 'bbbext_bnx_datahub'),
                    'moderator' => get_string('attendee:role:moderator', 'bbbext_bnx_datahub'),
                ];
            });

        return $filters;
    }
}
