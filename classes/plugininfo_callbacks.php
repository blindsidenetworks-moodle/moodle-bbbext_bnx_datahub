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
 * Callbacks invoked by the parent plugin when this sidecar is enabled or disabled.
 *
 * The parent plugin (bbbext_bnx) discovers this class via the convention
 * `\<plugin>\plugininfo_callbacks` and calls the appropriate method.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class plugininfo_callbacks {
    /**
     * Called when bnx_datahub is enabled.
     *
     * Ensures the meeting events setting is active so that BigBlueButton
     * activity data can be collected.
     *
     * @return void
     */
    public static function on_enable(): void {
        set_config('bigbluebuttonbn_meetingevents_enabled', 1);
    }
}
