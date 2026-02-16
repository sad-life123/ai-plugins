<?php
// /ai/placement/quizgen/db/services.php

defined('MOODLE_INTERNAL') || die();

$functions = [
    
    'quizgen_generate' => [
        'classname' => 'aiplacement_quizgen\external\generate',
        'methodname' => 'execute',
        'description' => 'Generate quiz questions from text',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'quizgen/generate'
    ],
    
    'quizgen_save_to_bank' => [
        'classname' => 'aiplacement_quizgen\external\save_to_bank',
        'methodname' => 'execute',
        'description' => 'Save questions to question bank',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'quizgen/save'
    ],
];