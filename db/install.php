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
 * Install.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

/**
 * Perform the post-install procedures.
 */
function xmldb_bbbext_bnx_datahub_install() {
    require_once(__DIR__ . '/migration.php');

    // Note: bigbluebuttonbn_meetingevents_enabled is intentionally NOT modified
    // here. As a sidecar subplugin of mod_bigbluebuttonbn, bbbext_bnx_datahub
    // must not silently flip its parent's settings. Operators are responsible
    // for enabling meeting events on the parent plugin; the
    // bbbext_bnx_datahub\check\meetingevents_enabled status check surfaces
    // the requirement (OL-3.2.1).

    // The legacy BN Reports *data* migration is intentionally NOT run here (OL-4.1.1).
    // A fresh install has no legacy data to backfill, so the heavy migration only runs
    // from db/upgrade.php for sites upgrading from a version that predates the backfill.
    // We do, however, still resolve a concurrent-install conflict with the legacy
    // sidecar: if bbbext_bnreports is installed and enabled, DataHub self-disables
    // (own namespace only) so the two sidecars never process events at the same time
    // (OL-3.2.1). This is a cheap guard, not a data migration.
    //
    // During a bulk install (e.g. a fresh site) the plugin manager's
    // installed-plugins cache was built before bbbext_bnx was installed earlier
    // in the same run, so the BNX guard would wrongly self-disable DataHub.
    // Refresh it so the guard sees the real state: BNX present and enabled on a
    // fresh site, or present but disabled when DataHub is added later.
    \core_plugin_manager::reset_caches();
    bbbext_bnx_datahub_disable_if_bnx_unavailable();
    bbbext_bnx_datahub_disable_if_bnreports_present();
}
