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

namespace aiplacement_textprocessor;

/**
 * Test editor button integration.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiplacement_textprocessor\utils
 */
class editor_button_test extends \advanced_testcase {

    /**
     * Test that utils class exists and has required methods.
     */
    public function test_utils_class_exists(): void {
        $this->assertTrue(class_exists(utils::class));
        $this->assertTrue(method_exists(utils::class, 'is_textprocessor_available'));
        $this->assertTrue(method_exists(utils::class, 'is_ollama_configured'));
        $this->assertTrue(method_exists(utils::class, 'is_textprocessor_placement_action_available'));
    }

    /**
     * Test that placement class exists and has required methods.
     */
    public function test_placement_class_exists(): void {
        $this->assertTrue(class_exists(placement::class));
        $this->assertTrue(method_exists(placement::class, 'get_action_list'));
    }

    /**
     * Test placement action list.
     */
    public function test_placement_action_list(): void {
        $actions = placement::get_action_list();
        $this->assertIsArray($actions);
        $this->assertContains(\core_ai\aiactions\generate_text::class, $actions);
    }

    /**
     * Test hook_callbacks class exists.
     */
    public function test_hook_callbacks_class_exists(): void {
        $this->assertTrue(class_exists(hook_callbacks::class));
        $this->assertTrue(method_exists(hook_callbacks::class, 'before_footer_html_generation'));
    }

    /**
     * Test file_extractor class exists and has required methods.
     */
    public function test_file_extractor_class_exists(): void {
        $this->assertTrue(class_exists(file_extractor::class));
        $this->assertTrue(method_exists(file_extractor::class, 'is_supported'));
        $this->assertTrue(method_exists(file_extractor::class, 'get_supported_types'));
        $this->assertTrue(method_exists(file_extractor::class, 'extract_from_base64'));
    }

    /**
     * Test file_extractor supported types.
     */
    public function test_file_extractor_supported_types(): void {
        $supported = file_extractor::get_supported_types();
        $this->assertIsArray($supported);
        $this->assertContains('pdf', $supported);
        $this->assertContains('docx', $supported);
        $this->assertContains('txt', $supported);
    }

    /**
     * Test file_extractor is_supported method.
     */
    public function test_file_extractor_is_supported(): void {
        $this->assertTrue(file_extractor::is_supported('document.pdf'));
        $this->assertTrue(file_extractor::is_supported('document.docx'));
        $this->assertTrue(file_extractor::is_supported('document.txt'));
        $this->assertTrue(file_extractor::is_supported('document.DOCX')); // Case insensitive.
        $this->assertFalse(file_extractor::is_supported('document.jpg'));
        $this->assertFalse(file_extractor::is_supported('document.png'));
    }
}