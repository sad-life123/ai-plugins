<?php
// /ai/placement/quizgen/classes/external/generate.php

namespace aiplacement_quizgen\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use aiplacement_quizgen\generator;

class generate extends external_api {
    
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'text' => new external_value(PARAM_RAW, 'Исходный текст'),
            'count' => new external_value(PARAM_INT, 'Количество вопросов', VALUE_DEFAULT, 5),
            'type' => new external_value(PARAM_TEXT, 'Тип вопросов', VALUE_DEFAULT, 'multichoice'),
            'difficulty' => new external_value(PARAM_TEXT, 'Сложность', VALUE_DEFAULT, 'medium'),
            'language' => new external_value(PARAM_TEXT, 'Язык', VALUE_DEFAULT, 'ru'),
            'contextid' => new external_value(PARAM_INT, 'ID контекста', VALUE_DEFAULT, 0)
        ]);
    }
    
    public static function execute(string $text, int $count = 5, string $type = 'multichoice', 
                                    string $difficulty = 'medium', string $language = 'ru', int $contextid = 0) {
        global $USER;
        
        $params = self::validate_parameters(self::execute_parameters(), [
            'text' => $text,
            'count' => $count,
            'type' => $type,
            'difficulty' => $difficulty,
            'language' => $language,
            'contextid' => $contextid
        ]);
        
        $context = $contextid ? \context::instance_by_id($contextid) : \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('quizgen/generate', $context);
        
        $generator = new generator();
        $result = $generator->generate_quiz($params['text'], [
            'count' => $params['count'],
            'type' => $params['type'],
            'difficulty' => $params['difficulty'],
            'language' => $params['language']
        ]);
        
        return $result;
    }
    
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Успешно'),
            'questions' => new external_value(PARAM_RAW, 'Вопросы в JSON', VALUE_OPTIONAL),
            'count' => new external_value(PARAM_INT, 'Количество вопросов', VALUE_OPTIONAL),
            'model' => new external_value(PARAM_TEXT, 'Модель', VALUE_OPTIONAL),
            'time' => new external_value(PARAM_INT, 'Время генерации', VALUE_OPTIONAL),
            'error' => new external_value(PARAM_TEXT, 'Ошибка', VALUE_OPTIONAL)
        ]);
    }
}