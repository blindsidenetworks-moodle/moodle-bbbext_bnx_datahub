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
 * Tests the BNX state observer.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
#[\PHPUnit\Framework\Attributes\CoversMethod(\bbbext_bnx_datahub\observer::class, 'bnx_state_changed')]
final class bnx_state_observer_test extends \advanced_testcase {
    /**
     * Disabling BNX must make DataHub self-disable through BNX's public event.
     */
    public function test_bnx_disable_event_self_disables_datahub(): void {
        $this->resetAfterTest(true);

        if (!class_exists(\bbbext_bnx\event\state_changed::class)) {
            $this->markTestSkipped('bbbext_bnx is not available in this test environment.');
        }

        set_config('version', '2026060200', 'bbbext_bnx');
        set_config('disabled', 1, 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx_datahub');
        \core_plugin_manager::reset_caches();

        $event = \bbbext_bnx\event\state_changed::create([
            'context' => \context_system::instance(),
            'other' => ['enabled' => false],
        ]);
        \bbbext_bnx_datahub\observer::bnx_state_changed($event);

        $this->assertSame('1', get_config('bbbext_bnx_datahub', 'disabled'));
    }
}
