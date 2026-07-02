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

use mod_bigbluebuttonbn\extension;

/**
 * Sidecar contract conformance tests.
 *
 * After OL-3.2.1 the bnx_datahub subplugin must NEVER mutate the parent
 * mod_bigbluebuttonbn `bigbluebuttonbn_meetingevents_enabled` setting through
 * its install / upgrade / observer / on_enable code paths. These tests pin
 * that contract so a regression cannot silently re-introduce the cross-plugin
 * write.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class meetingevents_enablement_test extends \advanced_testcase {
    /**
     * Install must NOT touch the parent meeting events setting.
     *
     * @covers ::xmldb_bbbext_bnx_datahub_install
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

    /**
     * Upgrade must NOT touch the parent meeting events setting on any savepoint.
     *
     * @covers ::xmldb_bbbext_bnx_datahub_upgrade
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

    /**
     * The cross-plugin observer and on_enable hook must no longer be present.
     *
     * @covers \bbbext_bnx_datahub\observer
     */
    public function test_cross_plugin_hooks_are_removed(): void {
        $this->assertFileDoesNotExist(
            __DIR__ . '/../classes/observer.php',
            'observer.php (config_log_created auto-enable) must be removed (OL-3.2.1).'
        );
        $this->assertFileDoesNotExist(
            __DIR__ . '/../classes/plugininfo_callbacks.php',
            'plugininfo_callbacks::on_enable must be removed (CC-1).'
        );
        $this->assertFileDoesNotExist(
            __DIR__ . '/../db/events.php',
            'db/events.php registering the cross-plugin observer must be removed.'
        );
    }

    /**
     * The status check surfaces the unmet parent-setting prerequisite.
     *
     * @covers \bbbext_bnx_datahub\check\meetingevents_enabled
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
     *
     * @covers \bbbext_bnx_datahub\check\meetingevents_enabled
     */
    public function test_status_check_ok_when_parent_setting_enabled(): void {
        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_meetingevents_enabled', 1);

        $check = new \bbbext_bnx_datahub\check\meetingevents_enabled();
        $result = $check->get_result();

        $this->assertSame(\core\check\result::OK, $result->get_status());
    }

    /**
     * The bnreports conflict check warns when the legacy sidecar is present and
     * DataHub has self-disabled (OL-3.2.1).
     *
     * @covers \bbbext_bnx_datahub\check\bnreports_conflict
     */
    public function test_bnreports_conflict_check_warns_when_self_disabled(): void {
        $this->resetAfterTest(true);

        set_config('version', '2024110600', 'bbbext_bnreports');
        set_config('disabled', 1, 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        $check = new \bbbext_bnx_datahub\check\bnreports_conflict();
        $result = $check->get_result();

        $this->assertSame(\core\check\result::WARNING, $result->get_status());
    }

    /**
     * DataHub cannot be manually re-enabled while the legacy BN Reports sidecar
     * remains enabled.
     */
    public function test_datahub_enable_is_blocked_while_bnreports_enabled(): void {
        $this->resetAfterTest(true);

        set_config('version', '2024110600', 'bbbext_bnreports');
        unset_config('disabled', 'bbbext_bnreports');
        set_config('disabled', 1, 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        $manager = \core_plugin_manager::resolve_plugininfo_class(extension::BBB_EXTENSION_PLUGIN_NAME);
        $result = $manager::enable_plugin('bnx_datahub', 1);

        $this->assertFalse($result);
        $this->assertSame('1', get_config('bbbext_bnx_datahub', 'disabled'));
    }

    /**
     * Re-enabling BN Reports must immediately self-disable DataHub again.
     */
    public function test_enabling_bnreports_self_disables_datahub(): void {
        $this->resetAfterTest(true);

        set_config('version', '2024110600', 'bbbext_bnreports');
        set_config('disabled', 1, 'bbbext_bnreports');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        $manager = \core_plugin_manager::resolve_plugininfo_class(extension::BBB_EXTENSION_PLUGIN_NAME);
        $result = $manager::enable_plugin('bnreports', 1);

        $this->assertTrue($result);
        $this->assertSame('1', get_config('bbbext_bnx_datahub', 'disabled'));
        $this->assertFalse(get_config('bbbext_bnreports', 'disabled'));
    }

    /**
     * The status check also warns if both sidecars are simultaneously enabled.
     *
     * @covers \bbbext_bnx_datahub\check\bnreports_conflict
     */
    public function test_bnreports_conflict_check_warns_when_both_sidecars_enabled(): void {
        $this->resetAfterTest(true);

        set_config('version', '2024110600', 'bbbext_bnreports');
        unset_config('disabled', 'bbbext_bnreports');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        $check = new \bbbext_bnx_datahub\check\bnreports_conflict();
        $result = $check->get_result();

        $this->assertSame(\core\check\result::WARNING, $result->get_status());
    }

    /**
     * The bnreports conflict check is OK when the legacy sidecar is absent
     * (OL-3.2.1).
     *
     * @covers \bbbext_bnx_datahub\check\bnreports_conflict
     */
    public function test_bnreports_conflict_check_ok_when_bnreports_absent(): void {
        $this->resetAfterTest(true);

        unset_config('version', 'bbbext_bnreports');
        set_config('disabled', 1, 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        $check = new \bbbext_bnx_datahub\check\bnreports_conflict();
        $result = $check->get_result();

        $this->assertSame(\core\check\result::OK, $result->get_status());
    }
}
