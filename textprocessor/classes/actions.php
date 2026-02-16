<?php
// /ai/placement/textprocessor/classes/actions.php

namespace aiplacement_textprocessor;

class actions {
    
    public static function get_all(): array {
        return [
            'to_html' => [
                'name' => 'В HTML',
                'icon' => '📄',
                'description' => 'Преобразовать обычный текст в HTML',
                'default' => true
            ],
            'from_markdown' => [
                'name' => 'Из Markdown',
                'icon' => '🔗',
                'description' => 'Конвертировать Markdown в HTML',
                'default' => false
            ],
            'to_table' => [
                'name' => 'В таблицу',
                'icon' => '📊',
                'description' => 'Преобразовать список в HTML таблицу',
                'default' => false
            ],
            'clean_html' => [
                'name' => 'Очистить',
                'icon' => '✨',
                'description' => 'Очистить и отформатировать HTML',
                'default' => false
            ]
        ];
    }
    
    public static function get_action(string $action): ?array {
        $all = self::get_all();
        return $all[$action] ?? null;
    }
}