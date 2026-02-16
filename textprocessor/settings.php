<?php
// /ai/placement/textprocessor/settings.php

defined('MOODLE_INTERNAL') || die();

use core_ai\admin\admin_settingspage_provider;

if ($hassiteconfig) {
    // Создаем страницу настроек для плагина
    $settings = new admin_settingspage_provider(
        'aiplacement_textprocessor',
        get_string('pluginname', 'aiplacement_textprocessor'),
        'moodle/site:config',
        true, // Важно: true означает, что это placement
    );
    
    // Добавляем настройки для каждого действия
    $actions = [
        'to_html' => get_string('to_html', 'aiplacement_textprocessor'),
        'from_markdown' => get_string('from_markdown', 'aiplacement_textprocessor'),
        'to_table' => get_string('to_table', 'aiplacement_textprocessor'),
        'clean_html' => get_string('clean_html', 'aiplacement_textprocessor')
    ];
    
    foreach ($actions as $actionname => $actiontitle) {
        // Создаем секцию для каждого действия
        $settings->add(new admin_setting_heading(
            "textprocessor/{$actionname}_heading",
            $actiontitle,
            ''
        ));
        
        // Включено/выключено
        $settings->add(new admin_setting_configcheckbox(
            "textprocessor/{$actionname}_enabled",
            get_string('enabled', 'core'),
            get_string('action_enabled_desc', 'aiplacement_textprocessor', $actiontitle),
            1
        ));
        
        // Права доступа (можно выбрать роль)
        $settings->add(new admin_setting_configselect(
            "textprocessor/{$actionname}_capability",
            get_string('requiredcapability', 'core'),
            get_string('action_capability_desc', 'aiplacement_textprocessor'),
            'textprocessor/use',
            [
                'textprocessor/use' => get_string('textprocessor:use', 'aiplacement_textprocessor'),
                'moodle/site:config' => get_string('site:config', 'role'),
                'moodle/course:manageactivities' => get_string('course:manageactivities', 'role')
            ]
        ));
    }
    
    // Добавляем страницу в раздел AI
    $ADMIN->add('ai', $settings);
}