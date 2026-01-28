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
 * Tests for the room entity class.
 *
 * @package   bbbext_bnx_datahub
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \bbbext_bnx_datahub\reportbuilder\local\entities\room
 */
final class room_entity_test extends advanced_testcase {
    /**
     * Set up for each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that room entity returns correct default tables.
     */
    public function test_get_default_tables(): void {
        $entity = new room();
        $reflection = new \ReflectionClass($entity);
        $method = $reflection->getMethod('get_default_tables');
        $method->setAccessible(true);

        $tables = $method->invoke($entity);

        $this->assertIsArray($tables);
        $this->assertContains('bigbluebuttonbn', $tables);
    }

    /**
     * Test that room entity returns correct entity title.
     */
    public function test_get_default_entity_title(): void {
        $entity = new room();
        $reflection = new \ReflectionClass($entity);
        $method = $reflection->getMethod('get_default_entity_title');
        $method->setAccessible(true);

        $title = $method->invoke($entity);

        $this->assertInstanceOf(\lang_string::class, $title);
        $this->assertEquals('room', $title->get_identifier());
        $this->assertEquals('bbbext_bnx_datahub', $title->get_component());
    }

    /**
     * Test that room entity initialises correctly.
     */
    public function test_initialise(): void {
        $entity = new room();
        $entity->set_table_alias('bigbluebuttonbn', 'bbb');
        $result = $entity->initialise();

        $this->assertInstanceOf(room::class, $result);
    }

    /**
     * Test that room entity provides columns.
     */
    public function test_get_all_columns(): void {
        $entity = new room();
        $entity->set_table_alias('bigbluebuttonbn', 'bbb');
        $entity->initialise();

        $columns = $entity->get_columns();

        $this->assertNotEmpty($columns);

        // Check that expected columns exist.
        $columnnames = array_map(function ($col) {
            return $col->get_name();
        }, $columns);

        $this->assertContains('id', $columnnames);
        $this->assertContains('name', $columnnames);
        $this->assertContains('timecreated', $columnnames);
        $this->assertContains('timemodified', $columnnames);
    }

    /**
     * Test that room entity provides filters.
     */
    public function test_get_all_filters(): void {
        $entity = new room();
        $entity->set_table_alias('bigbluebuttonbn', 'bbb');
        $entity->initialise();

        $filters = $entity->get_filters();

        $this->assertNotEmpty($filters);
    }

    /**
     * Test that room name filter exists.
     */
    public function test_name_filter_exists(): void {
        $entity = new room();
        $entity->set_table_alias('bigbluebuttonbn', 'bbb');
        $entity->initialise();

        $filters = $entity->get_filters();
        $filternames = array_map(function ($filter) {
            return $filter->get_name();
        }, $filters);

        $this->assertContains('name', $filternames);
    }
}
