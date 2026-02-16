<?php
// /ai/placement/quizgen/lang/ru/quizgen.php

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Генератор тестов';
$string['pluginname_desc'] = 'Генерация тестовых вопросов из текста с помощью Ollama';

// Права доступа
$string['quizgen:generate'] = 'Генерировать тесты';
$string['quizgen:save'] = 'Сохранять вопросы в банк';
$string['quizgen:manage'] = 'Управлять настройками генерации';

// Интерфейс
$string['generate_quiz'] = 'Сгенерировать тест';
$string['source_text'] = 'Исходный текст';
$string['source_text_help'] = 'Вставьте текст лекции, конспекта или статьи';
$string['question_count'] = 'Количество вопросов';
$string['question_type'] = 'Тип вопросов';
$string['difficulty'] = 'Сложность';
$string['difficulty_easy'] = 'Легкая';
$string['difficulty_medium'] = 'Средняя';
$string['difficulty_hard'] = 'Сложная';
$string['language'] = 'Язык вопросов';
$string['language_ru'] = 'Русский';
$string['language_en'] = 'Английский';
$string['generate'] = '🎯 Сгенерировать';
$string['generating'] = 'AI создает вопросы...';
$string['save_all'] = '💾 Сохранить все в банк';
$string['save_selected'] = 'Сохранить выбранные';
$string['edit'] = 'Редактировать';
$string['regenerate'] = '🔄 Перегенерировать';
$string['preview'] = 'Предпросмотр';
$string['export'] = '📤 Экспорт в XML';
$string['add_to_quiz'] = '📝 Добавить в тест';

// Типы вопросов
$string['type_multichoice'] = 'Множественный выбор';
$string['type_truefalse'] = 'Верно/Неверно';
$string['type_shortanswer'] = 'Короткий ответ';
$string['type_matching'] = 'Соответствие';
$string['type_essay'] = 'Эссе';
$string['type_combined'] = 'Комбинированный';

// Уведомления
$string['success_generated'] = '✅ Сгенерировано {$a} вопросов';
$string['success_saved'] = '✅ {$a} вопросов сохранено в банк';
$string['error_empty_text'] = '❌ Введите текст для генерации';
$string['error_ollama'] = '❌ Ошибка подключения к Ollama';
$string['error_generation'] = '❌ Ошибка генерации вопросов';
$string['error_save'] = '❌ Ошибка сохранения вопроса';

// Настройки
$string['ollama_url'] = 'URL Ollama';
$string['ollama_model'] = 'Модель';
$string['default_question_count'] = 'Вопросов по умолчанию';
$string['default_question_type'] = 'Тип по умолчанию';
$string['auto_save'] = 'Автосохранение';