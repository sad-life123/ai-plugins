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
 * Define all of the selectors we will be using on the AI QuizGen plugin.
 *
 * @module     aiplacement_quizgen/selectors
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        ELEMENTS: {
            AIDRAWER: '#ai-drawer-quizgen',
            AIDRAWER_BODY: '#ai-drawer-quizgen .ai-drawer-body',
            PAGE: '#page',
            AIDRAWER_CLOSE: '#ai-drawer-close-quizgen',
            QUIZGEN_CONTAINER: '.quizgen-drawer-content',
            QUIZGEN_TEXTAREA: '.quizgen-textarea',
            QUIZGEN_COUNT_SELECT: '.quizgen-question-count',
            QUIZGEN_TYPE_SELECT: '.quizgen-question-type',
            QUIZGEN_DIFFICULTY_SELECT: '.quizgen-difficulty',
            QUIZGEN_LANGUAGE_SELECT: '.quizgen-language',
            QUIZGEN_GENERATE_BTN: '.quizgen-generate-btn',
            QUIZGEN_SAVE_ALL_BTN: '.quizgen-save-all-btn',
            QUIZGEN_PROGRESS: '.quizgen-progress',
            QUIZGEN_QUESTIONS_GRID: '.quizgen-questions-grid',
            QUIZGEN_CHAR_COUNTER: '.quizgen-char-counter',
            JUMPTO: '.ai-quizgen-controls [data-region="jumpto"]',
            ACTION: '.ai-quizgen-controls [data-input-type="action"]',
        },
        ACTIONS: {
            QUIZGEN_OPEN: '.ai-quizgen-controls [data-action="quizgen"]',
        }
    };
});
