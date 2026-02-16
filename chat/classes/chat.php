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

    private $ollama_url;
    private $model;
    private $placement;
    private $context;

    public function __construct() {
        // Try to get Ollama URL from provider first, then fallback to local settings.
        $this->ollama_url = $this->get_ollama_url();
        $this->model = $this->get_ollama_model();
        $this->placement = new placement();
        $this->context = new context();
    }

    /**
     * Get Ollama URL from provider or fallback to local settings.
     *
     * @return string
     */
    private function get_ollama_url(): string {
        global $DB;

        try {
            // Check if AI plugin is installed.
            if (!$DB->get_manager()->table_exists('ai_provider_instances')) {
                throw new \Exception('AI table not found');
            }

            // Try to get from aiprovider_ollama instance.
            $provider = $DB->get_record('ai_provider_instances', [
                'provider' => 'aiprovider_ollama',
                'enabled' => 1
            ], 'config');

            if (!empty($provider->config)) {
                $config = json_decode($provider->config, true);
                if (!empty($config['endpoint'])) {
                    return rtrim($config['endpoint'], '/');
                }
            }
        } catch (\Exception $e) {
            // AI plugin not installed, use fallback.
        }

        // Fallback to local settings.
        return get_config('aiplacement_chat', 'ollama_url') ?: 'http://localhost:11434';
    }

    /**
     * Get Ollama model from provider or fallback to local settings.
     *
     * @return string
     */
    private function get_ollama_model(): string {
        global $DB;

        try {
            // Check if AI plugin is installed.
            if (!$DB->get_manager()->table_exists('ai_provider_instances')) {
                throw new \Exception('AI table not found');
            }

            // Try to get from aiprovider_ollama instance.
            $provider = $DB->get_record('ai_provider_instances', [
                'provider' => 'aiprovider_ollama',
                'enabled' => 1
            ], 'actionconfig');

            if (!empty($provider->actionconfig)) {
                $config = json_decode($provider->actionconfig, true);
                if (!empty($config['generate_text']['model'])) {
                    return $config['generate_text']['model'];
                }
            }
        } catch (\Exception $e) {
            // AI plugin not installed, use fallback.
        }

        // Fallback to local settings.
        return get_config('aiplacement_chat', 'ollama_model') ?: 'llama3.1';
    }
    
    /**
     * Отправка сообщения в Ollama + контекст курса
     */
    public function send_message(string $message, int $courseid, int $userid, array $history = []): array {
        global $DB;
        
        $starttime = microtime(true);
        
        try {
            // 1. Получаем контекст курса
            $course_context = \context_course::instance($courseid);
            $system_prompt = $this->placement->get_system_prompt($course_context, $userid);
            
            // 2. Формируем историю чата
            $messages = [
                [
                    'role' => 'system',
                    'content' => $system_prompt
                ]
            ];
            
            // Добавляем последние N сообщений из истории
            $max_history = get_config('coursechat', 'max_history') ?: 50;
            $recent_history = array_slice($history, -$max_history);
            
            foreach ($recent_history as $item) {
                $messages[] = [
                    'role' => $item['role'],
                    'content' => $item['content']
                ];
            }
            
            // Добавляем текущее сообщение
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];
            
            // 3. Отправляем в Ollama
            $response = $this->call_ollama($messages);
            
            $processing_time = round((microtime(true) - $starttime) * 1000);
            
            // 4. Логируем
            $this->log_message($courseid, $userid, $message, $response, $processing_time);
            
            return [
                'success' => true,
                'message' => $response,
                'model' => $this->model,
                'time' => $processing_time
            ];
            
        } catch (\Exception $e) {
            debugging('Ollama chat error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            
            return [
                'success' => false,
                'message' => get_string('error_ollama', 'aiplacement_chat'),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Вызов Ollama API
     */
    private function call_ollama(array $messages): string {
        global $CFG;
        
        require_once($CFG->libdir . '/filelib.php');
        
        $curl = new \curl();
        
        $data = [
            'model' => $this->model,
            'messages' => $messages,
            'stream' => false,
            'temperature' => 0.7,
            'top_p' => 0.9,
            'top_k' => 40
        ];
        
        $options = [
            'CURLOPT_TIMEOUT' => 120, // 2 минуты на ответ
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_POST' => true,
            'CURLOPT_POSTFIELDS' => json_encode($data),
            'CURLOPT_HTTPHEADER' => [
                'Content-Type: application/json'
            ]
        ];
        
        $response = $curl->post($this->ollama_url . '/api/chat', $data, $options);
        $errno = $curl->get_errno();
        
        if ($errno !== 0) {
            throw new \Exception("Ollama connection failed: " . $curl->error);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['message']['content'])) {
            throw new \Exception('Invalid Ollama response');
        }
        
        return $result['message']['content'];
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
        $log->model = $this->model;
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