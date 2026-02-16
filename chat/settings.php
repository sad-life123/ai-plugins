<?php
// /ai/placement/coursechat/settings.php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('coursechat_settings',
        get_string('pluginname', 'aiplacement_coursechat'));
    
    // ============================================
    // 🦙 НАСТРОЙКИ OLLAMA
    // ============================================
    $settings->add(new admin_setting_heading('ollama_heading',
        '🦙 Настройки Ollama (Локальный AI)',
        'Подключение к локальному серверу Ollama'
    ));
    
    $settings->add(new admin_setting_configtext(
        'coursechat/ollama_url',
        'URL Ollama сервера',
        'Адрес Ollama API (обычно http://localhost:11434)',
        'http://localhost:11434',
        PARAM_URL
    ));
    
    $settings->add(new admin_setting_configtext(
        'coursechat/ollama_model',
        'Модель для чата',
        'Рекомендуется: llama3.1, qwen2.5, mistral',
        'llama3.1',
        PARAM_TEXT
    ));
    
    // ============================================
    // 📚 НАСТРОЙКИ КОНТЕКСТА КУРСА
    // ============================================
    $settings->add(new admin_setting_heading('context_heading',
        '📚 Контекст курса',
        'Какие данные передавать AI для понимания курса'
    ));
    
    $settings->add(new admin_setting_configmulticheckbox(
        'coursechat/context_sources',
        'Источники контекста',
        'Выберите, что AI будет знать о курсе',
        [
            'files' => 1,
            'activities' => 1,
            'sections' => 1,
            'grades' => 0
        ],
        [
            'files' => '📄 Файлы курса (PDF, DOCX, TXT) - извлекается текст',
            'activities' => '📝 Описания заданий, тестов, форумов',
            'sections' => '📚 Названия и описания тем',
            'grades' => '📊 Оценки студента (только свой контекст)'
        ]
    ));
    
    $settings->add(new admin_setting_configtext(
        'coursechat/max_context_length',
        'Максимальная длина контекста',
        'Сколько символов контекста передавать AI (токены)',
        8000,
        PARAM_INT
    ));
    
    // ============================================
    // 💬 НАСТРОЙКИ ЧАТА
    // ============================================
    $settings->add(new admin_setting_heading('chat_heading',
        '💬 Настройки чата',
        'Интерфейс и поведение'
    ));
    
    $settings->add(new admin_setting_configselect(
        'coursechat/chat_position',
        'Позиция виджета',
        'Где отображать кнопку чата',
        'right',
        [
            'right' => 'Справа',
            'left' => 'Слева',
            'bottom' => 'Снизу по центру'
        ]
    ));
    
    $settings->add(new admin_setting_configtext(
        'coursechat/max_history',
        'История сообщений',
        'Сколько последних сообщений помнить',
        50,
        PARAM_INT
    ));
    
    $ADMIN->add('ai', $settings);
}