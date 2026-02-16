<?php
// /ai/placement/textprocessor/classes/action/from_markdown.php

namespace aiplacement_textprocessor\action;

use aiplacement_textprocessor\actions;

class from_markdown {
    
    public static function get_basename(): string {
        return 'from_markdown';
    }
    
    public static function get_description(): string {
        $all = actions::get_all();
        return $all['from_markdown']['description'] ?? 'Convert Markdown to HTML';
    }
    
    public static function get_prompt(string $text): string {
        return "Convert this Markdown to HTML. Return ONLY HTML code:\n\n$text";
    }
    
    public static function get_name(): string {
        $all = actions::get_all();
        return $all['from_markdown']['name'] ?? 'Из Markdown';
    }
    
    public static function get_icon(): string {
        $all = actions::get_all();
        return $all['from_markdown']['icon'] ?? '🔗';
    }
}