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

/**
 * English strings for AI Chat Placement.
 *
 * @package    aiplacement_chat
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'AI Course Chat';
$string['privacy:metadata'] = 'The AI Chat Placement plugin stores chat history for context continuity.';

// Capabilities.
$string['chat:use'] = 'Use AI course chat';

// Settings.
$string['context_heading'] = 'Course Context Settings';
$string['context_heading_desc'] = 'Configure what course data is passed to AI for context.';
$string['context_sources'] = 'Context Sources';
$string['context_sources_desc'] = 'Choose what AI will know about the course.';
$string['context_files'] = 'Course files (PDF, DOCX, TXT) - text extracted';
$string['context_activities'] = 'Activity descriptions, quizzes, forums';
$string['context_sections'] = 'Section names and descriptions';
$string['context_pages'] = 'Page content, lessons, labels';
$string['context_grades'] = 'Student grades (only own context)';
$string['max_context_length'] = 'Max Context Length';
$string['max_context_length_desc'] = 'Maximum characters of context to send to AI.';

$string['chat_heading'] = 'Chat Settings';
$string['chat_heading_desc'] = 'Interface and behavior settings.';
$string['chat_position'] = 'Widget Position';
$string['chat_position_desc'] = 'Where to display the chat button.';
$string['position_right'] = 'Right';
$string['position_left'] = 'Left';
$string['position_bottom'] = 'Bottom center';
$string['max_history'] = 'Message History';
$string['max_history_desc'] = 'How many recent messages to remember.';

$string['info_heading'] = 'Information';
$string['info_heading_desc'] = 'This plugin uses the AI Manager with configured providers (e.g., Ollama). Configure providers in Site administration > AI > Providers.';

// Interface.
$string['chat_title'] = 'AI Course Assistant';
$string['chat_button'] = 'Open chat';
$string['input_placeholder'] = 'Ask about the course...';
$string['send'] = 'Send';
$string['typing'] = 'AI is typing...';
$string['clear_history'] = 'Clear history';
$string['context_info'] = 'I know this course';

// Errors.
$string['error_ai'] = 'AI connection error. Check AI provider settings.';
$string['error_context'] = 'Failed to load course context';
$string['error_general'] = 'An error occurred. Please try again later.';