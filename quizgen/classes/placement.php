<?php
// /ai/placement/quizgen/classes/placement.php

namespace aiplacement_quizgen;

use core_ai\placement;

class placement extends placement {
    
    public function get_name(): string {
        return 'quizgen';
    }
    
    public function get_title(): string {
        return get_string('pluginname', 'aiplacement_quizgen');
    }
    
    public function get_actions(): array {
        return [
            'generate_quiz',      // Основное действие
            'generate_single',    // Перегенерировать вопрос
            'improve_question'    // Улучшить формулировку
        ];
    }
    
    public function is_action_available(string $action, \context $context): bool {
        return has_capability('quizgen/generate', $context);
    }
    
    /**
     * Получить промпт для генерации теста
     */
    public function get_quiz_prompt(string $text, array $params = []): string {
        $count = $params['count'] ?? 5;
        $type = $params['type'] ?? 'multichoice';
        $difficulty = $params['difficulty'] ?? 'medium';
        $language = $params['language'] ?? 'ru';
        
        // Карта типов вопросов
        $type_descriptions = [
            'multichoice' => 'multiple choice questions with 4 options, one correct answer, and an explanation',
            'truefalse' => 'true/false statements with explanation',
            'shortanswer' => 'short answer questions (1-2 words) with correct answer and explanation',
            'matching' => 'matching questions: 4-5 pairs of terms and definitions',
            'essay' => 'essay questions with detailed rubric',
            'combined' => 'mix of different question types'
        ];
        
        $type_desc = $type_descriptions[$type] ?? $type_descriptions['multichoice'];
        
        $difficulty_desc = [
            'easy' => 'basic, factual knowledge',
            'medium' => 'application and comprehension',
            'hard' => 'analysis, synthesis, and evaluation'
        ][$difficulty];
        
        $language_names = ['ru' => 'Russian', 'en' => 'English'];
        $lang = $language_names[$language] ?? 'Russian';
        
        $prompt = "Generate {$count} {$type_desc} based on this text.
        Difficulty: {$difficulty_desc}.
        Language: {$lang}.
        
        IMPORTANT: Return ONLY valid JSON array. NO other text, NO markdown, NO comments.
        
        [
            {
                \"question\": \"Question text\",
                \"type\": \"{$type}\",
                \"options\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"] (for multichoice),
                \"correct\": 0 (index of correct answer),
                \"explanation\": \"Why this is correct\",
                \"tags\": [\"topic1\", \"topic2\"]
            }
        ]
        
        Text: {$text}";
        
        return $prompt;
    }
}