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
 * Tests the configuration observer.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversMethod(\bbbext_bnx_datahub\observer::class, 'config_log_created')]
final class config_log_observer_test extends \advanced_testcase {
    /**
     * Enabling DataHub must be reverted when BNX is disabled.
     */
    public function test_enabling_datahub_is_blocked_when_bnx_is_disabled(): void {
        $this->resetAfterTest(true);

        set_config('version', '2026060200', 'bbbext_bnx');
        set_config('disabled', 1, 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core\plugin_manager::reset_caches();

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name' => 'disabled',
                'plugin' => 'bbbext_bnx_datahub',
                'oldvalue' => '1',
                'value' => '0',
            ],
        ]);
        \bbbext_bnx_datahub\observer::config_log_created($event);

        $this->assertSame('1', get_config('bbbext_bnx_datahub', 'disabled'));
    }

    /**
     * Enabling DataHub must be reverted when BNX is not installed.
     */
    public function test_enabling_datahub_is_blocked_when_bnx_is_uninstalled(): void {
        $this->resetAfterTest(true);

        unset_config('version', 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core\plugin_manager::reset_caches();

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name' => 'disabled',
                'plugin' => 'bbbext_bnx_datahub',
                'oldvalue' => '1',
                'value' => '0',
            ],
        ]);
        \bbbext_bnx_datahub\observer::config_log_created($event);

        $this->assertSame('1', get_config('bbbext_bnx_datahub', 'disabled'));
    }
}
