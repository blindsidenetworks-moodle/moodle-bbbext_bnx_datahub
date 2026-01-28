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

namespace bbbext_bnx_datahub\reportbuilder\local\entities;

use advanced_testcase;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;

/**
 * Tests for the attendee entity class.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\reportbuilder\local\entities\attendee
 */
final class attendee_entity_test extends advanced_testcase {
    /**
     * Set up for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that attendee entity returns correct default tables.
     */
    public function test_get_default_tables(): void {
        $entity = new attendee();
        $reflection = new \ReflectionClass($entity);
        $method = $reflection->getMethod('get_default_tables');
        $method->setAccessible(true);

        $tables = $method->invoke($entity);

        $this->assertIsArray($tables);
        $this->assertContains('bbbext_bnx_datahub_meeting_user', $tables);
    }

    /**
     * Test that attendee entity returns correct entity title.
     */
    public function test_get_default_entity_title(): void {
        $entity = new attendee();
        $reflection = new \ReflectionClass($entity);
        $method = $reflection->getMethod('get_default_entity_title');
        $method->setAccessible(true);

        $title = $method->invoke($entity);

        $this->assertInstanceOf(\lang_string::class, $title);
        $this->assertEquals('attendee', $title->get_identifier());
        $this->assertEquals('bbbext_bnx_datahub', $title->get_component());
    }

    /**
     * Test that attendee entity initialises correctly.
     */
    public function test_initialise(): void {
        $entity = new attendee();
        $entity->set_table_alias('bbbext_bnx_datahub_meeting_user', 'mu');
        $result = $entity->initialise();

        $this->assertInstanceOf(attendee::class, $result);
    }

    /**
     * Test that attendee entity provides columns.
     */
    public function test_get_all_columns(): void {
        $entity = new attendee();
        $entity->set_table_alias('bbbext_bnx_datahub_meeting_user', 'mu');
        $entity->initialise();

        $columns = $entity->get_columns();

        $this->assertNotEmpty($columns);

        // Check that expected columns exist.
        $columnnames = array_map(function ($col) {
            return $col->get_name();
        }, $columns);

        $this->assertContains('fullname', $columnnames);
        $this->assertContains('role', $columnnames);
        $this->assertContains('timejoin', $columnnames);
        $this->assertContains('timeleave', $columnnames);
        $this->assertContains('elapsedtime', $columnnames);
    }

    /**
     * Test that attendee entity provides engagement columns.
     */
    public function test_get_engagement_columns(): void {
        $entity = new attendee();
        $entity->set_table_alias('bbbext_bnx_datahub_meeting_user', 'mu');
        $entity->initialise();

        $columns = $entity->get_columns();
        $columnnames = array_map(function ($col) {
            return $col->get_name();
        }, $columns);

        // Check engagement-specific columns.
        $this->assertContains('engchats', $columnnames);
        $this->assertContains('engtalks', $columnnames);
        $this->assertContains('engraisehand', $columnnames);
        $this->assertContains('engemojis', $columnnames);
        $this->assertContains('engpollvotes', $columnnames);
        $this->assertContains('engtalktime', $columnnames);
    }

    /**
     * Test that attendee entity provides filters.
     */
    public function test_get_all_filters(): void {
        $entity = new attendee();
        $entity->set_table_alias('bbbext_bnx_datahub_meeting_user', 'mu');
        $entity->initialise();

        $filters = $entity->get_filters();

        $this->assertNotEmpty($filters);
    }

    /**
     * Test that attendee entity provides role filter.
     */
    public function test_role_filter_exists(): void {
        $entity = new attendee();
        $entity->set_table_alias('bbbext_bnx_datahub_meeting_user', 'mu');
        $entity->initialise();

        $filters = $entity->get_filters();
        $filternames = array_map(function ($filter) {
            return $filter->get_name();
        }, $filters);

        $this->assertContains('role', $filternames);
    }
}
