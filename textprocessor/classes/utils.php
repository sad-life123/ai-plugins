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

use core_ai\manager;

/**
 * AI Placement TextProcessor utils.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /**
     * Check if AI Placement TextProcessor action is available for the context.
     *
     * @param \context $context The context.
     * @param string $actionname The name of the action.
     * @param string $actionclass The class name of the action.
     * @return bool True if the action is available, false otherwise.
     */
    public static function is_textprocessor_placement_action_available(
        \context $context,
        string $actionname,
        string $actionclass
    ): bool {
        // Check if plugin is enabled.
        [$plugintype, $pluginname] = explode('_', \core_component::normalize_componentname('aiplacement_textprocessor'), 2);
        $pluginmanager = \core_plugin_manager::resolve_plugininfo_class($plugintype);
        if (!$pluginmanager::is_plugin_enabled($pluginname)) {
            return false;
        }

        // Check AI manager availability and permissions.
        $aimanager = \core\di::get(manager::class);
        if (
            has_capability("aiplacement/textprocessor:{$actionname}", $context)
            && $aimanager->is_action_available($actionclass)
            && $aimanager->is_action_enabled('aiplacement_textprocessor', $actionclass)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check if TextProcessor is available for editor integration.
     *
     * @param \context $context The context.
     * @return bool True if available.
     */
    public static function is_textprocessor_available(\context $context): bool {
        return self::is_textprocessor_placement_action_available(
            $context,
            'generate_text',
            \core_ai\aiactions\generate_text::class
        );
    }

    /**
     * Check if AI provider is configured.
     *
     * @return bool True if configured.
     */
    public static function is_ollama_configured(): bool {
        try {
            $aimanager = \core\di::get(manager::class);
            return $aimanager->is_action_available(\core_ai\aiactions\generate_text::class);
        } catch (\Exception $e) {
            return false;
        }
    }
}