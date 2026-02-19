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

namespace aiplacement_chat;

defined('MOODLE_INTERNAL') || die();

/**
 * Chat class for AI Chat Placement.
 * Uses only Moodle AI Manager - no fallback to direct provider calls.
 *
 * @package    aiplacement_chat
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chat {

    /** @var placement */
    private $placement;

    /** @var context */
    private $context;

    public function __construct() {
        $this->placement = new placement();
        $this->context = new context();
    }

    /**
     * Send message through Moodle AI Manager.
     *
     * @param string $message User message
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param array $history Chat history
     * @return array Response with success, message, error
     */
    public function send_message(string $message, int $courseid, int $userid, array $history = []): array {
        global $DB;

        $starttime = microtime(true);

        // DEBUG: Log incoming request.
        debugging('[Chat] send_message called. courseid=' . $courseid . ', userid=' . $userid, DEBUG_DEVELOPER);

        try {
            // 1. Get course context with all pages content.
            $coursecontext = \context_course::instance($courseid);
            $systemprompt = $this->placement->get_system_prompt($coursecontext, $userid);

            // DEBUG: Log system prompt length.
            debugging('[Chat] System prompt length: ' . strlen($systemprompt), DEBUG_DEVELOPER);

            // 2. Build full prompt with history.
            $fullprompt = $systemprompt . "\n\n";

            // Add recent history.
            $maxhistory = get_config('aiplacement_chat', 'max_history') ?: 10;
            $recenthistory = array_slice($history, -$maxhistory);

            foreach ($recenthistory as $item) {
                if ($item['role'] === 'user') {
                    $fullprompt .= "User: " . $item['content'] . "\n";
                } else {
                    $fullprompt .= "Assistant: " . $item['content'] . "\n";
                }
            }

            // Add current message.
            $fullprompt .= "User: " . $message . "\nAssistant:";

            // DEBUG: Log full prompt length.
            debugging('[Chat] Full prompt length: ' . strlen($fullprompt), DEBUG_DEVELOPER);

            // 3. Send via Moodle AI Manager (uses configured provider - ollama).
            $response = $this->call_ai_manager($fullprompt, $coursecontext->id, $userid);

            $processingtime = round((microtime(true) - $starttime) * 1000);

            if (!$response['success']) {
                debugging('[Chat] AI Manager error: ' . ($response['error'] ?? 'Unknown error'), DEBUG_DEVELOPER);
                return [
                    'success' => false,
                    'message' => '',
                    'error' => $response['error'] ?? 'AI generation failed'
                ];
            }

            // 4. Log the conversation.
            $this->log_message($courseid, $userid, $message, $response['message'], $processingtime);

            // DEBUG: Log success.
            debugging('[Chat] Success. Processing time: ' . $processingtime . 'ms', DEBUG_DEVELOPER);

            return [
                'success' => true,
                'message' => $response['message'],
                'model' => $response['model'] ?? 'unknown',
                'time' => $processingtime
            ];

        } catch (\Exception $e) {
            debugging('[Chat] Exception: ' . $e->getMessage(), DEBUG_DEVELOPER);

            return [
                'success' => false,
                'message' => '',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Call AI through Moodle AI Manager.
     * No fallback - uses only configured providers.
     *
     * @param string $prompt The prompt to send
     * @param int $contextid Context ID
     * @param int $userid User ID
     * @return array Response data
     */
    private function call_ai_manager(string $prompt, int $contextid, int $userid): array {
        try {
            // Create action for text generation.
            $action = new \core_ai\aiactions\generate_text(
                contextid: $contextid,
                userid: $userid,
                prompttext: $prompt
            );

            // Get AI manager via DI.
            $manager = \core\di::get(\core_ai\manager::class);

            // Send action for processing.
            $response = $manager->process_action($action);

            if ($response->get_success()) {
                $responsedata = $response->get_response_data();
                return [
                    'success' => true,
                    'message' => $responsedata['generatedcontent'] ?? '',
                    'model' => $responsedata['model'] ?? 'unknown'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->get_errormessage() ?: $response->get_error()
                ];
            }
        } catch (\Exception $e) {
            debugging('[Chat] AI Manager exception: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Log message to database.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param string $question User question
     * @param string $answer AI answer
     * @param int $time Processing time in ms
     */
    private function log_message(int $courseid, int $userid, string $question, string $answer, int $time): void {
        global $DB;

        $log = new \stdClass();
        $log->courseid = $courseid;
        $log->userid = $userid;
        $log->question = $question;
        $log->answer = $answer;
        $log->model = 'ai_manager';
        $log->processing_time = $time;
        $log->timecreated = time();

        $DB->insert_record('coursechat_log', $log);
    }

    /**
     * Get user's chat history.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param int $limit Max records
     * @return array History
     */
    public function get_history(int $courseid, int $userid, int $limit = 50): array {
        global $DB;

        $logs = $DB->get_records('coursechat_log',
            ['courseid' => $courseid, 'userid' => $userid],
            'timecreated ASC',
            'question, answer, timecreated',
            0, $limit
        );

        $history = [];
        foreach ($logs as $log) {
            $history[] = [
                'role' => 'user',
                'content' => $log->question,
                'time' => $log->timecreated
            ];
            $history[] = [
                'role' => 'assistant',
                'content' => $log->answer,
                'time' => $log->timecreated
            ];
        }

        return $history;
    }

    /**
     * Clear user's chat history.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @return bool Success
     */
    public function clear_history(int $courseid, int $userid): bool {
        global $DB;

        return $DB->delete_records('coursechat_log', [
            'courseid' => $courseid,
            'userid' => $userid
        ]);
    }
}