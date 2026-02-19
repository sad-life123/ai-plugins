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
 * Module to load and render the text processor for the AI TextProcessor plugin.
 *
 * @module     aiplacement_textprocessor/main
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/ajax',
    'core/notification',
    'aiplacement_textprocessor/selectors',
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

const AIPTextProcessor = class {

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
     * Current action.
     * @type {String}
     */
    currentAction;

    /**
     * Constructor.
     * @param {Integer} userId The user ID.
     * @param {Integer} contextId The context ID.
     * @param {Boolean} ollamaConfigured Whether Ollama is configured.
     * @param {Object} config Configuration object.
     */
    constructor(userId, contextId, ollamaConfigured, config = {}) {
        this.userId = userId;
        this.contextId = contextId;
        this.ollamaConfigured = ollamaConfigured;
        this.config = config;
        this.currentAction = 'to_html';

        this.aiDrawerElement = document.querySelector(Selectors.ELEMENTS.AIDRAWER);
        this.aiDrawerBodyElement = document.querySelector(Selectors.ELEMENTS.AIDRAWER_BODY);
        this.pageElement = document.querySelector(Selectors.ELEMENTS.PAGE);
        this.jumpToElement = document.querySelector(Selectors.ELEMENTS.JUMPTO);
        this.actionElement = document.querySelector(Selectors.ELEMENTS.ACTION);

        this.container = document.querySelector(Selectors.ELEMENTS.TEXTPROCESSOR_CONTAINER);
        this.input = this.container?.querySelector(Selectors.ELEMENTS.TEXTPROCESSOR_INPUT);
        this.output = this.container?.querySelector(Selectors.ELEMENTS.TEXTPROCESSOR_OUTPUT);
        this.preview = this.container?.querySelector(Selectors.ELEMENTS.TEXTPROCESSOR_PREVIEW);
        this.processBtn = this.container?.querySelector(Selectors.ELEMENTS.TEXTPROCESSOR_PROCESS_BTN);
        this.copyBtn = this.container?.querySelector(Selectors.ELEMENTS.TEXTPROCESSOR_COPY_BTN);
        this.insertBtn = this.container?.querySelector(Selectors.ELEMENTS.TEXTPROCESSOR_INSERT_BTN);
        this.actionBtns = this.container?.querySelectorAll(Selectors.ELEMENTS.TEXTPROCESSOR_ACTION_BTN);

        this.isDrawerFocusLocked = false;

        this.registerEventListeners();
    }

    /**
     * Register event listeners.
     */
    registerEventListeners() {
        // Handle action button click to open drawer.
        document.addEventListener('click', (e) => {
            const textprocessorAction = e.target.closest(Selectors.ACTIONS.TEXTPROCESSOR_OPEN);
            if (textprocessorAction) {
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

        // Process button.
        if (this.processBtn) {
            this.processBtn.addEventListener('click', () => this.process());
        }

        // Copy button.
        if (this.copyBtn) {
            this.copyBtn.addEventListener('click', () => this.copyToClipboard());
        }

        // Insert button.
        if (this.insertBtn) {
            this.insertBtn.addEventListener('click', () => this.insertToEditor());
        }

        // Action buttons.
        if (this.actionBtns && this.actionBtns.length) {
            this.actionBtns.forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    const action = e.target.dataset.action || e.target.closest('[data-action]')?.dataset.action;
                    if (action) {
                        this.setAction(action);
                        this.process();
                    }
                });
            });
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
     * Set the current action.
     * @param {String} action The action to set.
     */
    setAction(action) {
        this.currentAction = action;

        if (this.actionBtns && this.actionBtns.length) {
            this.actionBtns.forEach((btn) => {
                const btnAction = btn.dataset.action;
                if (btnAction === action) {
                    btn.classList.add('active', 'btn-primary');
                    btn.classList.remove('btn-outline-secondary');
                } else {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-outline-secondary');
                }
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
     * Process the text.
     */
    async process() {
        // Check if Ollama is configured.
        if (!this.ollamaConfigured) {
            Notification.alert('Ollama not configured', 'Please configure Ollama in plugin settings to process text.');
            return;
        }

        const text = this.input?.value?.trim();

        if (!text) {
            Notification.alert('Error', 'Please enter text to process');
            return;
        }

        this.setLoading(true);

        try {
            const response = await Ajax.call([{
                methodname: 'aiplacement_textprocessor_process',
                args: {
                    text: text,
                    action: this.currentAction,
                    contextid: this.contextId
                }
            }])[0];

            if (response.success) {
                if (this.output) {
                    this.output.value = response.html;
                }
                if (this.preview) {
                    this.preview.innerHTML = response.html;
                }
            } else {
                throw new Error(response.message || 'Processing error');
            }
        } catch (error) {
            Notification.exception(error);
            if (this.output) {
                this.output.value = 'Error: ' + error.message;
            }
            Notification.alert('Error', error.message);
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Copy to clipboard.
     */
    copyToClipboard() {
        const html = this.output?.value;

        if (!html) {
            return;
        }

        navigator.clipboard.writeText(html).then(() => {
            const originalText = this.copyBtn?.textContent;
            if (this.copyBtn) {
                this.copyBtn.textContent = '✓ Copied!';
            }
            setTimeout(() => {
                if (this.copyBtn && originalText) {
                    this.copyBtn.textContent = originalText;
                }
            }, 2000);
        }).catch(() => {
            Notification.alert('Error', 'Failed to copy');
        });
    }

    /**
     * Insert to editor at cursor position.
     */
    insertToEditor() {
        const html = this.output?.value;
        if (!html) {
            return;
        }

        // Try TinyMCE first.
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
            tinymce.activeEditor.insertContent(html);
            return;
        }

        // Try Atto.
        if (this.config.editorId) {
            const editor = Y.one('#' + this.config.editorId);
            if (editor && editor.ancestor('.editor_atto')) {
                // Atto editor - use its API.
                const host = Y.M.editor_atto.EditorPanel.getEditor(this.config.editorId);
                if (host) {
                    host.insertContentAtFocusPoint(html);
                    return;
                }
            }
        }

        // Fallback: plain textarea.
        if (this.config.editorId) {
            const textarea = document.getElementById(this.config.editorId);
            if (textarea) {
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, start) +
                                html +
                                textarea.value.substring(end);
                // Move cursor after inserted content.
                textarea.selectionStart = textarea.selectionEnd = start + html.length;
            }
        }
    }

    /**
     * Insert HTML at current cursor position (static method for editor plugins).
     *
     * @param {string} html The HTML to insert
     * @param {Object} editor The editor instance (TinyMCE or Atto)
     */
    static insertHtml(html, editor) {
        if (!html) {
            return;
        }

        // TinyMCE.
        if (editor && typeof editor.insertContent === 'function') {
            editor.insertContent(html);
            return;
        }

        // Atto.
        if (editor && typeof editor.insertContentAtFocusPoint === 'function') {
            editor.insertContentAtFocusPoint(html);
            return;
        }

        // Fallback: copy to clipboard.
        navigator.clipboard.writeText(html).then(() => {
            Notification.alert('Copied', 'HTML copied to clipboard. Paste it in the editor.');
        });
    }

    /**
     * Set loading state.
     * @param {Boolean} loading Whether loading.
     */
    setLoading(loading) {
        if (!this.processBtn) {
            return;
        }

        if (loading) {
            this.processBtn.disabled = true;
            this.processBtn.innerHTML = '⏳ Processing...';
        } else {
            this.processBtn.disabled = !this.ollamaConfigured;
            this.processBtn.innerHTML = '✨ Process';
        }
    }
};

return AIPTextProcessor;
});
