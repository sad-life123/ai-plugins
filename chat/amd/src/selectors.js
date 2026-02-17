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
 * Define all of the selectors we will be using on the AI Chat plugin.
 *
 * @module     aiplacement_chat/selectors
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        ELEMENTS: {
            AIDRAWER: '#ai-drawer-chat',
            AIDRAWER_BODY: '#ai-drawer-chat .ai-drawer-body',
            PAGE: '#page',
            AIDRAWER_CLOSE: '#ai-drawer-close-chat',
            CHAT_CONTAINER: '.coursechat-drawer-content',
            CHAT_MESSAGES: '.coursechat-messages',
            CHAT_INPUT: '.coursechat-input',
            CHAT_SEND: '.coursechat-send',
            CHAT_TYPING: '.coursechat-typing',
            CHAT_CLEAR: '.coursechat-clear',
            JUMPTO: '.ai-chat-controls [data-region="jumpto"]',
            ACTION: '.ai-chat-controls [data-input-type="action"]',
        },
        ACTIONS: {
            CHAT_OPEN: '.ai-chat-controls [data-action="chat"]',
            CHAT_CLEAR: '.coursechat-clear',
        }
    };
});
