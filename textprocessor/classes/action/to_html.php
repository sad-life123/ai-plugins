<?php
// /ai/placement/textprocessor/classes/action/to_html.php

namespace aiplacement_textprocessor\action;

use aiplacement_textprocessor\actions;

class to_html {
    
    public static function get_basename(): string {
        return 'to_html';
    }
    
    public static function get_description(): string {
        $all = actions::get_all();
        return $all['to_html']['description'] ?? 'Convert plain text to HTML';
    }
    
    public static function get_prompt(string $text): string {
        return "Convert this text to clean HTML. Use <p>, <h2>, <h3>, <ul>, <ol>, <li>, <strong>, <em>. Return ONLY HTML code:\n\n$text";
    }
    
    public static function get_name(): string {
        $all = actions::get_all();
        return $all['to_html']['name'] ?? 'В HTML';
    }
    
    public static function get_icon(): string {
        $all = actions::get_all();
        return $all['to_html']['icon'] ?? '📄';
    }
}