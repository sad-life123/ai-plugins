<?php
// /ai/placement/quizgen/classes/generator.php

namespace aiplacement_quizgen;

defined('MOODLE_INTERNAL') || die();

class generator {
    
    private $ollama_url;
    private $model;
    private $placement;
    
    public function __construct() {
        $this->ollama_url = get_config('quizgen', 'ollama_url') ?: 'http://localhost:11434';
        $this->ollama_url = rtrim($this->ollama_url, '/');
        $this->model = get_config('quizgen', 'ollama_model') ?: 'qwen2.5:7b';
        $this->placement = new placement();
    }
    
    /**
     * ГЕНЕРАЦИЯ ТЕСТА ИЗ ТЕКСТА
     */
    public function generate_quiz(string $text, array $params = []): array {
        $start_time = microtime(true);
        
        if (empty(trim($text))) {
            return ['success' => false, 'error' => 'empty_text'];
        }
        
        try {
            // 1. Получаем промпт
            $prompt = $this->placement->get_quiz_prompt($text, $params);
            
            // 2. Отправляем в Ollama
            $response = $this->call_ollama($prompt);
            
            // 3. Парсим JSON
            $questions = $this->parse_response($response);
            
            // 4. Валидируем вопросы
            $questions = $this->validate_questions($questions, $params);
            
            $time = round((microtime(true) - $start_time) * 1000);
            
            return [
                'success' => true,
                'questions' => $questions,
                'count' => count($questions),
                'model' => $this->model,
                'time' => $time
            ];
            
        } catch (\Exception $e) {
            debugging('Quiz generation error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * ВЫЗОВ OLLAMA API
     */
    private function call_ollama(string $prompt): string {
        global $CFG;
        
        require_once($CFG->libdir . '/filelib.php');
        
        $curl = new \curl();
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI that generates educational quiz questions. You ALWAYS respond with valid JSON only. Never include explanations, markdown, or any text outside the JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'stream' => false,
            'temperature' => 0.4,  // Баланс креативности и точности
            'top_p' => 0.9,
            'top_k' => 40
        ];
        
        $options = [
            'CURLOPT_TIMEOUT' => 120,
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
     * ПАРСИНГ JSON ОТВЕТА
     */
    private function parse_response(string $response): array {
        // Очищаем ответ от markdown и лишнего текста
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        $response = trim($response);
        
        // Находим JSON массив
        if (strpos($response, '[') === 0) {
            $json = $response;
        } else {
            preg_match('/\[[\s\S]*\]/', $response, $matches);
            $json = $matches[0] ?? '[]';
        }
        
        $questions = json_decode($json, true);
        
        if (!is_array($questions)) {
            // Fallback: парсим построчно
            $questions = $this->parse_fallback($response);
        }
        
        return array_slice($questions, 0, 20); // Максимум 20 вопросов
    }
    
    /**
     * FALLBACK парсинг если JSON сломался
     */
    private function parse_fallback(string $text): array {
        $questions = [];
        $lines = explode("\n", $text);
        
        $current = [];
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (strpos($line, 'Question:') === 0 || strpos($line, 'Вопрос:') === 0) {
                if (!empty($current)) {
                    $questions[] = $this->normalize_question($current);
                    $current = [];
                }
                $current['question'] = substr($line, strpos($line, ':') + 1);
            } elseif (strpos($line, 'A)') === 0 || strpos($line, '1)') === 0) {
                $current['options'][] = substr($line, strpos($line, ')') + 1);
            } elseif (strpos($line, 'Correct:') === 0 || strpos($line, 'Правильный:') === 0) {
                $answer = substr($line, strpos($line, ':') + 1);
                $current['correct'] = $this->find_correct_index($answer, $current['options'] ?? []);
            } elseif (strpos($line, 'Explanation:') === 0 || strpos($line, 'Пояснение:') === 0) {
                $current['explanation'] = substr($line, strpos($line, ':') + 1);
            }
        }
        
        if (!empty($current)) {
            $questions[] = $this->normalize_question($current);
        }
        
        return $questions;
    }
    
    /**
     * Нормализация вопроса
     */
    private function normalize_question(array $q): array {
        return [
            'question' => $q['question'] ?? 'Untitled question',
            'type' => 'multichoice',
            'options' => array_slice($q['options'] ?? [], 0, 4),
            'correct' => intval($q['correct'] ?? 0),
            'explanation' => $q['explanation'] ?? '',
            'tags' => $q['tags'] ?? []
        ];
    }
    
    private function find_correct_index(string $answer, array $options): int {
        $answer = strtoupper(trim($answer));
        
        if (preg_match('/[A-D]/', $answer, $matches)) {
            return ord($matches[0]) - ord('A');
        }
        
        foreach ($options as $i => $opt) {
            if (stripos($opt, $answer) !== false) {
                return $i;
            }
        }
        
        return 0;
    }
    
    /**
     * Валидация вопросов
     */
    private function validate_questions(array $questions, array $params): array {
        $validated = [];
        $type = $params['type'] ?? 'multichoice';
        
        foreach ($questions as $q) {
            if (empty($q['question'])) continue;
            
            $question = [
                'id' => uniqid('q_'),
                'question' => strip_tags($q['question']),
                'type' => $q['type'] ?? $type,
                'options' => [],
                'correct' => 0,
                'explanation' => strip_tags($q['explanation'] ?? ''),
                'tags' => $q['tags'] ?? []
            ];
            
            if ($question['type'] === 'multichoice') {
                $options = array_slice($q['options'] ?? [], 0, 4);
                while (count($options) < 4) {
                    $options[] = 'Вариант ' . (count($options) + 1);
                }
                $question['options'] = $options;
                $question['correct'] = min($q['correct'] ?? 0, 3);
                
            } elseif ($question['type'] === 'truefalse') {
                $question['options'] = ['Верно', 'Неверно'];
                $question['correct'] = ($q['correct'] ?? 0) == 0 ? 0 : 1;
                
            } elseif ($question['type'] === 'shortanswer') {
                $question['correctanswer'] = $q['correctanswer'] ?? $q['options'][0] ?? '';
            }
            
            $validated[] = $question;
        }
        
        return $validated;
    }
    
    /**
     * Проверка доступности Ollama
     */
    public function check_health(): array {
        try {
            $curl = new \curl();
            $response = $curl->get($this->ollama_url . '/api/tags');
            $result = json_decode($response, true);
            
            $models = [];
            foreach ($result['models'] ?? [] as $model) {
                $models[] = $model['name'];
            }
            
            return [
                'status' => 'ok',
                'models' => $models
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}