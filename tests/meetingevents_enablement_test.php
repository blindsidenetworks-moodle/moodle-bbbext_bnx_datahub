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
     * The legacy cross-plugin auto-enable hook must remain removed while the
     * local DataHub enablement guard may observe its own config event.
     */
    #[\PHPUnit\Framework\Attributes\CoversNothing]
    public function test_legacy_cross_plugin_auto_enable_hook_is_removed(): void {
        $this->assertFileDoesNotExist(
            __DIR__ . '/../classes/plugininfo_callbacks.php',
            'plugininfo_callbacks::on_enable must be removed (CC-1).'
        );

        $eventsfile = __DIR__ . '/../db/events.php';
        $this->assertFileExists($eventsfile);
        $this->assertStringNotContainsString(
            'plugininfo_callbacks',
            (string) file_get_contents($eventsfile),
            'DataHub must not restore the old plugininfo_callbacks cross-plugin auto-enable observer.'
        );
    }
}
