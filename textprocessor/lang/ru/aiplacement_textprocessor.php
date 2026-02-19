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
$string['processing_heading'] = 'Настройки обработки текста';
$string['processing_heading_desc'] = 'Настройте параметры обработки текста.';
$string['custom_prompt'] = 'Пользовательский промпт обработки';
$string['custom_prompt_desc'] = 'Этот промпт будет использоваться при выборе шаблона "Пользовательский". Оставьте пустым для стандартного поведения.';
$string['info_heading'] = 'Информация';
$string['info_heading_desc'] = 'Этот плагин использует AI Manager с настроенными провайдерами (например, Ollama). Настройте провайдеры в Администрирование сайта > AI > Провайдеры.';

// Диалог.
$string['template'] = 'Шаблон обработки';
$string['customprompt'] = 'Пользовательский промпт';
$string['customprompt_placeholder'] = 'Опишите, как вы хотите обработать текст...';
$string['upload_file'] = 'Загрузить файл (PDF, DOCX, TXT)';
$string['supported_files'] = 'Поддерживаются: PDF, DOCX, DOC, TXT, RTF. Файлы обрабатываются в памяти и НЕ сохраняются на сервере.';
$string['text_input'] = 'Или вставьте текст напрямую';
$string['text_input_placeholder'] = 'Вставьте текст сюда...';
$string['process'] = 'Обработать';
$string['insert'] = 'Вставить';
$string['processing'] = 'Обработка...';

// Шаблоны.
$string['template_document_to_html'] = 'Документ в HTML';
$string['template_document_to_html_desc'] = 'Преобразовать документ в структурированный HTML с заголовками, параграфами, списками.';
$string['template_structure_headings'] = 'Структура заголовков';
$string['template_structure_headings_desc'] = 'Извлечь и отформатировать иерархию заголовков с оглавлением.';
$string['template_definitions_table'] = 'Таблица определений';
$string['template_definitions_table_desc'] = 'Извлечь термины и определения в HTML таблицу.';
$string['template_image_centering'] = 'Центрирование изображений';
$string['template_image_centering_desc'] = 'Отформатировать изображения с центрированием и подписями.';
$string['template_custom'] = 'Пользовательский';
$string['template_custom_desc'] = 'Использовать пользовательский промпт для обработки.';

// Ошибки.
$string['error_empty_content'] = 'Нет содержимого для обработки. Введите текст или загрузите файл.';
$string['notavailable'] = 'TextProcessor недоступен в этом контексте.';

// Устаревшие строки (оставлены для совместимости).
$string['to_html'] = 'В HTML';
$string['from_markdown'] = 'Из Markdown';
$string['to_table'] = 'В таблицу';
$string['clean_html'] = 'Очистить HTML';
$string['input_label'] = 'Входной текст';
$string['input_placeholder'] = 'Введите текст для обработки...';
$string['output_label'] = 'Выходной HTML';
$string['output_placeholder'] = 'Обработанный HTML появится здесь...';
$string['copy'] = 'Копировать';
$string['ollama_not_configured'] = 'Ollama не настроен. Настройте AI провайдер в Администрировании сайта.';