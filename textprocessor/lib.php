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
 * Library functions for AI TextProcessor plugin.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend TinyMCE editor with AI TextProcessor button.
 *
 * @param array $params TinyMCE parameters
 * @param context $context The context
 * @param array $options Editor options
 */
function aiplacement_textprocessor_tinymce_editor_options(array &$params, \context $context, array $options = []) {
    global $PAGE;
    
    // Check if plugin is available.
    if (!\aiplacement_textprocessor\utils::is_textprocessor_available($context)) {
        return;
    }
    
    // Check if AI is configured.
    if (!\aiplacement_textprocessor\utils::is_ollama_configured()) {
        return;
    }
    
    // Add button to toolbar.
    if (!isset($params['toolbar'])) {
        $params['toolbar'] = '';
    }
    
    // Add AI button at the end of the toolbar.
    $params['toolbar'] = rtrim($params['toolbar'], ' ,') . ', aiplacement_textprocessor';
    
    // Add plugin configuration.
    $params['aiplacement_textprocessor'] = [
        'contextid' => $context->id,
        'wwwroot' => $PAGE->url->out(),
    ];
}

/**
 * Extend Atto editor with AI TextProcessor button.
 *
 * @param array $params Atto parameters
 * @param context $context The context
 * @param array $options Editor options
 */
function aiplacement_textprocessor_atto_editor_options(array &$params, \context $context, array $options = []) {
    global $PAGE;
    
    // Check if plugin is available.
    if (!\aiplacement_textprocessor\utils::is_textprocessor_available($context)) {
        return;
    }
    
    // Check if AI is configured.
    if (!\aiplacement_textprocessor\utils::is_ollama_configured()) {
        return;
    }
    
    // Add button to Atto groups.
    if (!isset($params['groups'])) {
        $params['groups'] = [];
    }
    
    // Add AI group with textprocessor button.
    $params['groups'][] = [
        'group' => 'ai_tools',
        'plugins' => ['aiplacement_textprocessor']
    ];
    
    // Add plugin configuration.
    $params['aiplacement_textprocessor'] = [
        'contextid' => $context->id,
        'wwwroot' => $PAGE->url->out(),
    ];
}

/**
 * Callback to add TextProcessor to TinyMCE plugins list.
 *
 * @return array
 */
function aiplacement_textprocessor_tinymce_plugins() {
    return [
        'aiplacement_textprocessor' => [
            'dir' => 'aiplacement_textprocessor/editor/tinymce',
            'name' => 'aiplacement_textprocessor',
        ]
    ];
}

/**
 * Callback to add TextProcessor to Atto plugins list.
 *
 * @return array
 */
function aiplacement_textprocessor_atto_plugins() {
    return [
        'aiplacement_textprocessor' => [
            'dir' => 'aiplacement_textprocessor/editor/atto',
            'name' => 'aiplacement_textprocessor',
        ]
    ];
}