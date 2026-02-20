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
 * Русские строки для TextProcessor AI Placement.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'AI Обработчик текста';
$string['privacy:metadata'] = 'Плагин TextProcessor AI Placement не хранит персональные данные.';

// Возможности.
$string['textprocessor:generate_text'] = 'Генерировать текст с помощью AI';

// Настройки.
$string['info_heading'] = 'Информация';
$string['info_heading_desc'] = 'Этот плагин использует AI Manager с настроенными провайдерами (например, Ollama). Настройте провайдеры в Администрирование сайта > AI > Провайдеры.';

// Диалог.
$string['upload_file'] = 'Загрузить файл (PDF, DOCX, TXT)';
$string['supported_files'] = 'Поддерживаются: PDF, DOCX, DOC, TXT, RTF. Файлы обрабатываются в памяти и НЕ сохраняются на сервере.';
$string['text_input'] = 'Или вставьте текст напрямую';
$string['text_input_placeholder'] = 'Вставьте текст сюда...';
$string['process'] = 'Обработать';
$string['insert'] = 'Вставить';
$string['processing'] = 'Обработка...';

// Информация.
$string['process_info_title'] = 'Что происходит:';
$string['process_info_desc'] = 'AI проанализирует документ и отформатирует его: заголовки, таблицы, списки, блоки кода, определения, изображения — всё будет преобразовано в правильный HTML.';

// Ошибки.
$string['error_empty_content'] = 'Нет содержимого для обработки. Введите текст или загрузите файл.';
$string['notavailable'] = 'TextProcessor недоступен в этом контексте.';
$string['ollama_not_configured'] = 'AI провайдер не настроен. Настройте AI провайдер в Администрировании сайта.';

// Устаревшие строки (оставлены для совместимости).
$string['input_label'] = 'Входной текст';
$string['input_placeholder'] = 'Введите текст для обработки...';
$string['output_label'] = 'Выходной HTML';
$string['output_placeholder'] = 'Обработанный HTML появится здесь...';
$string['copy'] = 'Копировать';