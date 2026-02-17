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

namespace aiplacement_quizgen;

use core_ai\manager;

/**
 * AI Placement QuizGen utils.
 *
 * @package    aiplacement_quizgen
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Check if quizgen is available for the context.
     * Shows button regardless of Ollama availability - error handling done at usage time.
     *
     * @param \context $context The context.
     * @return bool True if quizgen is available, false otherwise.
     */
    public static function is_quizgen_available(\context $context): bool {
        // Check if plugin is enabled.
        if (!get_config('aiplacement_quizgen', 'enabled')) {
            return false;
        }

        // Check if user has capability.
        return has_capability('aiplacement/quizgen:generate', $context);
    }

    /**
     * Check if the plugin should show in edit mode only.
     *
     * @return bool
     */
    public static function show_in_edit_mode_only(): bool {
        return (bool)get_config('aiplacement_quizgen', 'show_in_edit_mode');
    }

    /**
     * Get minimum course depth for showing the plugin.
     *
     * @return int
     */
    public static function get_min_course_depth(): int {
        return (int)get_config('aiplacement_quizgen', 'min_course_depth') ?? 0;
    }

    /**
     * Get available actions for the context.
     * Shows buttons based on capabilities - Ollama check happens at usage time.
     *
     * @param \context $context The context.
     * @return array Array of available actions.
     */
    public static function get_actions_available(\context $context): array {
        $actions = [];

        // Show button if user has capability - Ollama check is done at usage time.
        if (self::is_quizgen_available($context)) {
            $actions[] = [
                'name' => 'quizgen',
                'title' => get_string('pluginname', 'aiplacement_quizgen'),
                'icon' => 'fa-question-circle',
            ];
        }

        return $actions;
    }

    /**
     * Check if Ollama is configured (Moodle AI provider or local settings).
     *
     * @return bool
     */
    public static function is_ollama_configured(): bool {
        global $DB;

        // Check Moodle AI provider first.
        try {
            if ($DB->get_manager()->table_exists('ai_provider_instances')) {
                $provider = $DB->get_record('ai_provider_instances', [
                    'provider' => 'aiprovider_ollama',
                    'enabled' => 1
                ], 'id');
                if (!empty($provider)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // AI table doesn't exist yet.
        }

        // Check local settings fallback.
        $ollamaurl = get_config('aiplacement_quizgen', 'ollama_url');
        return !empty($ollamaurl);
    }

    /**
     * Check if AI Placement QuizGen action is available for the context.
     *
     * @param \context $context The context.
     * @param string $actionname The name of the action.
     * @param string $actionclass The class name of the action.
     * @return bool True if the action is available, false otherwise.
     */
    public static function is_quizgen_placement_action_available(
        \context $context,
        string $actionname,
        string $actionclass
    ): bool {
        // Check capability first.
        if (!has_capability("aiplacement/quizgen:{$actionname}", $context)) {
            return false;
        }

        // Check if plugin is enabled.
        [$plugintype, $pluginname] = explode('_', \core_component::normalize_componentname('aiplacement_quizgen'), 2);
        $pluginmanager = \core_plugin_manager::resolve_plugininfo_class($plugintype);
        if (!$pluginmanager::is_plugin_enabled($pluginname)) {
            return false;
        }

        // Check AI manager - but don't fail if AI plugin not installed.
        try {
            $aimanager = \core\di::get(manager::class);
            if (
                $aimanager->is_action_available($actionclass)
                && $aimanager->is_action_enabled('aiplacement_quizgen', $actionclass)
            ) {
                return true;
            }
        } catch (\Exception $e) {
            // AI plugin not installed - still allow if capabilities match.
        }

        // Allow if we have local Ollama settings.
        if (self::is_ollama_configured()) {
            return true;
        }

        return false;
    }
}
