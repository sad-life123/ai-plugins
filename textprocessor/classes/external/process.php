<?php
// /ai/placement/textprocessor/classes/external/process.php

namespace aiplacement_textprocessor\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use aiplacement_textprocessor\processor;

class process extends external_api {
    
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'text' => new external_value(PARAM_RAW, 'Text to process'),
            'action' => new external_value(PARAM_ALPHA, 'Action to perform', VALUE_DEFAULT, 'to_html'),
            'contextid' => new external_value(PARAM_INT, 'Context ID', VALUE_DEFAULT, 0)
        ]);
    }
    
    public static function execute(string $text, string $action = 'to_html', int $contextid = 0) {
        global $USER;
        
        $params = self::validate_parameters(self::execute_parameters(), [
            'text' => $text,
            'action' => $action,
            'contextid' => $contextid
        ]);
        
        $context = $contextid ? \context::instance_by_id($contextid) : \context_user::instance($USER->id);
        self::validate_context($context);
        require_capability('textprocessor/use', $context);
        
        $processor = new processor();
        return $processor->process($params['text'], $params['action'], $context);
    }
    
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'html' => new external_value(PARAM_RAW, 'Processed HTML'),
            'success' => new external_value(PARAM_BOOL, 'Success'),
            'message' => new external_value(PARAM_TEXT, 'Error message')
        ]);
    }
}