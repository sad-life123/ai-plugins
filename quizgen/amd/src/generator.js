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
 * Module to load and render the quiz generator for the AI QuizGen plugin.
 *
 * @module     aiplacement_quizgen/generator
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/ajax',
    'core/str',
    'core/notification',
    'core/templates',
    'aiplacement_quizgen/selectors',
    'core/drawer_events',
    'core/pubsub',
    'core_message/message_drawer_helper',
    'core/local/aria/focuslock',
    'core/pagehelpers'
], function(
    Ajax,
    Str,
    Notification,
    Templates,
    Selectors,
    DrawerEvents,
    PubSub,
    MessageDrawerHelper,
    FocusLock,
    PageHelpers
) {

const AIPQuizGen = class {

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
     * Questions array.
     * @type {Array}
     */
    questions;
    /**
     * Selected questions set.
     * @type {Set}
     */
    selectedQuestions;

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
        this.questions = [];
        this.selectedQuestions = new Set();

        this.aiDrawerElement = document.querySelector(Selectors.ELEMENTS.AIDRAWER);
        this.aiDrawerBodyElement = document.querySelector(Selectors.ELEMENTS.AIDRAWER_BODY);
        this.pageElement = document.querySelector(Selectors.ELEMENTS.PAGE);
        this.jumpToElement = document.querySelector(Selectors.ELEMENTS.JUMPTO);
        this.actionElement = document.querySelector(Selectors.ELEMENTS.ACTION);

        this.container = document.querySelector(Selectors.ELEMENTS.QUIZGEN_CONTAINER);
        this.textarea = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_TEXTAREA);
        this.countSelect = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_COUNT_SELECT);
        this.typeSelect = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_TYPE_SELECT);
        this.difficultySelect = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_DIFFICULTY_SELECT);
        this.languageSelect = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_LANGUAGE_SELECT);
        this.generateBtn = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_GENERATE_BTN);
        this.progressBar = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_PROGRESS);
        this.questionsGrid = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_QUESTIONS_GRID);
        this.saveAllBtn = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_SAVE_ALL_BTN);
        this.charCounter = this.container?.querySelector(Selectors.ELEMENTS.QUIZGEN_CHAR_COUNTER);

        this.isDrawerFocusLocked = false;

        this.registerEventListeners();
    }

    /**
     * Register event listeners.
     */
    registerEventListeners() {
        // Handle action button click to open drawer.
        document.addEventListener('click', (e) => {
            const quizgenAction = e.target.closest(Selectors.ACTIONS.QUIZGEN_OPEN);
            if (quizgenAction) {
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

        // Generate button.
        if (this.generateBtn) {
            this.generateBtn.addEventListener('click', () => this.generate());
        }

        // Save all button.
        if (this.saveAllBtn) {
            this.saveAllBtn.addEventListener('click', () => this.saveAll());
        }

        // Ctrl+Enter to generate.
        if (this.textarea) {
            this.textarea.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    this.generate();
                }
            });

            // Update char counter.
            this.textarea.addEventListener('input', () => this.updateCharCount());
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
     * Generate questions.
     */
    async generate() {
        // Check if Ollama is configured.
        if (!this.ollamaConfigured) {
            Notification.alert('Ollama not configured', 'Please configure Ollama in plugin settings to generate questions.');
            return;
        }

        const text = this.textarea?.value?.trim();

        if (!text) {
            Notification.alert('Error', 'Please enter text to generate questions');
            return;
        }

        const params = {
            text: text,
            count: parseInt(this.countSelect?.value || 5),
            type: this.typeSelect?.value || 'multichoice',
            difficulty: this.difficultySelect?.value || 'medium',
            language: this.languageSelect?.value || 'ru',
            contextid: this.contextId
        };

        this.setLoading(true);
        this.questions = [];
        this.selectedQuestions.clear();

        try {
            const response = await Ajax.call([{
                methodname: 'aiplacement_quizgen_generate',
                args: params
            }])[0];

            if (response.success) {
                this.questions = JSON.parse(response.questions) || [];
                await this.renderQuestions();

                Str.get_string('success_generated', 'aiplacement_quizgen', this.questions.length)
                    .then(s => Notification.addNotification({
                        message: s,
                        type: 'success'
                    }));

            } else {
                throw new Error(response.error || 'Generation failed');
            }

        } catch (error) {
            Notification.exception(error);
            if (this.questionsGrid) {
                this.questionsGrid.innerHTML = `
                    <div class="alert alert-danger">
                        ❌ Error: ${error.message}
                    </div>
                `;
            }
            Notification.alert('Error', error.message);
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Render questions.
     */
    async renderQuestions() {
        if (!this.questionsGrid || this.questions.length === 0) {
            this.questionsGrid.innerHTML = `
                <div class="alert alert-info">
                    🤖 Press "Generate" to create questions
                </div>
            `;
            return;
        }

        const html = await Templates.render('aiplacement_quizgen/question_preview', {
            questions: this.questions,
            showSelect: true
        });

        this.questionsGrid.innerHTML = html;

        // Add event listeners for each question.
        this.questions.forEach((q, index) => {
            const card = this.questionsGrid.querySelector(`[data-question-index="${index}"]`);
            if (card) {
                this.bindQuestionEvents(card, q, index);
            }
        });
    }

    /**
     * Bind events for a question card.
     * @param {HTMLElement} card The question card element.
     * @param {Object} question The question object.
     * @param {Integer} index The question index.
     */
    bindQuestionEvents(card, question, index) {
        // Select question.
        const selectBtn = card.querySelector('.question-select');
        if (selectBtn) {
            selectBtn.addEventListener('click', () => {
                if (this.selectedQuestions.has(index)) {
                    this.selectedQuestions.delete(index);
                    selectBtn.classList.remove('btn-primary');
                    selectBtn.classList.add('btn-outline-primary');
                } else {
                    this.selectedQuestions.add(index);
                    selectBtn.classList.remove('btn-outline-primary');
                    selectBtn.classList.add('btn-primary');
                }
            });
        }

        // Regenerate.
        const regenBtn = card.querySelector('.question-regenerate');
        if (regenBtn) {
            regenBtn.addEventListener('click', () => this.regenerateQuestion(index));
        }

        // Save.
        const saveBtn = card.querySelector('.question-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveQuestion(question));
        }

        // Edit.
        const editBtn = card.querySelector('.question-edit');
        if (editBtn) {
            editBtn.addEventListener('click', () => this.editQuestion(question, card));
        }
    }

    /**
     * Save all selected questions.
     */
    async saveAll() {
        if (this.questions.length === 0) {
            return;
        }

        const questionsToSave = this.selectedQuestions.size > 0
            ? this.questions.filter((_, i) => this.selectedQuestions.has(i))
            : this.questions;

        this.setSaving(true);

        try {
            const response = await Ajax.call([{
                methodname: 'aiplacement_quizgen_save_to_bank',
                args: {
                    questions: JSON.stringify(questionsToSave),
                    courseid: this.courseId
                }
            }])[0];

            if (response.success) {
                Str.get_string('success_saved', 'aiplacement_quizgen', response.saved_count)
                    .then(s => Notification.addNotification({
                        message: s,
                        type: 'success'
                    }));

                this.selectedQuestions.clear();
                await this.renderQuestions();
            }

        } catch (error) {
            Notification.exception(error);
        } finally {
            this.setSaving(false);
        }
    }

    /**
     * Save a single question.
     * @param {Object} question The question to save.
     */
    async saveQuestion(question) {
        try {
            const response = await Ajax.call([{
                methodname: 'aiplacement_quizgen_save_to_bank',
                args: {
                    questions: JSON.stringify([question]),
                    courseid: this.courseId
                }
            }])[0];

            if (response.success) {
                Notification.addNotification({
                    message: '✅ Question saved to bank',
                    type: 'success'
                });
            }

        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Regenerate a question.
     * @param {Integer} index The question index.
     */
    async regenerateQuestion(index) {
        // TODO: Implement regeneration.
    }

    /**
     * Edit a question.
     * @param {Object} question The question to edit.
     * @param {HTMLElement} card The question card element.
     */
    editQuestion(question, card) {
        // TODO: Implement editing.
    }

    /**
     * Set loading state.
     * @param {Boolean} loading Whether loading.
     */
    setLoading(loading) {
        if (!this.generateBtn) {
            return;
        }

        if (loading) {
            this.generateBtn.disabled = true;
            this.generateBtn.innerHTML = '<span class="quizgen-spinner"></span> Generating...';
            if (this.progressBar) {
                this.progressBar.classList.add('active');
            }
        } else {
            this.generateBtn.disabled = !this.ollamaConfigured;
            this.generateBtn.innerHTML = '🎯 Generate';
            if (this.progressBar) {
                this.progressBar.classList.remove('active');
            }
        }
    }

    /**
     * Set saving state.
     * @param {Boolean} saving Whether saving.
     */
    setSaving(saving) {
        if (this.saveAllBtn) {
            this.saveAllBtn.disabled = saving;
            this.saveAllBtn.innerHTML = saving
                ? '💾 Saving...'
                : '💾 Save all to bank';
        }
    }

    /**
     * Update character counter.
     */
    updateCharCount() {
        if (this.charCounter && this.textarea) {
            const len = this.textarea.value.length;
            this.charCounter.textContent = `${len} characters`;
        }
    }
};

return AIPQuizGen;
});
