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
use core_reportbuilder\local\filters\{boolean_select, date, text};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\{column, filter};

/**
 * Meeting entity
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class meeting extends base {
    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'bbbext_bnx_datahub_meeting',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('meeting', 'bbbext_bnx_datahub');
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
        $meetingalias = $this->get_table_alias('bbbext_bnx_datahub_meeting');

        // Meeting ID.
        $columns[] = (new column(
            'id',
            new lang_string('meetingid', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$meetingalias}.id", 'id')
            ->set_is_sortable(true);

        // Meeting Internal ID.
        $columns[] = (new column(
            'internalmeetingid',
            new lang_string('internalmeetingid', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_field("{$meetingalias}.internalmeetingid", 'internalmeetingid')
            ->add_callback(static function (?string $id, stdClass $meeting): string {
                if ($id === null) {
                    return '';
                }
                // Short identifier: use format_string with an explicit context (OL-3.2.4).
                return format_string($id, true, ['context' => \context_system::instance()]);
            });

        // Meeting name.
        $columns[] = (new column(
            'name',
            new lang_string('meetingname', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_field("{$meetingalias}.meetingname", 'meetingname')
            ->add_callback(static function (?string $name, stdClass $meeting): string {
                if ($name === null) {
                    return '';
                }
                // Short identifier: use format_string with an explicit context (OL-3.2.4).
                return format_string($name, true, ['context' => \context_system::instance()]);
            });

        // Time start.
        $columns[] = (new column(
            'timestart',
            new lang_string('meetingtimestart', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$meetingalias}.timestart")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        // Time end.
        $columns[] = (new column(
            'timeend',
            new lang_string('meetingtimeend', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$meetingalias}.timeend")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        $columns[] = (new column(
            'excluded',
            new lang_string('meetingexcluded', 'bbbext_bnx_datahub'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_BOOLEAN)
            ->add_field("{$meetingalias}.excluded", 'excluded')
            ->set_is_sortable(true)
            ->add_callback([format::class, 'boolean_as_text']);

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        global $DB;

        $meetingalias = $this->get_table_alias('bbbext_bnx_datahub_meeting');

        // Meeting ID.
        $filters[] = (new filter(
            text::class,
            'id',
            new lang_string('meetingid', 'bbbext_bnx_datahub'),
            $this->get_entity_name(),
            $DB->sql_cast_to_char("{$meetingalias}.id")
        ))
            ->add_joins($this->get_joins());

        // Meeting name.
        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('meetingname', 'bbbext_bnx_datahub'),
            $this->get_entity_name(),
            $DB->sql_cast_to_char("{$meetingalias}.meetingname")
        ))
            ->add_joins($this->get_joins());

        // Meeting time start.
        $filters[] = (new filter(
            date::class,
            'timestart',
            new lang_string('meetingtimestart', 'bbbext_bnx_datahub'),
            $this->get_entity_name(),
            $DB->sql_cast_to_char("{$meetingalias}.timestart")
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_RANGE,
                date::DATE_PREVIOUS,
                date::DATE_CURRENT,
            ]);

        $filters[] = (new filter(
            boolean_select::class,
            'excluded',
            new lang_string('meetingexcluded', 'bbbext_bnx_datahub'),
            $this->get_entity_name(),
            "{$meetingalias}.excluded"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
