<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace aiplacement_textprocessor;

class processor {

    public function process(string $text, string $action, \context $context, int $userid = 0): array {
        global $USER;
        
        if (empty(trim($text))) {
            return ['html' => '', 'success' => false, 'message' => 'Empty text'];
        }
        
        if ($userid === 0) {
            $userid = $USER->id;
        }
        
        try {
            // Получаем промпт из класса действия
            $actionclass = '\\aiplacement_textprocessor\\action\\' . $action;
            
            if (!class_exists($actionclass)) {
                throw new \Exception("Action class not found: {$action}");
            }
            
            $prompt = $actionclass::get_prompt($text);
            
            // Вызываем через Moodle AI Manager
            $response = $this->call_ai_manager($prompt, $context->id, $userid);
            
            if (!$response['success']) {
                return [
                    'html' => '',
                    'success' => false,
                    'message' => $response['error'] ?? 'AI generation failed'
                ];
            }
            
            $html = $this->extract_html($response['content']);
            
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
    
    /**
     * Вызов AI через Moodle AI Manager
     */
    private function call_ai_manager(string $prompt, int $contextid, int $userid): array {
        try {
            // Создаем action для генерации текста
            $action = new \core_ai\aiactions\generate_text(
                contextid: $contextid,
                userid: $userid,
                prompttext: $prompt
            );
            
            // Получаем AI manager через DI
            $manager = \core\di::get(\core_ai\manager::class);
            
            // Отправляем action на обработку
            $response = $manager->process_action($action);
            
            if ($response->get_success()) {
                $responsedata = $response->get_response_data();
                return [
                    'success' => true,
                    'content' => $responsedata['generatedcontent'] ?? '',
                    'model' => $responsedata['model'] ?? 'unknown'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->get_errormessage() ?: $response->get_error()
                ];
            }
        } catch (\Exception $e) {
            debugging('AI Manager error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function extract_html(string $response): string {
        // Очищаем от markdown и лишнего
        $html = preg_replace('/```html\s*/i', '', $response);
        $html = preg_replace('/```\s*$/', '', $html);
        $html = trim($html);
        
        return $html;
    }
    
    /**
     * Проверка доступности AI
     */
    public function check_health(): array {
        try {
            $manager = \core\di::get(\core_ai\manager::class);
            $providers = $manager->get_providers_for_actions([
                \core_ai\aiactions\generate_text::class
            ], true);
            
            if (!empty($providers[\core_ai\aiactions\generate_text::class])) {
                return [
                    'status' => 'ok',
                    'providers' => count($providers[\core_ai\aiactions\generate_text::class])
                ];
            }
            
            return [
                'status' => 'error',
                'message' => 'No AI providers available'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}