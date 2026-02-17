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
 * Module to load and render the chat for the AI Chat plugin.
 *
 * @module     aiplacement_chat/chat
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/ajax',
    'core/notification',
    'aiplacement_chat/selectors',
    'core/drawer_events',
    'core/pubsub',
    'core_message/message_drawer_helper',
    'core/local/aria/focuslock',
    'core/pagehelpers'
], function(
    Ajax,
    Notification,
    Selectors,
    DrawerEvents,
    PubSub,
    MessageDrawerHelper,
    FocusLock,
    PageHelpers
) {

const AIPChat = class {

    /**
     * The user ID.
     * @type {Integer}
     */
    userId;
    /**
     * The context ID.
     * @type {Integer}
     */
    contextId;
    /**
     * The course ID.
     * @type {Integer}
     */
    courseId;
    /**
     * Whether Ollama is configured.
     * @type {Boolean}
     */
    ollamaConfigured;
    /**
     * Config object.
     * @type {Object}
     */
    config;

    /**
     * Constructor.
     * @param {Integer} userId The user ID.
     * @param {Integer} contextId The context ID.
     * @param {Integer} courseId The course ID.
     * @param {Boolean} ollamaConfigured Whether Ollama is configured.
     * @param {Object} config Configuration object.
     */
    constructor(userId, contextId, courseId, ollamaConfigured, config = {}) {
        this.userId = userId;
        this.contextId = contextId;
        this.courseId = courseId;
        this.ollamaConfigured = ollamaConfigured;
        this.config = config;
        this.messages = [];

        this.aiDrawerElement = document.querySelector(Selectors.ELEMENTS.AIDRAWER);
        this.aiDrawerBodyElement = document.querySelector(Selectors.ELEMENTS.AIDRAWER_BODY);
        this.pageElement = document.querySelector(Selectors.ELEMENTS.PAGE);
        this.jumpToElement = document.querySelector(Selectors.ELEMENTS.JUMPTO);
        this.actionElement = document.querySelector(Selectors.ELEMENTS.ACTION);

        this.chatContainer = document.querySelector(Selectors.ELEMENTS.CHAT_CONTAINER);
        this.messagesContainer = this.chatContainer?.querySelector(Selectors.ELEMENTS.CHAT_MESSAGES);
        this.input = this.chatContainer?.querySelector(Selectors.ELEMENTS.CHAT_INPUT);
        this.sendBtn = this.chatContainer?.querySelector(Selectors.ELEMENTS.CHAT_SEND);
        this.typingIndicator = this.chatContainer?.querySelector(Selectors.ELEMENTS.CHAT_TYPING);
        this.clearBtn = this.chatContainer?.querySelector(Selectors.ELEMENTS.CHAT_CLEAR);

        this.isDrawerFocusLocked = false;

        this.registerEventListeners();

        // Add welcome message if Ollama is configured.
        if (this.ollamaConfigured) {
            this.addMessage('assistant', '👋 Hello! I am the AI assistant for this course. Ask me about the materials, assignments, or course structure.');
        }
    }

    /**
     * Register event listeners.
     */
    registerEventListeners() {
        // Handle action button click to open drawer.
        document.addEventListener('click', (e) => {
            const chatAction = e.target.closest(Selectors.ACTIONS.CHAT_OPEN);
            if (chatAction) {
                e.preventDefault();
                this.openAIDrawer();
            }
            
            // Close AI drawer.
            const closeAiDrawer = e.target.closest(Selectors.ELEMENTS.AIDRAWER_CLOSE);
            if (closeAiDrawer) {
                e.preventDefault();
                this.closeAIDrawer();
            }
        });

        // Send message on button click.
        if (this.sendBtn) {
            this.sendBtn.addEventListener('click', () => this.sendMessage());
        }

        // Send on Enter (Ctrl+Enter for new line).
        if (this.input) {
            this.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Enable/disable button.
            this.input.addEventListener('input', () => {
                if (this.sendBtn) {
                    this.sendBtn.disabled = this.input.value.trim().length === 0 || !this.ollamaConfigured;
                }
            });
        }

        // Clear history.
        if (this.clearBtn) {
            this.clearBtn.addEventListener('click', () => this.clearHistory());
        }

        // Handle Escape key to close drawer.
        document.addEventListener('keydown', e => {
            if (this.isAIDrawerOpen() && e.key === 'Escape') {
                this.closeAIDrawer();
            }
        });

        // Close AI drawer if message drawer is shown.
        PubSub.subscribe(DrawerEvents.DRAWER_SHOWN, () => {
            if (this.isAIDrawerOpen()) {
                this.closeAIDrawer();
            }
        });

        // Focus on the AI drawer's close button when the jump-to element is focused.
        if (this.jumpToElement) {
            this.jumpToElement.addEventListener('focus', () => {
                const closeBtn = this.aiDrawerElement?.querySelector(Selectors.ELEMENTS.AIDRAWER_CLOSE);
                if (closeBtn) {
                    closeBtn.focus();
                }
            });
        }

        // Focus on the action element when the AI drawer container receives focus.
        if (this.aiDrawerElement && this.actionElement) {
            this.aiDrawerElement.addEventListener('focus', () => {
                this.actionElement.focus();
            });
        }

        // Remove active from the action element when it loses focus.
        if (this.actionElement) {
            this.actionElement.addEventListener('blur', () => {
                this.actionElement.classList.remove('active');
            });
        }
    }

    /**
     * Check if the AI drawer is open.
     * @return {boolean} True if the AI drawer is open, false otherwise.
     */
    isAIDrawerOpen() {
        return this.aiDrawerElement?.classList.contains('show');
    }

    /**
     * Open the AI drawer.
     */
    openAIDrawer() {
        if (!this.aiDrawerElement) {
            return;
        }

        // Close message drawer if it is shown.
        MessageDrawerHelper.hide();

        this.aiDrawerElement.classList.add('show');
        this.aiDrawerElement.setAttribute('tabindex', 0);
        this.aiDrawerBodyElement?.setAttribute('aria-live', 'polite');

        if (this.pageElement && !this.pageElement.classList.contains('show-drawer-right')) {
            this.addPadding();
        }

        if (this.jumpToElement) {
            this.jumpToElement.setAttribute('tabindex', 0);
            this.jumpToElement.focus();
        }

        // If the AI drawer is opened on a small screen, we need to trap the focus tab within the AI drawer.
        if (PageHelpers.isSmall()) {
            FocusLock.trapFocus(this.aiDrawerElement);
            this.aiDrawerElement.setAttribute('aria-modal', 'true');
            this.aiDrawerElement.setAttribute('role', 'dialog');
            this.isDrawerFocusLocked = true;
        }
    }

    /**
     * Close the AI drawer.
     */
    closeAIDrawer() {
        if (!this.aiDrawerElement) {
            return;
        }

        // Untrap focus if it was locked.
        if (this.isDrawerFocusLocked) {
            FocusLock.untrapFocus();
            this.aiDrawerElement.removeAttribute('aria-modal');
            this.aiDrawerElement.setAttribute('role', 'region');
        }

        this.aiDrawerElement.classList.remove('show');
        this.aiDrawerElement.setAttribute('tabindex', -1);

        if (this.aiDrawerBodyElement) {
            this.aiDrawerBodyElement.removeAttribute('aria-live');
        }

        if (this.pageElement && this.pageElement.classList.contains('show-drawer-right')) {
            this.removePadding();
        }

        if (this.jumpToElement) {
            this.jumpToElement.setAttribute('tabindex', -1);
        }

        // Set focus on the action element.
        if (this.actionElement) {
            this.actionElement.classList.add('active');
            this.actionElement.focus();
        }
    }

    /**
     * Toggle the AI drawer.
     */
    toggleAIDrawer() {
        if (this.isAIDrawerOpen()) {
            this.closeAIDrawer();
        } else {
            this.openAIDrawer();
        }
    }

    /**
     * Add padding to the page to make space for the AI drawer.
     */
    addPadding() {
        if (this.pageElement && this.aiDrawerBodyElement) {
            this.pageElement.classList.add('show-drawer-right');
            this.aiDrawerBodyElement.dataset.removepadding = '1';
        }
    }

    /**
     * Remove padding from the page.
     */
    removePadding() {
        if (this.pageElement && this.aiDrawerBodyElement) {
            this.pageElement.classList.remove('show-drawer-right');
            this.aiDrawerBodyElement.dataset.removepadding = '0';
        }
    }

    /**
     * Send a message to the chat.
     */
    async sendMessage() {
        // Check if Ollama is configured.
        if (!this.ollamaConfigured) {
            Notification.alert('Ollama not configured', 'Please configure Ollama in plugin settings to use chat.');
            return;
        }

        const text = this.input?.value?.trim();
        if (!text) {
            return;
        }

        // Add user message.
        this.addMessage('user', text);

        // Clear input.
        if (this.input) {
            this.input.value = '';
        }
        if (this.sendBtn) {
            this.sendBtn.disabled = true;
        }

        // Show typing indicator.
        this.setTyping(true);

        try {
            const response = await Ajax.call([{
                methodname: 'aiplacement_chat_send_message',
                args: {
                    message: text,
                    courseid: this.courseId,
                    history: JSON.stringify(this.messages.slice(-10))
                }
            }])[0];

            this.setTyping(false);

            if (response.success) {
                this.addMessage('assistant', response.message);
            } else {
                this.addMessage('system', response.message || 'Error');
                Notification.alert('Error', response.message || 'Failed to get response from AI');
            }

        } catch (error) {
            this.setTyping(false);
            Notification.exception(error);
            this.addMessage('system', 'Connection error: ' + error.message);
        }
    }

    /**
     * Add a message to the chat.
     * @param {String} role The role (user, assistant, system).
     * @param {String} content The message content.
     */
    addMessage(role, content) {
        if (!this.messagesContainer) {
            return;
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `coursechat-message ${role}`;

        const time = new Date().toLocaleTimeString('ru-RU', {
            hour: '2-digit',
            minute: '2-digit'
        });

        let avatar = '🤖';
        if (role === 'user') avatar = '👤';
        if (role === 'system') avatar = '⚙️';

        messageDiv.innerHTML = `
            <div class="message-avatar">${avatar}</div>
            <div class="message-content">
                <div>${this.formatMessage(content)}</div>
                <div class="message-time">${time}</div>
            </div>
        `;

        this.messagesContainer.appendChild(messageDiv);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;

        this.messages.push({role, content, time});
    }

    /**
     * Format message content.
     * @param {String} text The text to format.
     * @return {String} The formatted text.
     */
    formatMessage(text) {
        // Simple formatting.
        text = text.replace(/\n/g, '<br>');
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
        text = text.replace(/`(.*?)`/g, '<code>$1</code>');
        return text;
    }

    /**
     * Show or hide typing indicator.
     * @param {Boolean} typing Whether to show typing indicator.
     */
    setTyping(typing) {
        if (this.typingIndicator) {
            this.typingIndicator.style.display = typing ? 'flex' : 'none';
        }

        if (typing && this.messagesContainer) {
            // Auto-scroll.
            setTimeout(() => {
                this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            }, 100);
        }
    }

    /**
     * Clear chat history.
     */
    clearHistory() {
        Ajax.call([{
            methodname: 'aiplacement_chat_clear_history',
            args: { courseid: this.courseId }
        }]);

        if (this.messagesContainer) {
            this.messagesContainer.innerHTML = '';
        }
        this.messages = [];

        // Welcome message.
        this.addMessage('assistant', '👋 Hello! I am the AI assistant for this course. Ask me about the materials, assignments, or course structure.');
    }
};

return AIPChat;
});
