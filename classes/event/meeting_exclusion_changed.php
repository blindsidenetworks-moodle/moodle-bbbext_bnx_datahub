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

namespace bbbext_bnx_datahub\event;

/**
 * A meeting was excluded from, or restored to, default reports.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meeting_exclusion_changed extends \core\event\base {
    /**
     * Initialise event metadata.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'bbbext_bnx_datahub_meeting';
    }

    /**
     * Return the event name.
     *
     * @return string Event name.
     */
    public static function get_name(): string {
        return get_string('eventexclusionchanged', 'bbbext_bnx_datahub');
    }

    /**
     * Return the event description.
     *
     * @return string Event description.
     */
    public function get_description(): string {
        $state = empty($this->other['excluded']) ? 'restored' : 'excluded';
        return "The user with id '{$this->userid}' {$state} meeting '{$this->objectid}' in module '{$this->contextinstanceid}'.";
    }
}
