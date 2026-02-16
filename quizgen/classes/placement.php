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

use core_ai\placement as base_placement;

/**
 * Class placement.
 *
 * @package    aiplacement_quizgen
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement extends base_placement {

    /**
     * Get the list of actions that this placement uses.
     *
     * @return array An array of action class names.
     */
    #[\Override]
    public static function get_action_list(): array {
        return [
            \core_ai\aiactions\generate_text::class,
        ];
    }

    /**
     * Get placement name.
     *
     * @return string
     */
    #[\Override]
    public static function get_name(): string {
        return 'quizgen';
    }

    /**
     * Get prompt for quiz generation.
     *
     * @param string $text
     * @param array $params
     * @return string
     */
    public function get_quiz_prompt(string $text, array $params = []): string {
        $count = $params['count'] ?? 5;
        $type = $params['type'] ?? 'multichoice';
        $difficulty = $params['difficulty'] ?? 'medium';
        $language = $params['language'] ?? 'ru';

        // Type descriptions.
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
        ][$difficulty] ?? 'medium';

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
