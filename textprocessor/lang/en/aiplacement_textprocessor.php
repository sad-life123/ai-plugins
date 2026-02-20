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
 * Strings for TextProcessor AI Placement.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'AI Text Processor';
$string['privacy:metadata'] = 'The TextProcessor AI Placement plugin does not store any personal data.';

// Capabilities.
$string['textprocessor:generate_text'] = 'Generate text with AI';

// Settings.
$string['info_heading'] = 'Information';
$string['info_heading_desc'] = 'This plugin uses the AI Manager with configured providers (e.g., Ollama). Configure providers in Site administration > AI > Providers.';

// Dialog.
$string['upload_file'] = 'Upload File (PDF, DOCX, TXT)';
$string['supported_files'] = 'Supported: PDF, DOCX, DOC, TXT, RTF. Files are processed in memory and NOT stored on server.';
$string['text_input'] = 'Or paste text directly';
$string['text_input_placeholder'] = 'Paste your text here...';
$string['process'] = 'Process';
$string['insert'] = 'Insert';
$string['processing'] = 'Processing...';

// Info.
$string['process_info_title'] = 'What happens:';
$string['process_info_desc'] = 'AI will analyze your document and format it: headings, tables, lists, code blocks, definitions, images - all converted to proper HTML.';

// Errors.
$string['error_empty_content'] = 'No content to process. Please enter text or upload a file.';
$string['notavailable'] = 'TextProcessor is not available in this context.';
$string['ollama_not_configured'] = 'AI provider is not configured. Please configure AI provider in Site administration.';

// Legacy strings (kept for compatibility).
$string['input_label'] = 'Input text';
$string['input_placeholder'] = 'Enter text to process...';
$string['output_label'] = 'Output HTML';
$string['output_placeholder'] = 'Processed HTML will appear here...';
$string['copy'] = 'Copy';