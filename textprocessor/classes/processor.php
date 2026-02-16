<?php
// /ai/placement/textprocessor/classes/processor.php

namespace aiplacement_textprocessor;

use core_ai\manager;

class processor {
    
    private $manager;
    
    public function __construct() {
        $this->manager = manager::get_instance();
    }
    
    public function process(string $text, string $action, \context $context): array {
        if (empty(trim($text))) {
            return ['html' => '', 'success' => false, 'message' => 'Empty text'];
        }
        
        try {
            // Получаем промпт из класса действия
            $actionclass = '\\aiplacement_textprocessor\\action\\' . $action;
            
            if (!class_exists($actionclass)) {
                throw new \Exception("Action class not found: {$action}");
            }
            
            $prompt = $actionclass::get_prompt($text);
            
            // Вызываем generate_text провайдера через AI Manager
            $response = $this->manager->generate_text($prompt, [
                'contextid' => $context->id,
                'placement' => 'textprocessor',
                'action' => $action
            ]);
            
            $html = $this->extract_html($response);
            
            return [
                'html' => $html,
                'success' => true,
                'message' => ''
            ];
            
        } catch (\Exception $e) {
            debugging('AI Processor error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            
            return [
                'html' => '',
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    private function extract_html($response): string {
        if (is_array($response) && isset($response['generatedcontent'][0]['text'])) {
            $html = $response['generatedcontent'][0]['text'];
        } else {
            $html = (string) $response;
        }
        
        $html = preg_replace('/```html\s*/i', '', $html);
        $html = preg_replace('/```\s*$/', '', $html);
        $html = trim($html);
        
        return $html;
    }
}