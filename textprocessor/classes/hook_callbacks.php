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
 * Hook callbacks for the textprocessor AI Placement.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Callback to add TextProcessor initialization script before footer.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE;

        // Check if plugin is available.
        if (!utils::is_ollama_configured()) {
            return;
        }

        // Get context.
        $context = $PAGE->context;
        if (!utils::is_textprocessor_available($context)) {
            return;
        }

        // Add JS module for editor integration.
        $PAGE->requires->js_call_amd(
            'aiplacement_textprocessor/editor_button',
            'init',
            [$context->id]
        );
    }

    /**
     * Callback to add content after HTTP headers.
     *
     * @param \core\hook\output\after_http_headers $hook
     */
    public static function after_http_headers(\core\hook\output\after_http_headers $hook): void {
        // This hook is kept for potential future use.
        // Currently all initialization happens in before_footer_html_generation.
    }
}