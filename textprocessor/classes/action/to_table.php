<?php
// /ai/placement/textprocessor/classes/action/to_table.php

namespace aiplacement_textprocessor\action;

use aiplacement_textprocessor\actions;

class to_table {
    
    public static function get_basename(): string {
        return 'to_table';
    }
    
    public static function get_description(): string {
        $all = actions::get_all();
        return $all['to_table']['description'] ?? 'Convert list to HTML table';
    }
    
    public static function get_prompt(string $text): string {
        return "Convert this data to an HTML table. Use <table>, <thead>, <tbody>. Return ONLY HTML code:\n\n$text";
    }
    
    public static function get_name(): string {
        $all = actions::get_all();
        return $all['to_table']['name'] ?? 'В таблицу';
    }
    
    public static function get_icon(): string {
        $all = actions::get_all();
        return $all['to_table']['icon'] ?? '📊';
    }
}