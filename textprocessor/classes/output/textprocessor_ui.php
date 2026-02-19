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

namespace aiplacement_textprocessor\output;

use aiplacement_textprocessor\utils;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;

/**
 * Output handler for the textprocessor AI Placement.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class textprocessor_ui {

    /**
     * Bootstrap the textprocessor UI.
     *
     * @param before_footer_html_generation $hook
     */
    public static function load_textprocessor_ui(before_footer_html_generation $hook): void {
        global $PAGE, $OUTPUT, $USER;

        // Preflight checks.
        if (!self::preflight_checks()) {
            return;
        }

        // Get consistent uniqid.
        $uniqid = uniqid('tp_');

        // Load the drawer template with textprocessor content inside.
        $params = [
            'uniqid' => $uniqid,
            'userid' => $USER->id,
            'contextid' => $PAGE->context->id,
            'ollama_status' => true,
            'config' => json_encode([
                'contextid' => $PAGE->context->id,
            ]),
        ];
        $html = $OUTPUT->render_from_template('aiplacement_textprocessor/drawer', $params);
        
        $hook->add_html($html);
    }

    /**
     * Determine if we should be loading a single button or a dropdown.
     *
     * @param after_http_headers $hook
     */
    public static function action_buttons_handler(after_http_headers $hook): void {
        // TextProcessor uses TinyMCE integration, no buttons on course page.
    }

    /**
     * Preflight checks to determine if the textprocessor UI should be loaded.
     *
     * @return bool
     */
    private static function preflight_checks(): bool {
        global $PAGE;
        if (during_initial_install()) {
            return false;
        }
        if (!get_config('aiplacement_textprocessor', 'version')) {
            return false;
        }
        if (in_array($PAGE->pagelayout, ['maintenance', 'print', 'redirect', 'embedded'])) {
            return false;
        }
        // Check we are in the right context - course or module context.
        if (!in_array($PAGE->context->contextlevel, [CONTEXT_COURSE, CONTEXT_MODULE])) {
            return false;
        }

        // Check if the user has permission to use the textprocessor.
        return has_capability('aiplacement/textprocessor:generate_text', $PAGE->context);
    }
}