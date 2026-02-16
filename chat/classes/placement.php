<?php
// /ai/placement/coursechat/classes/placement.php

namespace aiplacement_coursechat;

use core_ai\placement;

class placement extends placement {
    
    public function get_name(): string {
        return 'coursechat';
    }
    
    public function get_title(): string {
        return get_string('pluginname', 'aiplacement_coursechat');
    }
    
    public function get_actions(): array {
        return [
            'chat_message'  // Кастомное действие для чата
        ];
    }
    
    public function is_action_available(string $action, \context $context): bool {
        return has_capability('coursechat/use', $context);
    }
    
    /**
     * Системный промпт для Ollama с контекстом курса
     */
    public function get_system_prompt(\context $context, int $userid = 0): string {
        global $DB;
        
        $courseid = $context->instanceid;
        $course = $DB->get_record('course', ['id' => $courseid]);
        
        $prompt = get_string('system_prompt', 'aiplacement_coursechat');
        
        // Собираем контекст
        $context_collector = new context();
        $course_context = $context_collector->get_course_context($courseid, $userid);
        
        $prompt = str_replace('{course_context}', $course_context, $prompt);
        $prompt = str_replace('{course_name}', $course->fullname, $prompt);
        
        return $prompt;
    }
}