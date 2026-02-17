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
 * Define all of the selectors we will be using on the AI TextProcessor plugin.
 *
 * @module     aiplacement_textprocessor/selectors
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        ELEMENTS: {
            AIDRAWER: '#ai-drawer-textprocessor',
            AIDRAWER_BODY: '#ai-drawer-textprocessor .ai-drawer-body',
            PAGE: '#page',
            AIDRAWER_CLOSE: '#ai-drawer-close-textprocessor',
            TEXTPROCESSOR_CONTAINER: '.textprocessor-drawer-content',
            TEXTPROCESSOR_INPUT: '.textprocessor-input',
            TEXTPROCESSOR_OUTPUT: '.textprocessor-output',
            TEXTPROCESSOR_PREVIEW: '.textprocessor-preview',
            TEXTPROCESSOR_PROCESS_BTN: '.textprocessor-process-btn',
            TEXTPROCESSOR_COPY_BTN: '.textprocessor-copy-btn',
            TEXTPROCESSOR_INSERT_BTN: '.textprocessor-insert-btn',
            TEXTPROCESSOR_ACTION_BTN: '.textprocessor-action-btn',
            JUMPTO: '.ai-textprocessor-controls [data-region="jumpto"]',
            ACTION: '.ai-textprocessor-controls [data-input-type="action"]',
        },
        ACTIONS: {
            TEXTPROCESSOR_OPEN: '.ai-textprocessor-controls [data-action="textprocessor"]',
        }
    };
});
