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

class chat {

    private $placement;
    private $context;

    public function __construct() {
        $this->placement = new placement();
        $this->context = new context();
    }
    
    /**
     * Отправка сообщения через Moodle AI Manager
     */
    public function send_message(string $message, int $courseid, int $userid, array $history = []): array {
        global $DB;
        
        $starttime = microtime(true);
        
        try {
            // 1. Получаем контекст курса
            $course_context = \context_course::instance($courseid);
            $system_prompt = $this->placement->get_system_prompt($course_context, $userid);
            
            // 2. Формируем полный промпт с историей
            $fullprompt = $system_prompt . "\n\n";
            
            // Добавляем последние N сообщений из истории
            $max_history = get_config('aiplacement_chat', 'max_history') ?: 10;
            $recent_history = array_slice($history, -$max_history);
            
            foreach ($recent_history as $item) {
                if ($item['role'] === 'user') {
                    $fullprompt .= "User: " . $item['content'] . "\n";
                } else {
                    $fullprompt .= "Assistant: " . $item['content'] . "\n";
                }
            }
            
            // Добавляем текущее сообщение
            $fullprompt .= "User: " . $message . "\nAssistant:";
            
            // 3. Отправляем через Moodle AI Manager
            $response = $this->call_ai_manager($fullprompt, $course_context->id, $userid);
            
            $processing_time = round((microtime(true) - $starttime) * 1000);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => '',
                    'error' => $response['error'] ?? 'AI generation failed'
                ];
            }
            
            // 4. Логируем
            $this->log_message($courseid, $userid, $message, $response['message'], $processing_time);
            
            return [
                'success' => true,
                'message' => $response['message'],
                'model' => $response['model'] ?? 'unknown',
                'time' => $processing_time
            ];
            
        } catch (\Exception $e) {
            debugging('Chat error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            
            return [
                'success' => false,
                'message' => '',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Вызов AI через Moodle AI Manager
     */
    private function call_ai_manager(string $prompt, int $contextid, int $userid): array {
        try {
            // Создаем action для генерации текста
            $action = new \core_ai\aiactions\generate_text(
                contextid: $contextid,
                userid: $userid,
                prompttext: $prompt
            );
            
            // Получаем AI manager через DI
            $manager = \core\di::get(\core_ai\manager::class);
            
            // Отправляем action на обработку
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
            debugging('AI Manager error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Логирование сообщений
     */
    private function log_message(int $courseid, int $userid, string $question, string $answer, int $time) {
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
     * Получение истории чата пользователя
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
     * Очистка истории
     */
    public function clear_history(int $courseid, int $userid) {
        global $DB;
        
        return $DB->delete_records('coursechat_log', [
            'courseid' => $courseid,
            'userid' => $userid
        ]);
    }
}