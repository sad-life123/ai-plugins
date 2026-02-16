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

use core_ai\admin\admin_settingspage_provider;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create settings page for the plugin.
    $settings = new admin_settingspage_provider(
        'aiplacement_textprocessor',
        get_string('pluginname', 'aiplacement_textprocessor'),
        'moodle/site:config',
        true, // This is an AI placement.
    );

    // Check if Ollama provider is configured (only if AI plugin is installed).
    $ollamaenabled = false;
    try {
        global $DB;
        if ($DB->get_manager()->table_exists('ai_provider_instances')) {
            $ollamaenabled = $DB->record_exists('ai_provider_instances', [
                'provider' => 'aiprovider_ollama',
                'enabled' => 1
            ]);
        }
    } catch (\Exception $e) {
        // AI plugin not installed yet.
    }

    // ============================================
    // 🦙 OLLAMA SETTINGS (Fallback)
    // ============================================
    $ollama_desc = $ollamaenabled
        ? 'Ollama provider is configured in AI → Providers. These settings are used as fallback if provider is disabled.'
        : 'Configure Ollama URL here or set up Ollama provider in AI → Providers for better management.';

    $settings->add(new admin_setting_heading('ollama_heading',
        '🦙 Ollama Settings (Fallback)',
        $ollama_desc
    ));

    $settings->add(new admin_setting_configtext(
        'aiplacement_textprocessor/ollama_url',
        'Ollama Server URL',
        'Ollama API address (default: http://localhost:11434)',
        'http://localhost:11434',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'aiplacement_textprocessor/ollama_model',
        'Text Processing Model',
        'Recommended: llama3.1, qwen2.5, mistral',
        'qwen2:1.5b',
        PARAM_TEXT
    ));

    // ============================================
    // ⚙️ VISIBILITY SETTINGS
    // ============================================
    $settings->add(new admin_setting_heading('visibility_heading',
        '⚙️ Visibility Settings',
        'Control when the text processor is available'
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiplacement_textprocessor/show_in_edit_mode',
        'Show in Edit Mode Only',
        'Only show the text processor when editing the course',
        1
    ));

    $settings->add(new admin_setting_configselect(
        'aiplacement_textprocessor/min_course_depth',
        'Minimum Course Depth',
        'Show only at this course section depth or deeper (0 = show everywhere)',
        0,
        [
            0 => 'Show everywhere',
            1 => 'Section level (1)',
            2 => 'Subsection level (2)',
            3 => 'Deep level (3+)'
        ]
    ));

    // Add the settings page to the AI section.
    $ADMIN->add('ai', $settings);
}
