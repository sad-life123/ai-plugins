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

namespace aiplacement_quizgen;

defined('MOODLE_INTERNAL') || die();

class generator {

    private $placement;

    public function __construct() {
        $this->placement = new placement();
    }
    
    /**
     * ГЕНЕРАЦИЯ ТЕСТА ИЗ ТЕКСТА
     */
    public function generate_quiz(string $text, array $params = [], int $contextid = 1, int $userid = 0): array {
        global $USER;
        
        $start_time = microtime(true);
        
        if (empty(trim($text))) {
            return ['success' => false, 'error' => 'empty_text'];
        }
        
        if ($userid === 0) {
            $userid = $USER->id;
        }
        
        try {
            // 1. Получаем промпт
            $prompt = $this->placement->get_quiz_prompt($text, $params);
            
            // 2. Используем Moodle AI manager для генерации
            $response = $this->call_ai_manager($prompt, $contextid, $userid);
            
            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'AI generation failed'
                ];
            }
            
            // 3. Парсим JSON
            $questions = $this->parse_response($response['content']);
            
            // 4. Валидируем вопросы
            $questions = $this->validate_questions($questions, $params);
            
            $time = round((microtime(true) - $start_time) * 1000);
            
            return [
                'success' => true,
                'questions' => json_encode($questions),
                'count' => count($questions),
                'model' => $response['model'] ?? 'unknown',
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
                    'content' => $responsedata['generatedcontent'] ?? '',
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
     * Проверка доступности AI
     */
    public function check_health(): array {
        try {
            $manager = \core\di::get(\core_ai\manager::class);
            $providers = $manager->get_providers_for_actions([
                \core_ai\aiactions\generate_text::class
            ], true);
            
            if (!empty($providers[\core_ai\aiactions\generate_text::class])) {
                return [
                    'status' => 'ok',
                    'providers' => count($providers[\core_ai\aiactions\generate_text::class])
                ];
            }
            
            return [
                'status' => 'error',
                'message' => 'No AI providers available'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}