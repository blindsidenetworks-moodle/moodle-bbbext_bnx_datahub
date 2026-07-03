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
 * Tests for install-time migration from BN Reports.
 *
 * @covers ::bbbext_bnx_datahub_migrate_bnreports_data
 * @covers ::bbbext_bnx_datahub_migrate_bnreports_settings
 * @covers ::bbbext_bnx_datahub_migratable_setting_keys
 * @covers ::bbbext_bnx_datahub_is_bnx_enabled
 * @covers ::bbbext_bnx_datahub_disable_if_bnx_unavailable
 * @covers ::bbbext_bnx_datahub_disable_if_bnreports_present
 * @covers ::bbbext_bnx_datahub_ensure_bnx_record
 * @covers ::bbbext_bnx_datahub_legacy_bnreports_table
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
final class install_migration_test extends \advanced_testcase {
    /**
     * Set up for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Sidecar-contract bootstrap (CC-2).
        set_config('bigbluebuttonbn_meetingevents_enabled', 1);
        $this->setAdminUser();

        require_once(__DIR__ . '/../db/migration.php');
    }

    /**
     * A fresh install (db/install.php) must not run the heavy legacy data
     * migration, but must still self-disable when the legacy sidecar is present
     * (OL-4.1.1, OL-3.2.1).
     */
    public function test_install_does_not_migrate_data_but_self_disables(): void {
        global $DB;

        require_once(__DIR__ . '/../db/install.php');

        $this->create_legacy_bnreports_tables();

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);

        $DB->insert_record(bbbext_bnx_datahub_legacy_bnreports_table('_meeting'), (object) [
            'bigbluebuttonbnid' => $bbb->id,
            'externalmeetingid' => 'ext-3001',
            'internalmeetingid' => 'int-3001',
            'meetingname' => 'Fresh install meeting',
            'timestart' => 1700000000,
            'timeend' => 1700003600,
            'duration' => 3600,
            'metadata' => null,
            'rawdata' => null,
        ]);

        set_config('version', '2024110600', 'bbbext_bnreports');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        xmldb_bbbext_bnx_datahub_install();

        // The heavy legacy data migration must NOT run at fresh install (OL-4.1.1).
        $this->assertSame(0, $DB->count_records('bbbext_bnx_datahub_meeting', ['internalmeetingid' => 'int-3001']));
        // But the conflict guard must self-disable DataHub (OL-3.2.1).
        $this->assertSame('1', get_config('bbbext_bnx_datahub', 'disabled'));
        // And the legacy sidecar namespace is never mutated.
        $this->assertFalse(get_config('bbbext_bnreports', 'disabled'));
    }

    /**
     * BN Reports legacy settings, meetings and meeting users should be copied
     * into the BNX Datahub storage layout.
     */
    public function test_migrate_bnreports_data(): void {
        global $DB;

        $this->create_legacy_bnreports_tables();

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);

        $legacymeetingid = (int) $DB->insert_record(bbbext_bnx_datahub_legacy_bnreports_table('_meeting'), (object) [
            'bigbluebuttonbnid' => $bbb->id,
            'externalmeetingid' => 'ext-1001',
            'internalmeetingid' => 'int-1001',
            'meetingname' => 'Legacy meeting',
            'timestart' => 1700000000,
            'timeend' => 1700003600,
            'duration' => 3600,
            'metadata' => json_encode(['course' => 'Legacy Course']),
            'rawdata' => json_encode(['source' => 'bnreports']),
        ]);

        $DB->insert_record(bbbext_bnx_datahub_legacy_bnreports_table('_meeting_user'), (object) [
            'meetingid' => $legacymeetingid,
            'userid' => (int) $this->getDataGenerator()->create_user()->id,
            'fullname' => 'Legacy User',
            'role' => 'viewer',
            'timejoin' => 1700000100,
            'timeleave' => 1700000400,
            'elapsedtime' => 300,
            'score' => 10,
            'engchats' => 2,
            'engtalks' => 1,
            'engraisehand' => 0,
            'engemojis' => 3,
            'engpollvotes' => 1,
            'engtalktime' => 80,
            'rawdata' => json_encode(['legacy' => true]),
        ]);

        set_config('enable_debug_logging', '1', 'bbbext_bnreports');
        set_config('sample_legacy_flag', 'enabled', 'bbbext_bnreports');
        set_config('version', '2024110600', 'bbbext_bnreports');
        // Simulate a DataHub install that has not yet recorded this allow-listed key,
        // so the migration copy path is actually exercised.
        unset_config('enable_debug_logging', 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        bbbext_bnx_datahub_migrate_bnreports_data();

        $bnx = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id], '*', MUST_EXIST);
        $meeting = $DB->get_record('bbbext_bnx_datahub_meeting', ['internalmeetingid' => 'int-1001'], '*', MUST_EXIST);

        $this->assertSame((int) $bnx->id, (int) $meeting->bnxid);
        $this->assertSame('Legacy meeting', (string) $meeting->meetingname);

        $meetinguser = $DB->get_record('bbbext_bnx_datahub_meeting_user', ['meetingid' => $meeting->id], '*', MUST_EXIST);
        $this->assertSame('Legacy User', (string) $meetinguser->fullname);
        $this->assertSame('viewer', (string) $meetinguser->attendeerole);

        // Only allow-listed keys are migrated into the DataHub namespace (OL-3.2.1).
        $this->assertSame('1', get_config('bbbext_bnx_datahub', 'enable_debug_logging'));
        // Non-allow-listed legacy keys are never copied across.
        $this->assertFalse(get_config('bbbext_bnx_datahub', 'sample_legacy_flag'));
        // DataHub self-disables (own namespace) rather than mutating bnreports (OL-3.2.1).
        $this->assertSame('1', get_config('bbbext_bnx_datahub', 'disabled'));
        $this->assertFalse(get_config('bbbext_bnreports', 'disabled'));
    }

    /**
     * When the legacy BN Reports sidecar is not installed, DataHub must not
     * self-disable and must not touch the bnreports namespace (OL-3.2.1).
     */
    public function test_no_self_disable_when_bnreports_absent(): void {
        // Ensure no legacy sidecar version is registered.
        unset_config('version', 'bbbext_bnreports');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        bbbext_bnx_datahub_disable_if_bnreports_present();

        $this->assertFalse(get_config('bbbext_bnx_datahub', 'disabled'));
        $this->assertFalse(get_config('bbbext_bnreports', 'disabled'));
    }

    /**
     * When the administrator has already disabled the legacy sidecar there is no
     * conflict, so DataHub must not self-disable (OL-3.2.1).
     */
    public function test_no_self_disable_when_bnreports_already_disabled(): void {
        set_config('version', '2024110600', 'bbbext_bnreports');
        set_config('disabled', 1, 'bbbext_bnreports');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        bbbext_bnx_datahub_disable_if_bnreports_present();

        $this->assertFalse(get_config('bbbext_bnx_datahub', 'disabled'));
    }

    /**
     * When BNX is disabled, DataHub must self-disable and leave BNX untouched.
     */
    public function test_self_disable_when_bnx_disabled(): void {
        set_config('version', '2026060200', 'bbbext_bnx');
        set_config('disabled', 1, 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        bbbext_bnx_datahub_disable_if_bnx_unavailable();

        $this->assertSame('1', get_config('bbbext_bnx_datahub', 'disabled'));
        $this->assertSame('1', get_config('bbbext_bnx', 'disabled'));
    }

    /**
     * When BNX is enabled, the BNX guard must not self-disable DataHub.
     */
    public function test_no_self_disable_when_bnx_enabled(): void {
        set_config('version', '2026060200', 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        bbbext_bnx_datahub_disable_if_bnx_unavailable();

        $this->assertFalse(get_config('bbbext_bnx_datahub', 'disabled'));
    }

    /**
     * Migration should be idempotent and not duplicate rows on reruns.
     */
    public function test_migration_is_idempotent(): void {
        global $DB;

        $this->create_legacy_bnreports_tables();

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $userid = (int) $this->getDataGenerator()->create_user()->id;

        $legacymeetingid = (int) $DB->insert_record(bbbext_bnx_datahub_legacy_bnreports_table('_meeting'), (object) [
            'bigbluebuttonbnid' => $bbb->id,
            'externalmeetingid' => 'ext-2001',
            'internalmeetingid' => 'int-2001',
            'meetingname' => 'Idempotent meeting',
            'timestart' => 1700001000,
            'timeend' => 1700004600,
            'duration' => 3600,
            'metadata' => null,
            'rawdata' => null,
        ]);

        $DB->insert_record(bbbext_bnx_datahub_legacy_bnreports_table('_meeting_user'), (object) [
            'meetingid' => $legacymeetingid,
            'userid' => $userid,
            'fullname' => 'Idempotent User',
            'role' => 'moderator',
            'timejoin' => 1700001100,
            'timeleave' => 1700001300,
            'elapsedtime' => 200,
            'score' => 15,
            'engchats' => 1,
            'engtalks' => 1,
            'engraisehand' => 1,
            'engemojis' => 1,
            'engpollvotes' => 1,
            'engtalktime' => 50,
            'rawdata' => null,
        ]);

        set_config('version', '2024110600', 'bbbext_bnreports');
        \core_plugin_manager::reset_caches();

        bbbext_bnx_datahub_migrate_bnreports_data();
        bbbext_bnx_datahub_migrate_bnreports_data();

        $meetings = $DB->count_records('bbbext_bnx_datahub_meeting', ['internalmeetingid' => 'int-2001']);
        $this->assertSame(1, (int) $meetings);

        $meeting = $DB->get_record('bbbext_bnx_datahub_meeting', ['internalmeetingid' => 'int-2001'], '*', MUST_EXIST);
        $users = $DB->count_records('bbbext_bnx_datahub_meeting_user', [
            'meetingid' => $meeting->id,
            'userid' => $userid,
        ]);
        $this->assertSame(1, (int) $users);
    }

    /**
     * Newly created BNX records should be written back to the caller cache.
     */
    public function test_ensure_bnx_record_updates_prefetch_cache(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $cache = [];

        $bnxid = bbbext_bnx_datahub_ensure_bnx_record($bbb->id, time(), $cache);

        $this->assertNotNull($bnxid);
        $this->assertArrayHasKey($bbb->id, $cache);
        $this->assertSame((int) $bnxid, (int) $cache[$bbb->id]->id);
        $this->assertSame(1, $DB->count_records('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id]));

        $this->assertSame((int) $bnxid, bbbext_bnx_datahub_ensure_bnx_record($bbb->id, time(), $cache));
        $this->assertSame(1, $DB->count_records('bbbext_bnx', ['bigbluebuttonbnid' => $bbb->id]));
    }

    /**
     * Create the legacy BN Reports tables used by the migration helper.
     */
    private function create_legacy_bnreports_tables(): void {
        global $DB;

        $dbman = $DB->get_manager();

        $table = new \xmldb_table(bbbext_bnx_datahub_legacy_bnreports_table('_meeting'));
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('bigbluebuttonbnid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('externalmeetingid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('internalmeetingid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('meetingname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timeend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('metadata', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field('rawdata', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_bigbluebuttonbnid', XMLDB_KEY_FOREIGN, ['bigbluebuttonbnid'], 'bigbluebuttonbn', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new \xmldb_table(bbbext_bnx_datahub_legacy_bnreports_table('_meeting_user'));
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('meetingid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('role', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('timejoin', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timeleave', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('elapsedtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('score', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('engchats', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('engtalks', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('engraisehand', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('engemojis', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('engpollvotes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('engtalktime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('rawdata', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key(
            'fk_meetingid',
            XMLDB_KEY_FOREIGN,
            ['meetingid'],
            bbbext_bnx_datahub_legacy_bnreports_table('_meeting'),
            ['id']
        );
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }
}
