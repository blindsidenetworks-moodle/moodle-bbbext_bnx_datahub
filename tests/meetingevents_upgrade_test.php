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
 * Tests the upgrade-time meeting events contract.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('xmldb_bbbext_bnx_datahub_upgrade')]
final class meetingevents_upgrade_test extends \advanced_testcase {
    /**
     * Upgrade must NOT touch the parent meeting events setting on any savepoint.
     */
    public function test_upgrade_does_not_touch_meetingevents(): void {
        global $CFG;

        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_meetingevents_enabled', 0);
        set_config('version', 2026012201, 'bbbext_bnx_datahub');

        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/extension/bnx_datahub/db/upgrade.php');
        xmldb_bbbext_bnx_datahub_upgrade(2026012201);

        $this->assertSame(
            0,
            (int) get_config(null, 'bigbluebuttonbn_meetingevents_enabled'),
            'bbbext_bnx_datahub upgrade must not enable bigbluebuttonbn_meetingevents_enabled.'
        );
    }
}
