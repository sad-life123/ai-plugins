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

    private $ollama_url;
    private $model;

    public function __construct() {
        // Try to get Ollama URL from provider first, then fallback to local settings.
        $this->ollama_url = $this->get_ollama_url();
        $this->model = $this->get_ollama_model();
    }

    /**
     * Get Ollama URL from provider or fallback to local settings.
     *
     * @return string
     */
    private function get_ollama_url(): string {
        global $DB;

        try {
            // Check if AI plugin is installed.
            if (!$DB->get_manager()->table_exists('ai_provider_instances')) {
                throw new \Exception('AI table not found');
            }

            // Try to get from aiprovider_ollama instance.
            $provider = $DB->get_record('ai_provider_instances', [
                'provider' => 'aiprovider_ollama',
                'enabled' => 1
            ], 'config');

            if (!empty($provider->config)) {
                $config = json_decode($provider->config, true);
                if (!empty($config['endpoint'])) {
                    return rtrim($config['endpoint'], '/');
                }
            }
        } catch (\Exception $e) {
            // AI plugin not installed, use fallback.
        }

        // Fallback to local settings.
        return get_config('aiplacement_textprocessor', 'ollama_url') ?: 'http://localhost:11434';
    }

    /**
     * Get Ollama model from provider or fallback to local settings.
     *
     * @return string
     */
    private function get_ollama_model(): string {
        global $DB;

        try {
            // Check if AI plugin is installed.
            if (!$DB->get_manager()->table_exists('ai_provider_instances')) {
                throw new \Exception('AI table not found');
            }

            // Try to get from aiprovider_ollama instance.
            $provider = $DB->get_record('ai_provider_instances', [
                'provider' => 'aiprovider_ollama',
                'enabled' => 1
            ], 'actionconfig');

            if (!empty($provider->actionconfig)) {
                $config = json_decode($provider->actionconfig, true);
                if (!empty($config['generate_text']['model'])) {
                    return $config['generate_text']['model'];
                }
            }
        } catch (\Exception $e) {
            // AI plugin not installed, use fallback.
        }

        // Fallback to local settings.
        return get_config('aiplacement_textprocessor', 'ollama_model') ?: 'qwen2:1.5b';
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
            
            // Вызываем Ollama напрямую
            $response = $this->call_ollama($prompt);
            
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
    
    /**
     * Вызов Ollama API напрямую
     */
    private function call_ollama(string $prompt): string {
        global $CFG;
        
        require_once($CFG->libdir . '/filelib.php');
        
        $curl = new \curl();
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an HTML generator. Return ONLY valid HTML code. No explanations, no markdown, no code blocks. Just clean HTML.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'stream' => false,
            'temperature' => 0.3,
            'top_p' => 0.9
        ];
        
        $options = [
            'CURLOPT_TIMEOUT' => 120,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_POST' => true,
            'CURLOPT_POSTFIELDS' => json_encode($data),
            'CURLOPT_HTTPHEADER' => [
                'Content-Type: application/json'
            ]
        ];
        
        $response = $curl->post($this->ollama_url . '/api/chat', $data, $options);
        $errno = $curl->get_errno();
        
        if ($errno !== 0) {
            throw new \Exception("Ollama connection failed: " . $curl->error);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['message']['content'])) {
            throw new \Exception('Invalid Ollama response');
        }
        
        return $result['message']['content'];
    }
    
    private function extract_html(string $response): string {
        // Очищаем от markdown и лишнего
        $html = preg_replace('/```html\s*/i', '', $response);
        $html = preg_replace('/```\s*$/', '', $html);
        $html = trim($html);
        
        return $html;
    }
    
    /**
     * Проверка доступности Ollama
     */
    public function check_health(): array {
        try {
            $curl = new \curl();
            $response = $curl->get($this->ollama_url . '/api/tags');
            $result = json_decode($response, true);
            
            $models = [];
            foreach ($result['models'] ?? [] as $model) {
                $models[] = $model['name'];
            }
            
            return [
                'status' => 'ok',
                'models' => $models,
                'current' => $this->model
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
