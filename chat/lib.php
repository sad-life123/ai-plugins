<?php
// /ai/placement/coursechat/lib.php

defined('MOODLE_INTERNAL') || die();

/**
 * Добавляем виджет чата на все страницы курса
 */
function coursechat_before_footer() {
    global $PAGE, $COURSE, $USER;
    
    // Только в контексте курса
    if (empty($COURSE->id) || $COURSE->id == SITEID) {
        return;
    }
    
    // Проверка прав
    $context = context_course::instance($COURSE->id);
    if (!has_capability('coursechat/use', $context)) {
        return;
    }
    
    // Подключаем виджет
    $PAGE->requires->js_call_amd('aiplacement_coursechat/widget', 'init', [
        [
            'courseid' => $COURSE->id,
            'userid' => $USER->id,
            'contextid' => $context->id
        ]
    ]);
    
    $PAGE->requires->css('/ai/placement/coursechat/styles.css');
}

/**
 * TinyMCE интеграция (кнопка AI в редакторе)
 */
function coursechat_tiny_plugin_definitions() {
    return [
        'coursechat' => [
            'title' => 'AI Помощник курса',
            'icon' => 'chat',
            'buttons' => [
                'ai_chat' => [
                    'text' => '💬 AI',
                    'action' => 'openCourseChat'
                ]
            ]
        ]
    ];
}