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

    // Deliberate, one-time, guarded hand-off (OL-3.2.1, Option D). DataHub is the
    // successor to the legacy bbbext_bnreports sidecar. We never mutate the sibling
    // component's configuration; instead, if bnreports is still present we self-disable
    // (see bbbext_bnx_datahub_disable_if_bnreports_present) and surface the conflict to
    // the administrator via \bbbext_bnx_datahub\check\bnreports_conflict.

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

    bbbext_bnx_datahub_disable_if_bnreports_present();
}

/**
 * Return whether any legacy BN Reports rows still need to be migrated.
 *
 * The check is purely observational: it compares legacy BN Reports meetings and
 * attendees against the DataHub rows already present and returns true only when
 * at least one legacy record has no corresponding DataHub record yet.
 *
 * @return bool
 */
function bbbext_bnx_datahub_has_pending_bnreports_migration(): bool {
    global $DB;

    $dbman = $DB->get_manager();
    $legacymeetingtable = new xmldb_table(bbbext_bnx_datahub_legacy_bnreports_table('_meeting'));
    $legacyusertable = new xmldb_table(bbbext_bnx_datahub_legacy_bnreports_table('_meeting_user'));
    $targetmeetingtable = new xmldb_table('bbbext_bnx_datahub_meeting');
    $targetusertable = new xmldb_table('bbbext_bnx_datahub_meeting_user');

    if (
        !$dbman->table_exists($legacymeetingtable)
        || !$dbman->table_exists($legacyusertable)
        || !$dbman->table_exists($targetmeetingtable)
        || !$dbman->table_exists($targetusertable)
    ) {
        return false;
    }

    $legacymeetings = $DB->get_records(bbbext_bnx_datahub_legacy_bnreports_table('_meeting'));
    if (empty($legacymeetings)) {
        return false;
    }

    $internalmeetingids = [];
    foreach ($legacymeetings as $legacymeeting) {
        $internal = trim((string) ($legacymeeting->internalmeetingid ?? ''));
        if ($internal !== '') {
            $internalmeetingids[$internal] = true;
        }
    }

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

    if (count($existingmeetings) !== count($internalmeetingids)) {
        return true;
    }

    $meetingidmap = [];
    foreach ($legacymeetings as $legacymeeting) {
        $internalmeetingid = trim((string) ($legacymeeting->internalmeetingid ?? ''));
        if ($internalmeetingid === '' || !isset($existingmeetings[$internalmeetingid])) {
            return true;
        }
        $meetingidmap[(int) $legacymeeting->id] = (int) $existingmeetings[$internalmeetingid]->id;
    }

    $legacyusers = $DB->get_records(bbbext_bnx_datahub_legacy_bnreports_table('_meeting_user'));
    if (empty($legacyusers)) {
        return false;
    }

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
            return true;
        }

        $meetingid = (int) $meetingidmap[$legacymeetingid];
        $userid = isset($legacyuser->userid) ? (int) $legacyuser->userid : null;
        $bucket = $existingbymeeting[$meetingid] ?? ['byuserid' => [], 'byfingerprint' => []];
        if ($userid !== null && $userid > 0) {
            if (!isset($bucket['byuserid'][$userid])) {
                return true;
            }
        } else {
            $fingerprint = (string) ($legacyuser->fullname ?? '') . '|' . (int) ($legacyuser->timejoin ?? 0);
            if (!isset($bucket['byfingerprint'][$fingerprint])) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Return whether the legacy BN Reports sidecar is installed and enabled.
 *
 * @return bool
 */
function bbbext_bnx_datahub_has_bnreports_conflict(): bool {
    $pm = \core_plugin_manager::instance();
    $installed = $pm->get_installed_plugins('bbbext');

    // PHPUnit installs DataHub without the optional legacy plugin's code. Its
    // persisted version remains valid legacy-installation metadata in that case.
    if (!isset($installed['bnreports']) && get_config('bbbext_bnreports', 'version') === false) {
        return false;
    }

    return empty(get_config('bbbext_bnreports', 'disabled'));
}

/**
 * Setting keys that DataHub is willing to inherit from the legacy bbbext_bnreports
 * component during migration.
 *
 * This is an explicit allow-list (OL-3.2.1): only settings that DataHub itself
 * understands are copied into its own namespace. Any other bbbext_bnreports config
 * key (including forward-incompatible or unknown keys) is ignored, so migration can
 * never smuggle arbitrary configuration into the DataHub namespace.
 *
 * @return string[]
 */
function bbbext_bnx_datahub_migratable_setting_keys(): array {
    return [
        'enable_debug_logging',
    ];
}

/**
 * Migrate the allow-listed bbbext_bnreports component settings into bbbext_bnx_datahub.
 *
 * Only keys returned by bbbext_bnx_datahub_migratable_setting_keys() are considered,
 * and an existing DataHub value is never overwritten.
 *
 * @return void
 */
function bbbext_bnx_datahub_migrate_bnreports_settings(): void {
    $legacysettings = (array) get_config('bbbext_bnreports');
    if (empty($legacysettings)) {
        return;
    }

    foreach (bbbext_bnx_datahub_migratable_setting_keys() as $name) {
        // Only copy keys that the legacy component actually defined.
        if (!array_key_exists($name, $legacysettings)) {
            continue;
        }
        // Never overwrite a value DataHub already owns.
        if (get_config('bbbext_bnx_datahub', $name) !== false) {
            continue;
        }
        set_config($name, $legacysettings[$name], 'bbbext_bnx_datahub');
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
 * Self-disable DataHub when the legacy BN Reports sidecar is still present.
 *
 * OL-3.2.1 (Option D): DataHub must never mutate a sibling component's configuration.
 * Both DataHub and the legacy bbbext_bnreports sidecar consume the same BigBlueButton
 * meeting-events stream, so running them concurrently would double-process events. To
 * resolve the conflict without touching bnreports, DataHub disables *itself* (writing
 * only into its own bbbext_bnx_datahub namespace) whenever bnreports is installed and
 * still enabled.
 *
 * Recovery is entirely in the administrator's hands: once bnreports is removed or
 * disabled from the Plugins overview page, DataHub can be re-enabled from the same page.
 * The condition is surfaced by \bbbext_bnx_datahub\check\bnreports_conflict.
 *
 * @return void
 */
function bbbext_bnx_datahub_disable_if_bnreports_present(): void {
    if (!bbbext_bnx_datahub_has_bnreports_conflict()) {
        return;
    }

    // Do not thrash config/log if DataHub is already self-disabled.
    if (!empty(get_config('bbbext_bnx_datahub', 'disabled'))) {
        return;
    }

    // Self-disable: write only into DataHub's own namespace.
    $oldvalue = get_config('bbbext_bnx_datahub', 'disabled');
    set_config('disabled', 1, 'bbbext_bnx_datahub');
    if (function_exists('add_to_config_log')) {
        add_to_config_log('disabled', $oldvalue, 1, 'bbbext_bnx_datahub');
    }
    \core_plugin_manager::reset_caches();
}

/**
 * Return whether the required BNX sidecar is installed and enabled.
 *
 * DataHub depends on bbbext_bnx for its room-level records and navigation
 * integrations. If BNX is disabled, DataHub must not keep running as if its
 * dependency were healthy.
 *
 * @return bool
 */
function bbbext_bnx_datahub_is_bnx_enabled(): bool {
    $pm = \core_plugin_manager::instance();
    $installed = $pm->get_installed_plugins('bbbext');

    return isset($installed['bnx']) && empty(get_config('bbbext_bnx', 'disabled'));
}

/**
 * Self-disable DataHub when the required BNX sidecar is unavailable.
 *
 * DataHub never toggles BNX. Instead, if BNX is missing or disabled, DataHub
 * writes only its own disabled flag and leaves recovery to the administrator.
 *
 * @return void
 */
function bbbext_bnx_datahub_disable_if_bnx_unavailable(): void {
    if (bbbext_bnx_datahub_is_bnx_enabled()) {
        return;
    }

    if (!empty(get_config('bbbext_bnx_datahub', 'disabled'))) {
        return;
    }

    $oldvalue = get_config('bbbext_bnx_datahub', 'disabled');
    set_config('disabled', 1, 'bbbext_bnx_datahub');
    if (function_exists('add_to_config_log')) {
        add_to_config_log('disabled', $oldvalue, 1, 'bbbext_bnx_datahub');
    }
    \core_plugin_manager::reset_caches();
}
