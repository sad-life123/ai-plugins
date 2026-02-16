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

        // Load the markup for the quizgen interface.
        $params = [
            'uniqid' => uniqid('quiz_'),
            'userid' => $USER->id,
            'contextid' => $PAGE->context->id,
            'courseid' => $PAGE->course->id ?? 0,
            'ollama_status' => $ollamastatus,
            'model' => get_config('aiplacement_quizgen', 'ollama_model') ?: 'qwen2:1.5b',
            'auto_save' => get_config('aiplacement_quizgen', 'auto_save') ?: false,
            'initialtext' => '',
            'questions' => [],
        ];
        $html = $OUTPUT->render_from_template('aiplacement_quizgen/generator', $params);
        $hook->add_html($html);

        // Initialize the quizgen JS module.
        $containerid = 'quizgen-' . $params['uniqid'];
        $config = [
            'courseid' => $PAGE->course->id ?? 0,
            'contextid' => $PAGE->context->id,
            'ollama_configured' => $ollamastatus,
        ];
        
        // Add inline JS to initialize after DOM is ready.
        $PAGE->requires->js_init_code(
            "require(['aiplacement_quizgen/generator'], function(quizgen) { quizgen.init('{$containerid}', " . json_encode($config) . "); });"
        );
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
