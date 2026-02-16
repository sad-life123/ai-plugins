<?php
// /ai/placement/quizgen/classes/external/save_to_bank.php

namespace aiplacement_quizgen\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use aiplacement_quizgen\question_bank;

class save_to_bank extends external_api {
    
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'questions' => new external_value(PARAM_RAW, 'Вопросы в JSON'),
            'category_id' => new external_value(PARAM_INT, 'ID категории', VALUE_DEFAULT, 0),
            'courseid' => new external_value(PARAM_INT, 'ID курса', VALUE_DEFAULT, 0)
        ]);
    }
    
    public static function execute(string $questions, int $category_id = 0, int $courseid = 0) {
        global $USER;
        
        $params = self::validate_parameters(self::execute_parameters(), [
            'questions' => $questions,
            'category_id' => $category_id,
            'courseid' => $courseid
        ]);
        
        $context = $courseid ? \context_course::instance($courseid) : \context_system::instance();
        self::validate_context($context);
        require_capability('quizgen/save', $context);
        
        $questions_array = json_decode($params['questions'], true);
        
        $bank = new question_bank();
        $result = $bank->save_multiple($questions_array, $params['category_id'], $params['courseid']);
        
        return [
            'success' => $result['success'] > 0,
            'saved_count' => $result['success'],
            'question_ids' => json_encode($result['question_ids'])
        ];
    }
    
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Успешно'),
            'saved_count' => new external_value(PARAM_INT, 'Сохранено вопросов'),
            'question_ids' => new external_value(PARAM_RAW, 'ID вопросов')
        ]);
    }
}