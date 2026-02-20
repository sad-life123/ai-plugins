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

namespace aiplacement_textprocessor\external;

/**
 * Test process external API.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiplacement_textprocessor\external\process
 */
class process_test extends \advanced_testcase {

    /**
     * Test that process class exists and has required methods.
     */
    public function test_process_class_exists(): void {
        $this->assertTrue(class_exists(process::class));
        $this->assertTrue(method_exists(process::class, 'execute'));
        $this->assertTrue(method_exists(process::class, 'execute_parameters'));
        $this->assertTrue(method_exists(process::class, 'execute_returns'));
    }

    /**
     * Test execute_parameters returns correct structure.
     */
    public function test_execute_parameters(): void {
        $params = process::execute_parameters();
        $this->assertInstanceOf(\core_external\external_function_parameters::class, $params);
        
        $keys = array_keys($params->keys);
        $this->assertContains('contextid', $keys);
        $this->assertContains('content', $keys);
        $this->assertContains('filename', $keys);
    }

    /**
     * Test execute_returns returns correct structure.
     */
    public function test_execute_returns(): void {
        $returns = process::execute_returns();
        $this->assertInstanceOf(\core_external\external_function_parameters::class, $returns);
        
        $keys = array_keys($returns->keys);
        $this->assertContains('success', $keys);
        $this->assertContains('html', $keys);
        $this->assertContains('message', $keys);
    }

    /**
     * Test execute with empty content.
     */
    public function test_execute_empty_content(): void {
        $this->resetAfterTest();
        
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        
        $result = process::execute($context->id, '', '');
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
        $this->assertEquals('', $result['html']);
    }

    /**
     * Test execute with whitespace content.
     */
    public function test_execute_whitespace_content(): void {
        $this->resetAfterTest();
        
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        
        $result = process::execute($context->id, '   ', '');
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['message']);
    }
}