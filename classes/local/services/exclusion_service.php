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

namespace bbbext_bnx_datahub\local\services;

/**
 * Manages reversible dashboard exclusions.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exclusion_service {
    /**
     * Find a meeting in the given activity after checking the write capability.
     *
     * @param int $cmid Activity course module ID.
     * @param int $meetingid Meeting ID.
     * @return \stdClass Meeting record.
     */
    public static function get_meeting(int $cmid, int $meetingid): \stdClass {
        $context = \context_module::instance($cmid);
        require_capability('bbbext/bnx_datahub:manageexclusions', $context);
        foreach (meeting_service::get_all_by_cmid($cmid) as $meeting) {
            if ((int) $meeting->id === $meetingid) {
                return $meeting;
            }
        }
        throw new \moodle_exception('invalidrecord', 'error');
    }

    /**
     * Change exclusion state without deleting meeting or attendee data.
     *
     * @param int $cmid Activity course module ID.
     * @param int $meetingid Meeting ID.
     * @param bool $excluded Whether the meeting is excluded.
     * @return bool Whether the state changed.
     */
    public static function set_excluded(int $cmid, int $meetingid, bool $excluded): bool {
        global $DB;

        $meeting = self::get_meeting($cmid, $meetingid);
        if ((bool) $meeting->excluded === $excluded) {
            return false;
        }
        $DB->set_field('bbbext_bnx_datahub_meeting', 'excluded', (int) $excluded, ['id' => $meetingid]);
        \bbbext_bnx_datahub\event\meeting_exclusion_changed::create([
            'context' => \context_module::instance($cmid),
            'objectid' => $meetingid,
            'other' => ['excluded' => $excluded],
        ])->trigger();
        return true;
    }
}
