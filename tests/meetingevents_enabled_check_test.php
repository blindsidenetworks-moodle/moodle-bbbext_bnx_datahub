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
 * Tests the meeting events prerequisite check.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\bbbext_bnx_datahub\check\meetingevents_enabled::class)]
final class meetingevents_enabled_check_test extends \advanced_testcase {
    /**
     * The status check surfaces the unmet parent-setting prerequisite.
     */
    public function test_status_check_warns_when_parent_setting_missing(): void {
        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_meetingevents_enabled', 0);
        set_config('meetingevents_enabled', 0, 'mod_bigbluebuttonbn');

        $check = new \bbbext_bnx_datahub\check\meetingevents_enabled();
        $result = $check->get_result();

        $this->assertSame(\core\check\result::WARNING, $result->get_status());
    }

    /**
     * The status check is OK when the parent setting is enabled.
     */
    public function test_status_check_ok_when_parent_setting_enabled(): void {
        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_meetingevents_enabled', 1);

        $check = new \bbbext_bnx_datahub\check\meetingevents_enabled();
        $result = $check->get_result();

        $this->assertSame(\core\check\result::OK, $result->get_status());
    }
}
