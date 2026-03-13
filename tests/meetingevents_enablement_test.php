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
 * Conformance tests for meeting events auto-enable behaviour.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
final class meetingevents_enablement_test extends \advanced_testcase {
    /**
     * Install must enable meeting events.
     *
     * @covers ::xmldb_bbbext_bnx_datahub_install
     * @return void
     */
    public function test_install_enables_meetingevents(): void {
        global $CFG;

        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_meetingevents_enabled', 0);
        $this->assertSame(0, (int) get_config(null, 'bigbluebuttonbn_meetingevents_enabled'));

        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/extension/bnx_datahub/db/install.php');
        xmldb_bbbext_bnx_datahub_install();

        $this->assertSame(1, (int) get_config(null, 'bigbluebuttonbn_meetingevents_enabled'));
    }

    /**
     * Enabling bnx_datahub must enable meeting events.
     *
     * @covers \bbbext_bnx_datahub\observer::config_log_created
     * @return void
     */
    public function test_enable_event_enables_meetingevents(): void {
        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_meetingevents_enabled', 0);
        $this->assertSame(0, (int) get_config(null, 'bigbluebuttonbn_meetingevents_enabled'));

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name' => 'disabled',
                'plugin' => 'bbbext_bnx_datahub',
                'oldvalue' => '1',
                'value' => '0',
            ],
        ]);

        observer::config_log_created($event);

        $this->assertSame(1, (int) get_config(null, 'bigbluebuttonbn_meetingevents_enabled'));
    }

    /**
     * Disabling bnx_datahub must NOT touch the meeting events setting.
     *
     * @covers \bbbext_bnx_datahub\observer::config_log_created
     * @return void
     */
    public function test_disable_event_does_not_change_meetingevents(): void {
        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_meetingevents_enabled', 1);

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name' => 'disabled',
                'plugin' => 'bbbext_bnx_datahub',
                'oldvalue' => '0',
                'value' => '1',
            ],
        ]);

        observer::config_log_created($event);

        $this->assertSame(1, (int) get_config(null, 'bigbluebuttonbn_meetingevents_enabled'));
    }

    /**
     * Upgrade must backfill meeting events enablement.
     *
     * @covers ::xmldb_bbbext_bnx_datahub_upgrade
     * @return void
     */
    public function test_upgrade_enables_meetingevents(): void {
        global $CFG;

        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_meetingevents_enabled', 0);
        set_config('version', 2026012201, 'bbbext_bnx_datahub');

        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/extension/bnx_datahub/db/upgrade.php');
        xmldb_bbbext_bnx_datahub_upgrade(2026012201);

        $this->assertSame(1, (int) get_config(null, 'bigbluebuttonbn_meetingevents_enabled'));
    }
}
