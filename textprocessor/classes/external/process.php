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

namespace aiplacement_textprocessor\external;

use aiplacement_textprocessor\utils;
use aiplacement_textprocessor\file_extractor;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

/**
 * External API for text processing - converts text/documents to structured HTML.
 *
 * Supports:
 * - Plain text input
 * - File input (PDF, DOCX, DOC, TXT) via base64 - NOT stored on server
 *
 * Templates:
 * - document_to_html  - Full document structure with headings, paragraphs, lists
 * - structure_headings - Extract and format heading hierarchy
 * - definitions_table  - Format definitions/terms as HTML table
 * - image_centering    - Add centered image markup
 * - custom             - Custom prompt from user
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process extends external_api {

    /**
     * Process parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(
                PARAM_INT,
                'The context ID',
                VALUE_REQUIRED,
            ),
            'content' => new external_value(
                PARAM_RAW,
                'Text content or base64 encoded file content',
                VALUE_REQUIRED,
            ),
            'template' => new external_value(
                PARAM_ALPHANUMEXT,
                'Processing template: document_to_html, structure_headings, definitions_table, image_centering, custom',
                VALUE_DEFAULT,
                'document_to_html',
            ),
            'filename' => new external_value(
                PARAM_FILE,
                'Original filename if content is a file (determines file type)',
                VALUE_DEFAULT,
                '',
            ),
            'customprompt' => new external_value(
                PARAM_RAW,
                'Custom prompt for template=custom',
                VALUE_DEFAULT,
                '',
            ),
        ]);
    }

    /**
     * Process text or file content and return structured HTML.
     *
     * @param int $contextid The context ID.
     * @param string $content Text or base64 file content.
     * @param string $template Processing template.
     * @param string $filename Original filename (if file).
     * @param string $customprompt Custom prompt (if template=custom).
     * @return array The processed HTML content.
     */
    public static function execute(
        int $contextid,
        string $content,
        string $template = 'document_to_html',
        string $filename = '',
        string $customprompt = ''
    ): array {
        global $USER;

        // DEBUG: log incoming request.
        debugging('[TextProcessor] process::execute called. template=' . $template .
            ', filename=' . $filename . ', contextid=' . $contextid, DEBUG_DEVELOPER);

        // Parameter validation.
        [
            'contextid'    => $contextid,
            'content'      => $content,
            'template'     => $template,
            'filename'     => $filename,
            'customprompt' => $customprompt,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid'    => $contextid,
            'content'      => $content,
            'template'     => $template,
            'filename'     => $filename,
            'customprompt' => $customprompt,
        ]);

        // Context validation and permission check.
        $context = \core\context::instance_by_id($contextid);
        self::validate_context($context);

        if (!utils::is_textprocessor_placement_action_available($context, 'generate_text', \core_ai\aiactions\generate_text::class)) {
            throw new \moodle_exception('notavailable', 'aiplacement_textprocessor');
        }

        // Step 1: Extract text from file if filename provided.
        $textcontent = $content;
        if (!empty($filename) && file_extractor::is_supported($filename)) {
            debugging('[TextProcessor] Extracting text from file: ' . $filename, DEBUG_DEVELOPER);
            $textcontent = file_extractor::extract_from_base64($content, $filename);
            debugging('[TextProcessor] Extracted ' . strlen($textcontent) . ' chars from file', DEBUG_DEVELOPER);
        }

        if (empty(trim($textcontent))) {
            return [
                'success'  => false,
                'html'     => '',
                'message'  => get_string('error_empty_content', 'aiplacement_textprocessor'),
            ];
        }

        // Step 2: Build prompt based on template.
        $prompt = self::build_prompt($template, $textcontent, $customprompt);

        debugging('[TextProcessor] Sending to AI, prompt length=' . strlen($prompt), DEBUG_DEVELOPER);

        // Step 3: Send to AI manager (uses configured provider - ollama).
        $action = new \core_ai\aiactions\generate_text(
            contextid: $contextid,
            userid: $USER->id,
            prompttext: $prompt,
        );

        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);

        if (!$response->get_success()) {
            debugging('[TextProcessor] AI error: ' . $response->get_errormessage(), DEBUG_DEVELOPER);
            return [
                'success'  => false,
                'html'     => '',
                'message'  => $response->get_errormessage() ?: $response->get_error(),
            ];
        }

        $generatedcontent = $response->get_response_data()['generatedcontent'] ?? '';

        // Step 4: Clean up the generated HTML.
        $html = self::clean_html_output($generatedcontent);

        debugging('[TextProcessor] Success, html length=' . strlen($html), DEBUG_DEVELOPER);

        return [
            'success' => true,
            'html'    => $html,
            'message' => '',
        ];
    }

    /**
     * Build the AI prompt based on template.
     *
     * @param string $template Template name
     * @param string $text Source text
     * @param string $customprompt Custom prompt (for template=custom)
     * @return string Full prompt for AI
     */
    private static function build_prompt(string $template, string $text, string $customprompt = ''): string {
        // Check for admin-defined custom prompt override.
        $adminprompt = get_config('aiplacement_textprocessor', 'custom_prompt');

        $base_instructions = "You are an HTML formatter. Your task is to convert the provided text into clean, semantic HTML.
IMPORTANT RULES:
- Return ONLY valid HTML markup, no explanations, no markdown code blocks
- Do NOT wrap output in ```html or ``` tags
- Use semantic HTML5 elements
- All images should be centered: <figure class=\"text-center\"><img src=\"#\" alt=\"description\" class=\"img-fluid\"></figure>
- Tables should have class=\"table table-bordered\"
- Use Bootstrap-compatible classes
- Preserve the original language of the text";

        switch ($template) {
            case 'document_to_html':
                $prompt = $base_instructions . "

TASK: Convert the following document text into a fully structured HTML document fragment.
Structure requirements:
- Use <h1> for main title, <h2> for sections, <h3> for subsections
- Use <p> for paragraphs
- Use <ul>/<ol> with <li> for lists
- Use <strong> for important terms, <em> for emphasis
- Use <blockquote> for quotes
- Use <table class=\"table table-bordered\"> for tabular data
- Center images with <figure class=\"text-center\">
- Use <hr> for section separators

TEXT TO CONVERT:
{$text}";
                break;

            case 'structure_headings':
                $prompt = $base_instructions . "

TASK: Extract and format the heading structure from the following text.
Requirements:
- Create a hierarchical heading structure using <h1>, <h2>, <h3>, <h4>
- Under each heading, include a brief summary paragraph <p>
- Create a table of contents at the top: <nav><ul class=\"list-unstyled\">...</ul></nav>
- Use anchor links: <h2 id=\"section-1\">...</h2>

TEXT TO PROCESS:
{$text}";
                break;

            case 'definitions_table':
                $prompt = $base_instructions . "

TASK: Extract all definitions, terms, and key concepts from the text and format them as an HTML table.
Requirements:
- Create a table: <table class=\"table table-bordered table-striped\">
- Columns: Term | Definition | Example (if available)
- Add <thead> with column headers
- Add <tbody> with data rows
- If no definitions found, create a glossary from key terms in the text
- Sort terms alphabetically

TEXT TO PROCESS:
{$text}";
                break;

            case 'image_centering':
                $prompt = $base_instructions . "

TASK: Process the following HTML/text and ensure all images are properly centered and formatted.
Requirements:
- Wrap each image in: <figure class=\"text-center my-3\"><img src=\"...\" alt=\"...\" class=\"img-fluid rounded\"><figcaption class=\"text-muted\">...</figcaption></figure>
- If text mentions images/figures without actual img tags, create placeholder markup
- Keep all other content as-is, just fix image formatting

TEXT TO PROCESS:
{$text}";
                break;

            case 'custom':
                if (!empty($customprompt)) {
                    $prompt = $base_instructions . "\n\nCUSTOM TASK: " . $customprompt . "\n\nTEXT TO PROCESS:\n{$text}";
                } elseif (!empty($adminprompt)) {
                    $prompt = $base_instructions . "\n\nTASK: " . $adminprompt . "\n\nTEXT TO PROCESS:\n{$text}";
                } else {
                    $prompt = $base_instructions . "

TASK: Format the following text as clean, well-structured HTML.

TEXT TO PROCESS:
{$text}";
                }
                break;

            default:
                $prompt = $base_instructions . "

TASK: Convert the following text to structured HTML.

TEXT:
{$text}";
        }

        return $prompt;
    }

    /**
     * Clean up AI-generated HTML output.
     * Remove markdown code blocks, fix common issues.
     *
     * @param string $html Raw AI output
     * @return string Cleaned HTML
     */
    private static function clean_html_output(string $html): string {
        // Remove markdown code blocks if AI wrapped output in them.
        $html = preg_replace('/^```html\s*/i', '', $html);
        $html = preg_replace('/^```\s*/m', '', $html);
        $html = preg_replace('/\s*```$/m', '', $html);

        // Remove any leading/trailing whitespace.
        $html = trim($html);

        return $html;
    }

    /**
     * Process return value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'success' => new external_value(
                PARAM_BOOL,
                'Was the processing successful',
                VALUE_REQUIRED,
            ),
            'html' => new external_value(
                PARAM_RAW,
                'The generated HTML content',
                VALUE_DEFAULT,
                '',
            ),
            'message' => new external_value(
                PARAM_TEXT,
                'Error message if any',
                VALUE_DEFAULT,
                '',
            ),
        ]);
    }
}
