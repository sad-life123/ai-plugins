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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create settings page for the plugin.
    $settings = new admin_settingpage(
        'aiplacement_chat',
        get_string('pluginname', 'aiplacement_chat'),
        'moodle/site:config'
    );

    // ============================================
    // 📚 COURSE CONTEXT SETTINGS
    // ============================================
    $settings->add(new admin_setting_heading('context_heading',
        get_string('context_heading', 'aiplacement_chat'),
        get_string('context_heading_desc', 'aiplacement_chat')
    ));

    $settings->add(new admin_setting_configmulticheckbox(
        'aiplacement_chat/context_sources',
        get_string('context_sources', 'aiplacement_chat'),
        get_string('context_sources_desc', 'aiplacement_chat'),
        [
            'files' => 1,
            'activities' => 1,
            'sections' => 1,
            'pages' => 1,
            'grades' => 0
        ],
        [
            'files' => get_string('context_files', 'aiplacement_chat'),
            'activities' => get_string('context_activities', 'aiplacement_chat'),
            'sections' => get_string('context_sections', 'aiplacement_chat'),
            'pages' => get_string('context_pages', 'aiplacement_chat'),
            'grades' => get_string('context_grades', 'aiplacement_chat')
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'aiplacement_chat/max_context_length',
        get_string('max_context_length', 'aiplacement_chat'),
        get_string('max_context_length_desc', 'aiplacement_chat'),
        8000,
        PARAM_INT
    ));

    // ============================================
    // 💬 CHAT SETTINGS
    // ============================================
    $settings->add(new admin_setting_heading('chat_heading',
        get_string('chat_heading', 'aiplacement_chat'),
        get_string('chat_heading_desc', 'aiplacement_chat')
    ));

    $settings->add(new admin_setting_configselect(
        'aiplacement_chat/chat_position',
        get_string('chat_position', 'aiplacement_chat'),
        get_string('chat_position_desc', 'aiplacement_chat'),
        'right',
        [
            'right' => get_string('position_right', 'aiplacement_chat'),
            'left' => get_string('position_left', 'aiplacement_chat'),
            'bottom' => get_string('position_bottom', 'aiplacement_chat')
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'aiplacement_chat/max_history',
        get_string('max_history', 'aiplacement_chat'),
        get_string('max_history_desc', 'aiplacement_chat'),
        50,
        PARAM_INT
    ));

    // ============================================
    // ℹ️ INFO
    // ============================================
    $settings->add(new admin_setting_heading('info_heading',
        get_string('info_heading', 'aiplacement_chat'),
        get_string('info_heading_desc', 'aiplacement_chat')
    ));

    // Add the settings page to the AI section.
    $ADMIN->add('ai', $settings);
}