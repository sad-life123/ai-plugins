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

/**
 * Русские строки для AI Chat Placement.
 *
 * @package    aiplacement_chat
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'AI Чат курса';
$string['privacy:metadata'] = 'Плагин AI Chat Placement сохраняет историю чата для непрерывности контекста.';

// Возможности.
$string['chat:use'] = 'Использовать AI чат курса';

// Настройки.
$string['context_heading'] = 'Настройки контекста курса';
$string['context_heading_desc'] = 'Настройте, какие данные курса передаются AI.';
$string['context_sources'] = 'Источники контекста';
$string['context_sources_desc'] = 'Выберите, что AI будет знать о курсе.';
$string['context_files'] = 'Файлы курса (PDF, DOCX, TXT) - текст извлекается';
$string['context_activities'] = 'Описания активностей, тесты, форумы';
$string['context_sections'] = 'Названия и описания разделов';
$string['context_pages'] = 'Содержимое страниц, уроков, меток';
$string['context_grades'] = 'Оценки студента (только свои)';
$string['max_context_length'] = 'Макс. длина контекста';
$string['max_context_length_desc'] = 'Максимальное количество символов контекста для отправки AI.';

$string['chat_heading'] = 'Настройки чата';
$string['chat_heading_desc'] = 'Интерфейс и поведение.';
$string['chat_position'] = 'Позиция виджета';
$string['chat_position_desc'] = 'Где отображать кнопку чата.';
$string['position_right'] = 'Справа';
$string['position_left'] = 'Слева';
$string['position_bottom'] = 'Внизу по центру';
$string['max_history'] = 'История сообщений';
$string['max_history_desc'] = 'Сколько последних сообщений запоминать.';

$string['info_heading'] = 'Информация';
$string['info_heading_desc'] = 'Этот плагин использует AI Manager с настроенными провайдерами (например, Ollama). Настройте провайдеры в Администрирование сайта > AI > Провайдеры.';

// Интерфейс.
$string['chat_title'] = 'AI Помощник курса';
$string['chat_button'] = 'Открыть чат';
$string['input_placeholder'] = 'Спросите о курсе...';
$string['send'] = 'Отправить';
$string['typing'] = 'AI печатает...';
$string['clear_history'] = 'Очистить историю';
$string['context_info'] = 'Я знаю этот курс';

// Ошибки.
$string['error_ai'] = 'Ошибка подключения к AI. Проверьте настройки провайдера.';
$string['error_context'] = 'Не удалось загрузить контекст курса';
$string['error_general'] = 'Произошла ошибка. Попробуйте позже.';