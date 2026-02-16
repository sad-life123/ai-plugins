<?php
// /ai/placement/textprocessor/lib.php

defined('MOODLE_INTERNAL') || die();

/**
 * Добавляем кнопку в TinyMCE
 */
function textprocessor_tiny_plugin_definitions() {
    return [
        'textprocessor' => [
            'title' => 'AI Text Processor',
            'icon' => 'processor',
            'buttons' => [
                'textprocessor_btn' => [
                    'text' => '✨ AI',
                    'action' => 'openProcessorDialog'
                ]
            ]
        ]
    ];
}

/**
 * Добавляем кнопку в Atto
 */
function textprocessor_atto_plugin_definitions() {
    return [
        'textprocessor' => [
            'title' => 'AI Text Processor',
            'button' => [
                'icon' => 'e/ai',
                'iconComponent' => 'core',
            ],
            'menu' => [
                ['text' => 'В HTML', 'action' => 'toHtml'],
                ['text' => 'Из Markdown', 'action' => 'fromMarkdown'],
                ['text' => 'В таблицу', 'action' => 'toTable'],
                ['text' => 'Очистить HTML', 'action' => 'cleanHtml']
            ]
        ]
    ];
}

/**
 * Добавляем пункт в контекстное меню
 */
function textprocessor_before_footer() {
    global $PAGE;
    
    static $initialized = false;
    if ($initialized) return;
    
    $PAGE->requires->js_call_amd('aiplacement_textprocessor/editor', 'init', [
        ['contextid' => $PAGE->context->id]
    ]);
    
    $PAGE->requires->css('/ai/placement/textprocessor/styles.css');
    
    $initialized = true;
}