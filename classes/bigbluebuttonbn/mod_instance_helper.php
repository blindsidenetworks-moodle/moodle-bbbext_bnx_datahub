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

/**
 * Mod instance helper for the bnx_datahub extension.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx_datahub\bigbluebuttonbn;

use stdClass;

/**
 * Helper class for managing BigBlueButton instance lifecycle events.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mod_instance_helper extends \mod_bigbluebuttonbn\local\extension\mod_instance_helper {
    /**
     * Runs any processes that must occur when a BigBlueButton instance is added.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButton form data
     * @return void
     */
    public function add_instance(stdClass $bigbluebuttonbn): void {
        // Handle instance creation.
    }

    /**
     * Runs any processes that must occur when a BigBlueButton instance is updated.
     *
     * @param stdClass $bigbluebuttonbn BigBlueButton form data
     * @return void
     */
    public function update_instance(stdClass $bigbluebuttonbn): void {
        // Handle instance update.
    }

    /**
     * Runs any processes that must occur when a BigBlueButton instance is deleted.
     *
     * @param int $id The BigBlueButton instance id
     * @return void
     */
    public function delete_instance(int $id): void {
        // Handle instance deletion.
    }
}
