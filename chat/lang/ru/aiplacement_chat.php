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

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Course Chat';
$string['pluginname_desc'] = 'AI chat based on course context (Ollama)';

// Capabilities.
$string['chat:use'] = 'Use AI course chat';
$string['chat:viewcontext'] = 'View course context in chat';

// Interface.
$string['chat_title'] = 'AI Course Assistant';
$string['chat_button'] = 'Open chat';
$string['input_placeholder'] = 'Ask about the course...';
$string['send'] = 'Send';
$string['typing'] = 'AI is typing...';
$string['clear_history'] = 'Clear history';
$string['context_info'] = 'I know this course';

// System prompts.
$string['system_prompt'] = 'You are an AI assistant in Moodle.
Answer student questions based on course context.
Course context: {course_context}

Rules:
1. Answer ONLY from course material
2. If no answer in context - say honestly
3. Be friendly and helpful
4. Answer in the language of the question (Russian/English)';

// Errors.
$string['error_ollama'] = 'AI connection error. Check Ollama settings.';
$string['error_context'] = 'Failed to load course context';
$string['error_general'] = 'An error occurred. Try again later.';

// Settings.
$string['ollama_url'] = 'Ollama URL';
$string['ollama_model'] = 'Ollama Model';
$string['context_sources'] = 'Context Sources';
$string['chat_position'] = 'Chat Position';
$string['max_history'] = 'Max History';
