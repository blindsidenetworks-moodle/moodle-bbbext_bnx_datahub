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

namespace bbbext_bnx_datahub\local\helpers;

/**
 * Helper for datahub debug logging.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debug_helper {
    /**
     * Whether debug logging is enabled for this subplugin.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return !empty(get_config('bbbext_bnx_datahub', 'enable_debug_logging'));
    }

    /**
     * Emit a DEBUG_DEVELOPER message only when subplugin debug logging is enabled.
     *
     * @param string $message
     * @return void
     */
    public static function developer(string $message): void {
        if (!self::is_enabled()) {
            return;
        }

        debugging($message, DEBUG_DEVELOPER);
    }
}
