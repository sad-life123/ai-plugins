<?php
// /ai/placement/quizgen/settings.php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('quizgen_settings',
        get_string('pluginname', 'aiplacement_quizgen'));
    
    // 🦙 НАСТРОЙКИ OLLAMA
    $settings->add(new admin_setting_heading('ollama_heading',
        '🦙 Настройки Ollama',
        'Подключение к локальному серверу Ollama'
    ));
    
    $settings->add(new admin_setting_configtext(
        'quizgen/ollama_url',
        'URL Ollama сервера',
        'Адрес Ollama API (обычно http://localhost:11434)',
        'http://localhost:11434',
        PARAM_URL
    ));
    
    $settings->add(new admin_setting_configtext(
        'quizgen/ollama_model',
        'Модель для генерации',
        'Рекомендуется: llama3.1, qwen2.5, mistral',
        'qwen2.5:7b',
        PARAM_TEXT
    ));
    
    // 🎯 НАСТРОЙКИ ПО УМОЛЧАНИЮ
    $settings->add(new admin_setting_heading('defaults_heading',
        '🎯 Настройки по умолчанию',
        'Параметры генерации тестов'
    ));
    
    $settings->add(new admin_setting_configselect(
        'quizgen/default_question_count',
        'Количество вопросов',
        'По умолчанию генерировать N вопросов',
        5,
        [3 => '3', 5 => '5', 10 => '10', 15 => '15', 20 => '20']
    ));
    
    $settings->add(new admin_setting_configselect(
        'quizgen/default_question_type',
        'Тип вопросов',
        'По умолчанию создавать вопросы типа',
        'multichoice',
        [
            'multichoice' => 'Множественный выбор',
            'truefalse' => 'Верно/Неверно',
            'shortanswer' => 'Короткий ответ',
            'matching' => 'Соответствие',
            'essay' => 'Эссе'
        ]
    ));
    
    $settings->add(new admin_setting_configtext(
        'quizgen/default_category',
        'Категория по умолчанию',
        'ID категории вопросов в банке (0 = корневая)',
        0,
        PARAM_INT
    ));
    
    $settings->add(new admin_setting_configcheckbox(
        'quizgen/auto_save',
        'Автосохранение',
        'Автоматически сохранять вопросы в банк при генерации',
        0
    ));
    
    $ADMIN->add('ai', $settings);
}