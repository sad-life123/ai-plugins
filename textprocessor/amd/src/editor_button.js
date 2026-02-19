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
 * TextProcessor integration for TinyMCE and Atto editors.
 *
 * @module     aiplacement_textprocessor/editor_button
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/modal_save_cancel',
    'core/str',
    'core/ajax',
    'core/notification',
    'core/templates',
    'core/modal_events'
], function(
    ModalSaveCancel,
    Str,
    Ajax,
    Notification,
    Templates,
    ModalEvents
) {

    /**
     * Current editor instance reference.
     * @type {Object}
     */
    let currentEditor = null;

    /**
     * Current context ID.
     * @type {Number}
     */
    let contextId = null;

    /**
     * Modal instance.
     * @type {Object}
     */
    let modal = null;

    /**
     * Initialize TextProcessor - called from PHP hook.
     * Sets up integration with TinyMCE and Atto editors.
     *
     * @param {Number} ctxid Context ID
     */
    function init(ctxid) {
        contextId = ctxid;
        setupEditorIntegration();
    }

    /**
     * Setup editor integration with proper timing.
     */
    function setupEditorIntegration() {
        // Try to register TinyMCE plugin immediately if TinyMCE is already loaded.
        if (typeof tinymce !== 'undefined') {
            registerTinyMCEPlugin();
        }

        // Setup Atto buttons if Atto is already loaded.
        if (document.querySelector('.editor_atto_toolbar')) {
            setupAttoButtons();
        }

        // Use MutationObserver to detect when editors are added to the page.
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Check for Atto editor toolbars.
                if (mutation.target.classList && mutation.target.classList.contains('editor_atto_toolbar')) {
                    setupAttoButtons();
                }
                // Check for TinyMCE iframes.
                if (mutation.target.id && mutation.target.id.indexOf('tinymce') !== -1) {
                    registerTinyMCEPlugin();
                }
            });
        });
        observer.observe(document.body, {childList: true, subtree: true});

        // Also use Moodle's pending/complete system for proper timing.
        if (typeof window.M !== 'undefined' && window.M.util && window.M.util.js_pending) {
            window.M.util.js_pending('aiplacement_textprocessor_init');
            setTimeout(function() {
                registerTinyMCEPlugin();
                setupAttoButtons();
                if (window.M.util.js_complete) {
                    window.M.util.js_complete('aiplacement_textprocessor_init');
                }
            }, 500);
        }
    }

    /**
     * Register TinyMCE plugin for TextProcessor.
     */
    function registerTinyMCEPlugin() {
        if (typeof tinymce === 'undefined') {
            return;
        }

        // Check if already registered.
        if (tinymce.PluginManager.lookup['aiplacement_textprocessor']) {
            return;
        }

        // Register the plugin.
        tinymce.PluginManager.add('aiplacement_textprocessor', function(editor) {
            // Add button to toolbar.
            editor.ui.registry.addButton('aiplacement_textprocessor', {
                icon: 'format',
                tooltip: 'AI TextProcessor',
                onAction: function() {
                    currentEditor = editor;
                    openDialog();
                }
            });

            // Add to menu.
            editor.ui.registry.addMenuItem('aiplacement_textprocessor', {
                icon: 'format',
                text: 'AI TextProcessor',
                onAction: function() {
                    currentEditor = editor;
                    openDialog();
                }
            });
        });

        // Add button to already initialized editors.
        if (tinymce.EditorManager && tinymce.EditorManager.editors) {
            tinymce.EditorManager.editors.forEach(function(editor) {
                if (editor.ui.registry && editor.ui.registry.getAll &&
                    !editor.ui.registry.getAll().buttons.aiplacement_textprocessor) {
                    editor.ui.registry.addButton('aiplacement_textprocessor', {
                        icon: 'format',
                        tooltip: 'AI TextProcessor',
                        onAction: function() {
                            currentEditor = editor;
                            openDialog();
                        }
                    });
                }
            });
        }
    }

    /**
     * Setup Atto editor buttons.
     */
    function setupAttoButtons() {
        const toolbars = document.querySelectorAll('.editor_atto_toolbar');

        toolbars.forEach(function(toolbar) {
            // Check if button already exists.
            if (toolbar.querySelector('.atto_textprocessor_button')) {
                return;
            }

            // Find or create button group.
            let buttonGroup = toolbar.querySelector('.textprocessor_group');
            if (!buttonGroup) {
                buttonGroup = document.createElement('div');
                buttonGroup.className = 'atto_group textprocessor_group';
                toolbar.appendChild(buttonGroup);
            }

            // Create button.
            const button = document.createElement('button');
            button.className = 'atto_textprocessor_button atto_button';
            button.title = 'AI TextProcessor';
            button.innerHTML = '<span class="icon" style="font-size: 16px;">✨</span>';
            button.type = 'button';

            button.addEventListener('click', function(e) {
                e.preventDefault();
                // Find associated editor.
                const editorId = toolbar.id.replace('editor_atto_toolbar_', '');
                const textarea = document.getElementById(editorId);
                currentEditor = {
                    type: 'atto',
                    textarea: textarea,
                    editorId: editorId,
                    insertContent: function(html) {
                        // Try to use Atto's contenteditable div.
                        const contenteditable = document.querySelector('[contenteditable="true"][id="' + editorId + 'editable"]');
                        if (contenteditable) {
                            // Use execCommand for contenteditable.
                            contenteditable.focus();
                            document.execCommand('insertHTML', false, html);
                            return;
                        }
                        // Fallback: insert into textarea.
                        if (textarea) {
                            const start = textarea.selectionStart;
                            const end = textarea.selectionEnd;
                            textarea.value = textarea.value.substring(0, start) + html + textarea.value.substring(end);
                            textarea.selectionStart = textarea.selectionEnd = start + html.length;
                            // Trigger change event.
                            textarea.dispatchEvent(new Event('change', {bubbles: true}));
                        }
                    }
                };
                openDialog();
            });

            buttonGroup.appendChild(button);
        });
    }

    /**
     * Open the TextProcessor dialog.
     */
    async function openDialog() {
        try {
            // Get strings for modal.
            const strings = await Str.get_strings([
                {key: 'pluginname', component: 'aiplacement_textprocessor'},
                {key: 'process', component: 'aiplacement_textprocessor'},
                {key: 'cancel', component: 'core'},
                {key: 'insert', component: 'aiplacement_textprocessor'}
            ]);

            // Create modal using new API (Moodle 4.3+).
            modal = await ModalSaveCancel.create({
                title: strings[0],
                body: await getModalBody(),
                buttons: {
                    save: strings[3],
                    cancel: strings[2]
                }
            });

            // Handle save button.
            modal.getRoot().on(ModalEvents.save, function(e) {
                e.preventDefault();
                processAndInsert();
            });

            // Handle template selection change.
            modal.getRoot().on('change', '[name="template"]', function(e) {
                const customPromptField = modal.getRoot().find('[name="customprompt"]');
                if (e.target.value === 'custom') {
                    customPromptField.closest('.form-group').show();
                } else {
                    customPromptField.closest('.form-group').hide();
                }
            });

            modal.show();

        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Get the modal body content.
     *
     * @return {Promise<string>}
     */
    async function getModalBody() {
        const context = {
            contextid: contextId,
            templates: [
                {value: 'document_to_html', name: '📄 Document to HTML', selected: true},
                {value: 'structure_headings', name: '📑 Structure Headings'},
                {value: 'definitions_table', name: '📊 Definitions Table'},
                {value: 'image_centering', name: '🖼️ Image Centering'},
                {value: 'custom', name: '✏️ Custom'}
            ]
        };

        return Templates.render('aiplacement_textprocessor/editor_dialog', context);
    }

    /**
     * Process content and insert into editor.
     */
    async function processAndInsert() {
        const root = modal.getRoot();

        // Get form values.
        const template = root.find('[name="template"]').val();
        const customprompt = root.find('[name="customprompt"]').val();
        const fileInput = root.find('[name="file"]')[0];
        const textInput = root.find('[name="text"]').val();

        let content = '';
        let filename = '';

        // Check if file is uploaded.
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            const file = fileInput.files[0];
            filename = file.name;

            // Read file as base64.
            content = await readFileAsBase64(file);
        } else {
            // Use text input.
            content = textInput;
        }

        if (!content || !content.trim()) {
            Notification.alert('Error', 'Please enter text or select a file');
            return;
        }

        // Show loading indicator in modal body.
        const loadingEl = root.find('.textprocessor-loading');
        if (loadingEl.length) {
            loadingEl.show();
        }

        // Disable save button during processing.
        const saveBtn = root.find('[data-action="save"]');
        if (saveBtn.length) {
            saveBtn.prop('disabled', true);
        }

        try {
            // Call the API.
            const response = await Ajax.call([{
                methodname: 'aiplacement_textprocessor_process',
                args: {
                    contextid: contextId,
                    content: content,
                    template: template,
                    filename: filename,
                    customprompt: customprompt
                }
            }])[0];

            if (response.success && response.html) {
                // Insert into editor.
                insertIntoEditor(response.html);
                modal.hide();
            } else {
                Notification.alert('Error', response.message || 'Processing failed');
            }

        } catch (error) {
            Notification.exception(error);
        } finally {
            // Re-enable save button.
            if (saveBtn.length) {
                saveBtn.prop('disabled', false);
            }
            // Hide loading indicator.
            if (loadingEl.length) {
                loadingEl.hide();
            }
        }
    }

    /**
     * Read file as base64.
     *
     * @param {File} file
     * @return {Promise<string>}
     */
    function readFileAsBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Remove data URL prefix to get pure base64.
                const base64 = e.target.result.split(',')[1];
                resolve(base64);
            };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    /**
     * Insert HTML content into the editor.
     *
     * @param {string} html
     */
    function insertIntoEditor(html) {
        if (!currentEditor) {
            // Fallback: copy to clipboard.
            navigator.clipboard.writeText(html).then(function() {
                Notification.alert('Copied', 'HTML copied to clipboard. Paste it in the editor.');
            });
            return;
        }

        // TinyMCE.
        if (currentEditor.insertContent) {
            currentEditor.insertContent(html);
            return;
        }

        // Atto or custom editor with insertContent method.
        if (typeof currentEditor.insertContent === 'function') {
            currentEditor.insertContent(html);
            return;
        }

        // Fallback for textarea.
        if (currentEditor.textarea) {
            const textarea = currentEditor.textarea;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            textarea.value = textarea.value.substring(0, start) + html + textarea.value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + html.length;
            // Trigger change event.
            textarea.dispatchEvent(new Event('change', {bubbles: true}));
        }
    }

    /**
     * Public API.
     */
    return {
        /**
         * Initialize for TinyMCE.
         *
         * @param {Object} editor TinyMCE editor instance
         * @param {Number} contextid Context ID
         */
        initForTinyMCE: function(editor, contextid) {
            currentEditor = editor;
            contextId = contextid;
            editor.ui.registry.addButton('aiplacement_textprocessor', {
                icon: 'format',
                tooltip: 'AI TextProcessor',
                onAction: function() {
                    openDialog();
                }
            });
        },

        /**
         * Initialize for Atto.
         *
         * @param {Object} editor Atto editor instance
         * @param {Number} contextid Context ID
         */
        initForAtto: function(editor, contextid) {
            currentEditor = editor;
            contextId = contextid;
        },

        /**
         * Open dialog manually (for testing or custom integration).
         *
         * @param {Object} editor Editor instance
         * @param {Number} contextid Context ID
         */
        openDialog: function(editor, contextid) {
            currentEditor = editor;
            contextId = contextid;
            openDialog();
        },

        /**
         * Process text and return HTML (without inserting).
         *
         * @param {Number} contextid Context ID
         * @param {string} content Text content
         * @param {string} template Template name
         * @param {string} filename Filename (if file)
         * @param {string} customprompt Custom prompt
         * @return {Promise<Object>}
         */
        processText: async function(contextid, content, template, filename, customprompt) {
            return Ajax.call([{
                methodname: 'aiplacement_textprocessor_process',
                args: {
                    contextid: contextid,
                    content: content,
                    template: template,
                    filename: filename || '',
                    customprompt: customprompt || ''
                }
            }])[0];
        },

        /**
         * Initialize - called from PHP hook.
         *
         * @param {Number} contextid Context ID
         */
        init: function(contextid) {
            init(contextid);
        }
    };
});