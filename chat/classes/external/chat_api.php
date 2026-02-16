<?php
// /ai/placement/coursechat/classes/external/chat_api.php

namespace aiplacement_coursechat\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use aiplacement_coursechat\chat;

class chat_api extends external_api {
    
    /**
     * Параметры для отправки сообщения
     */
    public static function send_message_parameters(): external_function_parameters {
        return new external_function_parameters([
            'message' => new external_value(PARAM_TEXT, 'Сообщение пользователя'),
            'courseid' => new external_value(PARAM_INT, 'ID курса'),
            'history' => new external_value(PARAM_RAW, 'История чата (JSON)', VALUE_DEFAULT, '[]')
        ]);
    }
    
    /**
     * Отправка сообщения
     */
    public static function send_message(string $message, int $courseid, string $history = '[]') {
        global $USER;
        
        $params = self::validate_parameters(self::send_message_parameters(), [
            'message' => $message,
            'courseid' => $courseid,
            'history' => $history
        ]);
        
        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('coursechat/use', $context);
        
        $history_array = json_decode($params['history'], true) ?: [];
        
        $chat = new chat();
        $result = $chat->send_message(
            $params['message'],
            $params['courseid'],
            $USER->id,
            $history_array
        );
        
        return $result;
    }
    
    /**
     * Возврат результата
     */
    public static function send_message_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Успешно'),
            'message' => new external_value(PARAM_RAW, 'Ответ AI'),
            'model' => new external_value(PARAM_TEXT, 'Модель', VALUE_OPTIONAL),
            'time' => new external_value(PARAM_INT, 'Время обработки', VALUE_OPTIONAL),
            'error' => new external_value(PARAM_TEXT, 'Ошибка', VALUE_OPTIONAL)
        ]);
    }
    
    /**
     * Получение истории
     */
    public static function get_history_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'ID курса'),
            'limit' => new external_value(PARAM_INT, 'Лимит сообщений', VALUE_DEFAULT, 50)
        ]);
    }
    
    public static function get_history(int $courseid, int $limit = 50) {
        global $USER;
        
        $params = self::validate_parameters(self::get_history_parameters(), [
            'courseid' => $courseid,
            'limit' => $limit
        ]);
        
        $context = \context_course::instance($courseid);
        self::validate_context($context);
        
        $chat = new chat();
        $history = $chat->get_history($courseid, $USER->id, $limit);
        
        return ['history' => json_encode($history)];
    }
    
    public static function get_history_returns(): external_single_structure {
        return new external_single_structure([
            'history' => new external_value(PARAM_RAW, 'История в JSON')
        ]);
    }
    
    /**
     * Очистка истории
     */
    public static function clear_history_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'ID курса')
        ]);
    }
    
    public static function clear_history(int $courseid) {
        global $USER;
        
        $params = self::validate_parameters(self::clear_history_parameters(), [
            'courseid' => $courseid
        ]);
        
        $context = \context_course::instance($courseid);
        self::validate_context($context);
        
        $chat = new chat();
        $result = $chat->clear_history($courseid, $USER->id);
        
        return ['success' => $result];
    }
    
    public static function clear_history_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Успешно')
        ]);
    }
}