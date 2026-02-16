<?php
// /ai/placement/coursechat/classes/chat.php

namespace aiplacement_coursechat;

defined('MOODLE_INTERNAL') || die();

class chat {
    
    private $ollama_url;
    private $model;
    private $placement;
    private $context;
    
    public function __construct() {
        $this->ollama_url = get_config('coursechat', 'ollama_url') ?: 'http://localhost:11434';
        $this->ollama_url = rtrim($this->ollama_url, '/');
        $this->model = get_config('coursechat', 'ollama_model') ?: 'llama3.1';
        $this->placement = new placement();
        $this->context = new context();
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
                'message' => get_string('error_ollama', 'aiplacement_coursechat'),
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