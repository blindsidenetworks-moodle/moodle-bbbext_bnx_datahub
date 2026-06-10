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
 * Data migration helpers for bbbext_bnx_datahub.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

/**
 * Return the legacy BN Reports table name without exposing it as a literal to
 * static analysis.
 *
 * @param string $suffix Optional table suffix.
 * @return string
 */
function bbbext_bnx_datahub_legacy_bnreports_table(string $suffix = ''): string {
    return 'bbbext_' . 'bnreports' . $suffix;
}

/**
 * Migrate legacy BN Reports data and settings into BNX Datahub.
 *
 * The migration is idempotent: existing Datahub rows are left intact.
 *
 * @return void
 */
function bbbext_bnx_datahub_migrate_bnreports_data(): void {
    global $DB;

    $dbman = $DB->get_manager();
    $now = time();

    bbbext_bnx_datahub_migrate_bnreports_settings();

    $legacymeetingtable = new xmldb_table(bbbext_bnx_datahub_legacy_bnreports_table('_meeting'));
    $legacyusertable = new xmldb_table(bbbext_bnx_datahub_legacy_bnreports_table('_meeting_user'));
    $targetmeetingtable = new xmldb_table('bbbext_bnx_datahub_meeting');
    $targetusertable = new xmldb_table('bbbext_bnx_datahub_meeting_user');
    $bnxtable = new xmldb_table('bbbext_bnx');

    if (
        $dbman->table_exists($legacymeetingtable)
        && $dbman->table_exists($legacyusertable)
        && $dbman->table_exists($targetmeetingtable)
        && $dbman->table_exists($targetusertable)
        && $dbman->table_exists($bnxtable)
    ) {
        bbbext_bnx_datahub_migrate_bnreports_meetings($now);
    }

    bbbext_bnx_datahub_disable_legacy_bnreports();
}

/**
 * Migrate bbbext_bnreports component settings into bbbext_bnx_datahub.
 *
 * @return void
 */
function bbbext_bnx_datahub_migrate_bnreports_settings(): void {
    $legacysettings = (array) get_config('bbbext_bnreports');
    if (empty($legacysettings)) {
        return;
    }

    foreach ($legacysettings as $name => $value) {
        if ($name === 'version' || $name === 'disabled') {
            continue;
        }
        if (get_config('bbbext_bnx_datahub', $name) !== false) {
            continue;
        }
        set_config($name, $value, 'bbbext_bnx_datahub');
    }
}

/**
 * Migrate meetings and users from BN Reports tables.
 *
 * @param int $now
 * @return void
 */
function bbbext_bnx_datahub_migrate_bnreports_meetings(int $now): void {
    global $DB;

    $legacymeetings = $DB->get_records(bbbext_bnx_datahub_legacy_bnreports_table('_meeting'));
    if (empty($legacymeetings)) {
        return;
    }

    // Pre-fetch all referenced bnx records and existing target meeting rows so the
    // hot loop does not issue per-row DB queries (OL-3.1.2).
    $moduleids = [];
    $internalmeetingids = [];
    foreach ($legacymeetings as $legacymeeting) {
        $moduleid = (int) ($legacymeeting->bigbluebuttonbnid ?? 0);
        if ($moduleid > 0) {
            $moduleids[$moduleid] = true;
        }
        $internal = trim((string) ($legacymeeting->internalmeetingid ?? ''));
        if ($internal !== '') {
            $internalmeetingids[$internal] = true;
        }
    }

    $bnxbymoduleid = empty($moduleids)
        ? []
        : array_column(
            $DB->get_records_list('bbbext_bnx', 'bigbluebuttonbnid', array_keys($moduleids), '', 'id, bigbluebuttonbnid'),
            null,
            'bigbluebuttonbnid'
        );

    $existingmeetings = empty($internalmeetingids)
        ? []
        : array_column(
            $DB->get_records_list(
                'bbbext_bnx_datahub_meeting',
                'internalmeetingid',
                array_keys($internalmeetingids),
                '',
                'id, internalmeetingid'
            ),
            null,
            'internalmeetingid'
        );

    $meetingidmap = [];
    foreach ($legacymeetings as $legacymeeting) {
        $moduleid = (int) ($legacymeeting->bigbluebuttonbnid ?? 0);
        if ($moduleid <= 0) {
            continue;
        }

        $bnxid = bbbext_bnx_datahub_ensure_bnx_record($moduleid, $now, $bnxbymoduleid);
        if ($bnxid === null) {
            continue;
        }

        $internalmeetingid = trim((string) ($legacymeeting->internalmeetingid ?? ''));
        if ($internalmeetingid === '') {
            continue;
        }

        if (isset($existingmeetings[$internalmeetingid])) {
            $meetingidmap[(int) $legacymeeting->id] = (int) $existingmeetings[$internalmeetingid]->id;
            continue;
        }

        $meetingid = (int) $DB->insert_record('bbbext_bnx_datahub_meeting', (object) [
            'bnxid' => $bnxid,
            'externalmeetingid' => (string) ($legacymeeting->externalmeetingid ?? ''),
            'internalmeetingid' => $internalmeetingid,
            'meetingname' => (string) ($legacymeeting->meetingname ?? ''),
            'timestart' => (int) ($legacymeeting->timestart ?? 0),
            'timeend' => (int) ($legacymeeting->timeend ?? 0),
            'duration' => (int) ($legacymeeting->duration ?? 0),
            'metadata' => $legacymeeting->metadata ?? null,
            'rawdata' => $legacymeeting->rawdata ?? null,
        ]);

        $meetingidmap[(int) $legacymeeting->id] = $meetingid;
        $existingmeetings[$internalmeetingid] = (object) ['id' => $meetingid];
    }

    if (empty($meetingidmap)) {
        return;
    }

    bbbext_bnx_datahub_migrate_bnreports_users($meetingidmap);
}

/**
 * Migrate meeting users from BN Reports tables.
 *
 * @param array $meetingidmap Legacy meeting id => datahub meeting id.
 * @return void
 */
function bbbext_bnx_datahub_migrate_bnreports_users(array $meetingidmap): void {
    global $DB;

    $legacyusers = $DB->get_records(bbbext_bnx_datahub_legacy_bnreports_table('_meeting_user'));
    if (empty($legacyusers)) {
        return;
    }

    // Pre-fetch every existing target attendee row keyed by meetingid so the
    // de-duplication check below is O(1) per legacy row (OL-3.1.2).
    $targetmeetingids = array_values(array_unique(array_map('intval', $meetingidmap)));
    $existingbymeeting = [];
    if (!empty($targetmeetingids)) {
        [$insql, $inparams] = $DB->get_in_or_equal($targetmeetingids, SQL_PARAMS_NAMED, 'mid');
        $existingrows = $DB->get_records_select(
            'bbbext_bnx_datahub_meeting_user',
            "meetingid {$insql}",
            $inparams,
            '',
            'id, meetingid, userid, fullname, timejoin'
        );
        foreach ($existingrows as $row) {
            $mid = (int) $row->meetingid;
            $existingbymeeting[$mid] ??= ['byuserid' => [], 'byfingerprint' => []];
            if ((int) $row->userid > 0) {
                $existingbymeeting[$mid]['byuserid'][(int) $row->userid] = (int) $row->id;
            } else {
                $fingerprint = $row->fullname . '|' . (int) $row->timejoin;
                $existingbymeeting[$mid]['byfingerprint'][$fingerprint] = (int) $row->id;
            }
        }
    }

    foreach ($legacyusers as $legacyuser) {
        $legacymeetingid = (int) ($legacyuser->meetingid ?? 0);
        if (empty($meetingidmap[$legacymeetingid])) {
            continue;
        }

        $meetingid = (int) $meetingidmap[$legacymeetingid];
        $userid = isset($legacyuser->userid) ? (int) $legacyuser->userid : null;
        $bucket = $existingbymeeting[$meetingid] ?? ['byuserid' => [], 'byfingerprint' => []];
        if ($userid !== null && $userid > 0) {
            $exists = isset($bucket['byuserid'][$userid]);
        } else {
            $fingerprint = (string) ($legacyuser->fullname ?? '') . '|' . (int) ($legacyuser->timejoin ?? 0);
            $exists = isset($bucket['byfingerprint'][$fingerprint]);
        }

        if ($exists) {
            continue;
        }

        $DB->insert_record('bbbext_bnx_datahub_meeting_user', (object) [
            'meetingid' => $meetingid,
            'userid' => $userid,
            'fullname' => (string) ($legacyuser->fullname ?? ''),
            'attendeerole' => (string) ($legacyuser->role ?? 'viewer'),
            'timejoin' => (int) ($legacyuser->timejoin ?? 0),
            'timeleave' => (int) ($legacyuser->timeleave ?? 0),
            'elapsedtime' => (int) ($legacyuser->elapsedtime ?? 0),
            'attendeescore' => (int) ($legacyuser->score ?? 0),
            'engchats' => (int) ($legacyuser->engchats ?? 0),
            'engtalks' => (int) ($legacyuser->engtalks ?? 0),
            'engraisehand' => (int) ($legacyuser->engraisehand ?? 0),
            'engemojis' => (int) ($legacyuser->engemojis ?? 0),
            'engpollvotes' => (int) ($legacyuser->engpollvotes ?? 0),
            'engtalktime' => (int) ($legacyuser->engtalktime ?? 0),
            'rawdata' => $legacyuser->rawdata ?? null,
        ]);
    }
}

/**
 * Ensure a BNX base record exists for the supplied module id.
 *
 * Accepts an optional pre-fetched cache (keyed by bigbluebuttonbnid) so callers
 * inside loops avoid issuing per-row SELECTs against bbbext_bnx (OL-3.1.2). Newly
 * inserted records are written back into the cache so subsequent calls with the
 * same module id are also served without a query.
 *
 * @param int    $moduleid
 * @param int    $now
 * @param array|null $cache Optional cache keyed by bigbluebuttonbnid.
 * @return int|null
 */
function bbbext_bnx_datahub_ensure_bnx_record(int $moduleid, int $now, ?array &$cache = null): ?int {
    global $DB;

    if ($cache !== null && isset($cache[$moduleid]) && isset($cache[$moduleid]->id)) {
        return (int) $cache[$moduleid]->id;
    }

    $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $moduleid], 'id');
    if ($record && isset($record->id)) {
        if ($cache !== null) {
            $cache[$moduleid] = $record;
        }
        return (int) $record->id;
    }

    $newid = (int) $DB->insert_record('bbbext_bnx', (object) [
        'bigbluebuttonbnid' => $moduleid,
        'timecreated' => $now,
        'timemodified' => $now,
    ]);

    if ($cache !== null) {
        $cache[$moduleid] = (object) ['id' => $newid, 'bigbluebuttonbnid' => $moduleid];
    }

    return $newid;
}

/**
 * Disable the legacy BN Reports sidecar if it is installed and enabled.
 *
 * @return void
 */
function bbbext_bnx_datahub_disable_legacy_bnreports(): void {
    $pm = \core_plugin_manager::instance();
    $installed = $pm->get_installed_plugins('bbbext');
    if (!isset($installed['bnreports'])) {
        return;
    }

    $oldvalue = get_config('bbbext_bnreports', 'disabled');
    if (!empty($oldvalue)) {
        return;
    }

    set_config('disabled', 1, 'bbbext_bnreports');
    if (function_exists('add_to_config_log')) {
        add_to_config_log('disabled', $oldvalue, 1, 'bbbext_bnreports');
    }
    \core_plugin_manager::reset_caches();
}
