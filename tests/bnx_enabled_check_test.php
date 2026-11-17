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
 * Tests the BNX prerequisite check.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\bbbext_bnx_datahub\check\bnx_enabled::class)]
final class bnx_enabled_check_test extends \advanced_testcase {
    /**
     * The BNX prerequisite check warns when BNX is disabled.
     */
    public function test_bnx_status_check_warns_when_bnx_disabled(): void {
        $this->resetAfterTest(true);

        set_config('version', '2026060200', 'bbbext_bnx');
        set_config('disabled', 1, 'bbbext_bnx');
        \core_plugin_manager::reset_caches();

        $check = new \bbbext_bnx_datahub\check\bnx_enabled();
        $result = $check->get_result();

        $this->assertSame(\core\check\result::WARNING, $result->get_status());
    }

    /**
     * The BNX prerequisite check warns when BNX is not installed.
     */
    public function test_bnx_status_check_warns_when_bnx_is_uninstalled(): void {
        $this->resetAfterTest(true);

        unset_config('version', 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx');
        \core\plugin_manager::reset_caches();

        $check = new \bbbext_bnx_datahub\check\bnx_enabled();
        $result = $check->get_result();

        $this->assertSame(\core\check\result::WARNING, $result->get_status());
    }

    /**
     * The BNX prerequisite check is OK when BNX is enabled.
     */
    public function test_bnx_status_check_ok_when_bnx_enabled(): void {
        $this->resetAfterTest(true);

        set_config('version', '2026060200', 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx');
        \core_plugin_manager::reset_caches();

        $check = new \bbbext_bnx_datahub\check\bnx_enabled();
        $result = $check->get_result();

        $this->assertSame(\core\check\result::OK, $result->get_status());
    }
}
