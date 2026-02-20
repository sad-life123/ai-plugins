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
 * Single unified action: intelligently formats ALL content elements:
 * - Headings, paragraphs, lists
 * - Tables, definitions
 * - Code blocks, quotes
 * - Images with centering
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
            'filename' => new external_value(
                PARAM_FILE,
                'Original filename if content is a file',
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
     * @param string $filename Original filename (if file).
     * @return array The processed HTML content.
     */
    public static function execute(
        int $contextid,
        string $content,
        string $filename = ''
    ): array {
        global $USER;

        // Parameter validation.
        [
            'contextid' => $contextid,
            'content'   => $content,
            'filename'  => $filename,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'content'   => $content,
            'filename'  => $filename,
        ]);

        // Context validation and permission check.
        $context = \core\context::instance_by_id($contextid);
        self::validate_context($context);

        if (!utils::is_textprocessor_placement_action_available($context, 'generate_text', \core_ai\aiactions\generate_text::class)) {
            return [
                'success' => false,
                'html'    => '',
                'message' => get_string('notavailable', 'aiplacement_textprocessor'),
            ];
        }

        // Step 1: Extract text from file if filename provided.
        $textcontent = $content;
        if (!empty($filename) && file_extractor::is_supported($filename)) {
            $textcontent = file_extractor::extract_from_base64($content, $filename);
        }

        if (empty(trim($textcontent))) {
            return [
                'success' => false,
                'html'    => '',
                'message' => get_string('error_empty_content', 'aiplacement_textprocessor'),
            ];
        }

        // Step 2: Build unified prompt.
        $prompt = self::build_unified_prompt($textcontent);

        // Step 3: Send to AI manager.
        $action = new \core_ai\aiactions\generate_text(
            contextid: $contextid,
            userid: $USER->id,
            prompttext: $prompt,
        );

        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);

        if (!$response->get_success()) {
            return [
                'success' => false,
                'html'    => '',
                'message' => $response->get_errormessage() ?: 'AI processing failed',
            ];
        }

        $generatedcontent = $response->get_response_data()['generatedcontent'] ?? '';

        // Step 4: Clean up the generated HTML.
        $html = self::clean_html_output($generatedcontent);

        return [
            'success' => true,
            'html'    => $html,
            'message' => '',
        ];
    }

    /**
     * Build the unified AI prompt for comprehensive document formatting.
     *
     * @param string $text Source text
     * @return string Full prompt for AI
     */
    private static function build_unified_prompt(string $text): string {
        // Use clear instruction format with the text at the beginning for better AI focus.
        $prompt = "Convert the following text to clean semantic HTML. Output ONLY the HTML, nothing else.

TEXT TO FORMAT:
{$text}

FORMATTING INSTRUCTIONS:
- Return ONLY HTML markup (no explanations, no markdown, no code blocks)
- Preserve the original language
- Use Bootstrap 5 classes where appropriate

Apply these rules:
1. Headings: Main title <h1>, sections <h2>, subsections <h3>-<h6>
2. Paragraphs: <p> with <strong> and <em> for emphasis
3. Lists: <ol>/<ul> with <li>, definitions use <dl><dt><dd>
4. Tables: <table class=\"table table-bordered\"> with <thead><tbody>
5. Code: <pre><code> for blocks, <code> for inline
6. Quotes: <blockquote class=\"blockquote\">
7. Images: <figure class=\"text-center\"><img class=\"img-fluid\">
8. Links: <a href=\"URL\">text</a>
9. Notes: <div class=\"alert alert-info\">
10. Warnings: <div class=\"alert alert-warning\">

HTML OUTPUT:";

        return $prompt;
    }

    /**
     * Clean up AI-generated HTML output.
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