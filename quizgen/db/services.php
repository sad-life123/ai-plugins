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

    'aiplacement_quizgen_generate' => [
        'classname' => 'aiplacement_quizgen\external\generate',
        'methodname' => 'execute',
        'description' => 'Generate quiz questions from text',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'aiplacement/quizgen:generate'
    ],

    'aiplacement_quizgen_save_to_bank' => [
        'classname' => 'aiplacement_quizgen\external\save_to_bank',
        'methodname' => 'execute',
        'description' => 'Save questions to question bank',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'aiplacement/quizgen:save'
    ],
];
