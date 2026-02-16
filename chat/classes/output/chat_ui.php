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

namespace aiplacement_chat\output;

use aiplacement_chat\utils;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;

/**
 * Output handler for the chat AI Placement.
 *
 * @package    aiplacement_chat
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chat_ui {

    /**
     * Bootstrap the chat UI.
     *
     * @param before_footer_html_generation $hook
     */
    public static function load_chat_ui(before_footer_html_generation $hook): void {
        global $PAGE, $OUTPUT, $USER;

        // Preflight checks.
        if (!self::preflight_checks()) {
            return;
        }

        // Check Ollama status.
        $ollamastatus = utils::is_ollama_configured();

        // Load the markup for the chat interface.
        $params = [
            'uniqid' => uniqid('chat_'),
            'userid' => $USER->id,
            'contextid' => $PAGE->context->id,
            'courseid' => $PAGE->course->id ?? 0,
            'ollama_status' => $ollamastatus,
            'config' => json_encode([
                'ollama_url' => get_config('aiplacement_chat', 'ollama_url') ?: 'http://localhost:11434',
                'ollama_model' => get_config('aiplacement_chat', 'ollama_model') ?: 'qwen2:1.5b',
                'ollama_configured' => $ollamastatus,
            ]),
        ];
        $html = $OUTPUT->render_from_template('aiplacement_chat/chat', $params);
        $hook->add_html($html);

        // Initialize the chat JS module.
        $containerid = 'coursechat-' . $params['uniqid'];
        $config = [
            'courseid' => $PAGE->course->id ?? 0,
            'contextid' => $PAGE->context->id,
            'ollama_configured' => $ollamastatus,
        ];
        
        // Add inline JS to initialize after DOM is ready.
        $PAGE->requires->js_init_code(
            "require(['aiplacement_chat/chat'], function(chat) { chat.init('{$containerid}', " . json_encode($config) . "); });"
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

        // Check edit mode setting.
        if (utils::show_in_edit_mode_only() && !$PAGE->user_is_editing()) {
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

        $html = $OUTPUT->render_from_template('aiplacement_chat/actions', $actions);
        $hook->add_html($html);
    }

    /**
     * Preflight checks to determine if the chat UI should be loaded.
     *
     * @return bool
     */
    private static function preflight_checks(): bool {
        global $PAGE;
        if (during_initial_install()) {
            return false;
        }
        if (!get_config('aiplacement_chat', 'version')) {
            return false;
        }
        if (in_array($PAGE->pagelayout, ['maintenance', 'print', 'redirect', 'embedded'])) {
            return false;
        }
        // Check we are in the right context - course context.
        if ($PAGE->context->contextlevel != CONTEXT_COURSE) {
            return false;
        }

        // Check if the user has permission to use the chat.
        return utils::is_chat_available($PAGE->context);
    }
}
