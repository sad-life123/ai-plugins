<?php
// /ai/placement/textprocessor/classes/action/clean_html.php

namespace aiplacement_textprocessor\action;

use aiplacement_textprocessor\actions;

class clean_html {
    
    public static function get_basename(): string {
        return 'clean_html';
    }
    
    public static function get_description(): string {
        $all = actions::get_all();
        return $all['clean_html']['description'] ?? 'Clean and format HTML';
    }
    
    public static function get_prompt(string $text): string {
        return "Clean and fix this HTML. Remove extra whitespace, fix nesting. Return ONLY HTML code:\n\n$text";
    }
    
    public static function get_name(): string {
        $all = actions::get_all();
        return $all['clean_html']['name'] ?? 'Очистить';
    }
    
    public static function get_icon(): string {
        $all = actions::get_all();
        return $all['clean_html']['icon'] ?? '✨';
    }
}