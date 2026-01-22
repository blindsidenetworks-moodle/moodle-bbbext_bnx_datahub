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
 * Mod form addons for the bnx_datahub extension.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx_datahub\bigbluebuttonbn;

use stdClass;

/**
 * A class for the main mod form extension.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mod_form_addons extends \mod_bigbluebuttonbn\local\extension\mod_form_addons {
    /**
     * Check if BNX is enabled.
     *
     * This sidecar should only modify the form when BNX is enabled.
     *
     * @return bool True if BNX is enabled.
     */
    private static function is_bnx_enabled(): bool {
        $enabledplugins = \core_plugin_manager::instance()->get_enabled_plugins('bbbext');
        return isset($enabledplugins['bnx']);
    }

    /**
     * Allows modules to modify the data returned by form get_data().
     *
     * @param stdClass $data passed by reference
     */
    public function data_postprocessing(stdClass &$data): void {
        // No processing needed.
    }

    /**
     * Allow module to modify the data at the pre-processing stage.
     *
     * @param array|null $defaultvalues
     */
    public function data_preprocessing(?array &$defaultvalues): void {
        if (!self::is_bnx_enabled()) {
            return;
        }
        // No preprocessing needed.
    }

    /**
     * Add custom completion rules.
     *
     * @return array Array of string IDs of added items, empty array if none.
     */
    public function add_completion_rules(): array {
        return [];
    }

    /**
     * Called during validation to check if a custom completion rule is enabled.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled(array $data): bool {
        return false;
    }

    /**
     * Form adjustments after setting data.
     *
     * @return void
     */
    public function definition_after_data(): void {
        if (!self::is_bnx_enabled()) {
            return;
        }
        // No adjustments needed.
    }

    /**
     * Add new form field definition.
     */
    public function add_fields(): void {
        if (!self::is_bnx_enabled()) {
            return;
        }
        // No form fields to add.
    }

    /**
     * Validate form and returns an array of errors indexed by field name.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation(array $data, array $files): array {
        return [];
    }
}
