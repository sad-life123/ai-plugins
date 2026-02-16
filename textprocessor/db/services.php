<?php
// /ai/placement/textprocessor/db/services.php

defined('MOODLE_INTERNAL') || die();

$functions = [
    'textprocessor_process' => [
        'classname' => 'aiplacement_textprocessor\external\process',
        'methodname' => 'execute',
        'description' => 'Process text with AI',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'textprocessor/use'
    ]
];