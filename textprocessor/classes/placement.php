<?php
// /ai/placement/textprocessor/classes/placement.php

namespace aiplacement_textprocessor;

use core_ai\placement as base_placement;

class placement extends base_placement {
    
    /**
     * Уникальное имя плагина
     */
    public static function get_name(): string {
        return 'textprocessor';
    }
    
    /**
     * Человеко-читаемое название
     */
    public static function get_title(): string {
        return get_string('pluginname', 'aiplacement_textprocessor');
    }
    
    /**
     * Простой список действий
     */
    public static function get_actions(): array {
        return array_keys(actions::get_all());
    }
    
    /**
     * Список действий с классами
     */
    public static function get_action_list(): array {
        $actions = actions::get_all();
        $list = [];
        
        foreach (array_keys($actions) as $action) {
            $list[$action] = 'aiplacement_textprocessor\\action\\' . $action;
        }
        
        return $list;
    }
    
    /**
     * ⚠️ КЛЮЧЕВОЙ МЕТОД - связывает действия с провайдером
     */
    public static function get_provider_actions(): array {
        return [
            'to_html' => 'generate_text',
            'from_markdown' => 'generate_text',
            'to_table' => 'generate_text',
            'clean_html' => 'generate_text'
        ];
    }
    
    /**
     * Проверка доступности действия
     */
    public static function is_action_available(string $action, \context $context): bool {
        if (!isloggedin()) {
            return false;
        }
        return has_capability('textprocessor/use', $context);
    }
    
    /**
     * ⚠️ ВАЖНО: указываем, что действия используют AI
     */
    public static function is_action_ai_provider_based(string $action): bool {
        return true; // Все действия используют AI провайдера
    }
}