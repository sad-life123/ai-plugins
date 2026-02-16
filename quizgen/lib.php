<?php
// /ai/placement/quizgen/lib.php

defined('MOODLE_INTERNAL') || die();

/**
 * Добавляем кнопку "Сгенерировать тест" в редактор
 */
function quizgen_tiny_plugin_definitions() {
    return [
        'quizgen' => [
            'title' => 'AI Генератор тестов',
            'icon' => 'quiz',
            'buttons' => [
                'generate_quiz' => [
                    'text' => '📝 AI Тест',
                    'action' => 'openQuizGenerator'
                ]
            ]
        ]
    ];
}

/**
 * Добавляем пункт в меню курса
 */
function quizgen_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;
    
    if (has_capability('quizgen/generate', $context)) {
        $url = new moodle_url('/ai/placement/quizgen/index.php', ['courseid' => $course->id]);
        $navigation->add(
            '📝 AI Генератор тестов',
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'quizgen',
            new pix_icon('i/questions', '')
        );
    }
}

/**
 * Обработка перед удалением курса
 */
function quizgen_pre_course_delete($course) {
    // Очищаем сгенерированные вопросы
    $category = question_get_default_category($course->id);
    if ($category) {
        $questions = get_questions_in_category($category->id);
        foreach ($questions as $question) {
            if (strpos($question->name, '[AI]') === 0) {
                question_delete_question($question->id);
            }
        }
    }
}