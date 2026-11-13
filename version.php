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
 * Version information for BigBlueButton BN Experience - Data Hub
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component    = 'bbbext_bnx_datahub';
$plugin->release      = '1.2-beta.1';
$plugin->version      = 2026110501;
$plugin->requires     = 2026071400; // Moodle 5.3.0 minimum.
$plugin->supported    = [503, 503];
$plugin->maturity     = MATURITY_BETA;
$plugin->dependencies = [
    // Pin to BNX 1.3 (2026110500) so the no-cross-plugin-enablement
    // contract emitted by bbbext_bnx (state_changed event) is in place
    // before bbbext_bnx_datahub installs / upgrades (CC-4).
    'bbbext_bnx'         => 2026110500,
];
