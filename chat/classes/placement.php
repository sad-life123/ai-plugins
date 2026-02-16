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

namespace aiplacement_chat;

use core_ai\placement as base_placement;

/**
 * Class placement.
 *
 * @package    aiplacement_chat
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement extends base_placement {

    /**
     * Get the list of actions that this placement uses.
     *
     * @return array An array of action class names.
     */
    #[\Override]
    public static function get_action_list(): array {
        return [
            \core_ai\aiactions\generate_text::class,
        ];
    }

    /**
     * Get placement name.
     *
     * @return string
     */
    #[\Override]
    public static function get_name(): string {
        return 'chat';
    }

    /**
     * Get system prompt for chat context.
     *
     * @param \context $context
     * @param int $userid
     * @return string
     */
    public function get_system_prompt(\context $context, int $userid): string {
        $contextobj = new context();
        $coursecontext = $context->get_course_context();
        $courseid = $coursecontext->id;

        $context_text = $contextobj->get_course_context($courseid, $userid);

        return "Ты - AI ассистент курса в Moodle. Ты помогаешь студентам с их вопросами по курсу.

КОНТЕКСТ КУРСА:
{$context_text}

Правила:
1. Отвечай на русском языке (если пользователь пишет на русском)
2. Будь кратким и полезным
3. Если не знаешь ответ - честно скажи об этом
4. Используй контекст курса для релевантных ответов

Отвечай на вопрос студента:";
    }
}
