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

namespace aiplacement_quizgen\output;

use aiplacement_quizgen\utils;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;

/**
 * Output handler for the quizgen AI Placement.
 *
 * @package    aiplacement_quizgen
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizgen_ui {

    /**
     * Store the generated uniqid to ensure consistency between UI and buttons.
     * @var string|null
     */
    private static $currentUniqid = null;

    /**
     * Get or generate a consistent uniqid for this request.
     *
     * @return string
     */
    private static function get_uniqid(): string {
        if (self::$currentUniqid === null) {
            self::$currentUniqid = uniqid('quiz_');
        }
        return self::$currentUniqid;
    }

    /**
     * Bootstrap the quizgen UI.
     *
     * @param before_footer_html_generation $hook
     */
    public static function load_quizgen_ui(before_footer_html_generation $hook): void {
        global $PAGE, $OUTPUT, $USER;

        // Preflight checks.
        if (!self::preflight_checks()) {
            return;
        }

        // Check Ollama status.
        $ollamastatus = utils::is_ollama_configured();

        // Get consistent uniqid.
        $uniqid = self::get_uniqid();

        // Load the drawer template with quizgen content inside.
        $params = [
            'uniqid' => $uniqid,
            'userid' => $USER->id,
            'contextid' => $PAGE->context->id,
            'courseid' => $PAGE->course->id ?? 0,
            'ollama_status' => $ollamastatus,
            'model' => get_config('aiplacement_quizgen', 'ollama_model') ?: 'qwen2:1.5b',
            'config' => json_encode([
                'courseid' => $PAGE->course->id ?? 0,
                'contextid' => $PAGE->context->id,
                'ollama_configured' => $ollamastatus,
            ]),
        ];
        $html = $OUTPUT->render_from_template('aiplacement_quizgen/drawer', $params);
        
        $hook->add_html($html);
    }

    /**
     * Determine if we should be loading a single button or a dropdown.
     *
     * @param after_http_headers $hook
     */
    public static function action_buttons_handler(after_http_headers $hook): void {
        global $PAGE, $OUTPUT;

        // Preflight checks.
        if (!self::preflight_checks()) {
            return;
        }

        $actions['actions'] = utils::get_actions_available($PAGE->context);

        // No actions available.
        if (empty($actions['actions'])) {
            return;
        }

        // Use consistent uniqid for the quizgen container that will be toggled.
        $actions['uniqid'] = self::get_uniqid();

        if (count($actions['actions']) > 1) {
            $actions['isdropdown'] = true;
        }

        $html = $OUTPUT->render_from_template('aiplacement_quizgen/actions', $actions);
        $hook->add_html($html);
    }

    /**
     * Preflight checks to determine if the quizgen UI should be loaded.
     *
     * @return bool
     */
    private static function preflight_checks(): bool {
        global $PAGE;
        if (during_initial_install()) {
            return false;
        }
        if (!get_config('aiplacement_quizgen', 'version')) {
            return false;
        }
        if (in_array($PAGE->pagelayout, ['maintenance', 'print', 'redirect', 'embedded'])) {
            return false;
        }
        // Check we are in the right context - course context.
        if ($PAGE->context->contextlevel != CONTEXT_COURSE) {
            return false;
        }

        // QuizGen should only show in edit mode (can be disabled in settings).
        if (utils::show_in_edit_mode_only() && !$PAGE->user_is_editing()) {
            return false;
        }

        // Check if the user has permission to use the quizgen.
        return utils::is_quizgen_available($PAGE->context);
    }
}
