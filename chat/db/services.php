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

$functions = [
    'aiplacement_chat_send_message' => [
        'classname' => 'aiplacement_chat\external\chat_api',
        'methodname' => 'send_message',
        'description' => 'Send message to course chat',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'aiplacement/chat:use'
    ],

    'aiplacement_chat_get_history' => [
        'classname' => 'aiplacement_chat\external\chat_api',
        'methodname' => 'get_history',
        'description' => 'Get chat history',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'aiplacement/chat:use'
    ],

    'aiplacement_chat_clear_history' => [
        'classname' => 'aiplacement_chat\external\chat_api',
        'methodname' => 'clear_history',
        'description' => 'Clear chat history',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'aiplacement/chat:use'
    ]
];
