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
 * Upgrade.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

/**
 * Perform the upgrade procedures.
 *
 * @param int $oldversion The old version number.
 * @return bool Whether the upgrade was successful.
 */
function xmldb_bbbext_bnx_datahub_upgrade($oldversion) {
    global $DB;

    require_once(__DIR__ . '/migration.php');

    if ($oldversion < 2026012202) {
        // Historical upgrade step: previously force-enabled
        // bigbluebuttonbn_meetingevents_enabled. Removed (OL-3.2.1) - sidecar
        // subplugins must not silently mutate their parent's settings.
        upgrade_plugin_savepoint(true, 2026012202, 'bbbext', 'bnx_datahub');
    }

    if ($oldversion < 2026031300) {
        // Historical upgrade step: previously corrected a wrong-scope write of
        // bigbluebuttonbn_meetingevents_enabled. Removed (OL-3.2.1).
        upgrade_plugin_savepoint(true, 2026031300, 'bbbext', 'bnx_datahub');
    }

    if ($oldversion < 2026031301) {
        // Backfill BN Reports migration for instances installed before this release.
        bbbext_bnx_datahub_migrate_bnreports_data();

        upgrade_plugin_savepoint(true, 2026031301, 'bbbext', 'bnx_datahub');
    }

    if ($oldversion < 2026060200) {
        // Release 1.0.2: no schema change. The OL-3.2.1 remediation removed the
        // historical cross-plugin set_config writes that were performed in this
        // upgrade path on previous savepoints.
        upgrade_plugin_savepoint(true, 2026060200, 'bbbext', 'bnx_datahub');
    }

    if ($oldversion < 2026061000) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('bbbext_bnx_datahub_meeting_user');

        $field = new xmldb_field('role', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'attendeerole');
        }

        $field = new xmldb_field('score', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'attendeescore');
        }

        upgrade_plugin_savepoint(true, 2026061000, 'bbbext', 'bnx_datahub');
    }

    return true;
}
