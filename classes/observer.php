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

namespace bbbext_bnx_datahub;

/**
 * Event observer callbacks for DataHub.
 *
 * @package    bbbext_bnx_datahub
 * @copyright  2026 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Prevent DataHub from remaining enabled when BNX is unavailable.
     *
     * @param \core\event\config_log_created $event
     * @return void
     */
    public static function config_log_created(\core\event\config_log_created $event): void {
        $other = $event->other ?? [];

        if (
            ($other['name'] ?? '') !== 'disabled'
                || ($other['plugin'] ?? '') !== 'bbbext_bnx_datahub'
                || (int)($other['value'] ?? 0) === 1
        ) {
            return;
        }

        require_once(__DIR__ . '/../db/migration.php');
        if (bbbext_bnx_datahub_is_bnx_enabled()) {
            return;
        }

        bbbext_bnx_datahub_disable_if_bnx_unavailable();
        \core\notification::error(get_string('check_bnx_enabled_enable_error', 'bbbext_bnx_datahub'));
    }

    /**
     * Self-disable DataHub when BNX publishes a disabled transition.
     *
     * DataHub never toggles BNX; it only writes its own disabled flag in
     * response to BNX's public lifecycle event.
     *
     * @param \bbbext_bnx\event\state_changed $event
     * @return void
     */
    public static function bnx_state_changed(\bbbext_bnx\event\state_changed $event): void {
        if (!empty($event->other['enabled'])) {
            return;
        }

        require_once(__DIR__ . '/../db/migration.php');
        bbbext_bnx_datahub_disable_if_bnx_unavailable();
    }
}
