<?php
// /ai/placement/coursechat/lang/ru/coursechat.php

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Чат курса';
$string['pluginname_desc'] = 'Чат с AI на основе контекста курса (Ollama)';

// Права доступа
$string['coursechat:use'] = 'Использовать AI чат курса';
$string['coursechat:viewcontext'] = 'Видеть контекст курса в чате';

// Интерфейс
$string['chat_title'] = 'AI Помощник курса';
$string['chat_button'] = 'Открыть чат';
$string['input_placeholder'] = 'Спросите о курсе...';
$string['send'] = 'Отправить';
$string['typing'] = 'AI печатает...';
$string['clear_history'] = 'Очистить историю';
$string['context_info'] = 'Я знаю этот курс';

// Системные промпты
$string['system_prompt'] = 'Ты - AI ассистент курса в Moodle. 
Отвечай на вопросы студентов на основе контекста курса.
Контекст курса: {course_context}

Правила:
1. Отвечай ТОЛЬКО по материалу курса
2. Если ответа нет в контексте - скажи честно
3. Будь дружелюбным и полезным
4. Отвечай на языке вопроса (русский/английский)';

// Ошибки
$string['error_ollama'] = 'Ошибка подключения к AI. Проверьте настройки Ollama.';
$string['error_context'] = 'Не удалось загрузить контекст курса';
$string['error_general'] = 'Произошла ошибка. Попробуйте позже.';

// Настройки
$string['ollama_url'] = 'URL Ollama';
$string['ollama_model'] = 'Модель Ollama';
$string['context_sources'] = 'Источники контекста';
$string['chat_position'] = 'Позиция чата';
$string['max_history'] = 'Максимум истории';