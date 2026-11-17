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
 * Tests the install-time meeting events contract.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('xmldb_bbbext_bnx_datahub_install')]
final class meetingevents_install_test extends \advanced_testcase {
    /**
     * Install must NOT touch the parent meeting events setting.
     */
    public function test_install_does_not_touch_meetingevents(): void {
        global $CFG;

        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_meetingevents_enabled', 0);

        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/extension/bnx_datahub/db/install.php');
        xmldb_bbbext_bnx_datahub_install();

        $this->assertSame(
            0,
            (int) get_config(null, 'bigbluebuttonbn_meetingevents_enabled'),
            'bbbext_bnx_datahub install must not enable bigbluebuttonbn_meetingevents_enabled.'
        );
    }
}
