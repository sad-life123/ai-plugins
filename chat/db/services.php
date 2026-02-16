<?php
// /ai/placement/coursechat/db/services.php

defined('MOODLE_INTERNAL') || die();

$functions = [
    'coursechat_send_message' => [
        'classname' => 'aiplacement_coursechat\external\chat_api',
        'methodname' => 'send_message',
        'description' => 'Send message to course chat',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'coursechat/use'
    ],
    
    'coursechat_get_history' => [
        'classname' => 'aiplacement_coursechat\external\chat_api',
        'methodname' => 'get_history',
        'description' => 'Get chat history',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'coursechat/use'
    ],
    
    'coursechat_clear_history' => [
        'classname' => 'aiplacement_coursechat\external\chat_api',
        'methodname' => 'clear_history',
        'description' => 'Clear chat history',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'coursechat/use'
    ]
];