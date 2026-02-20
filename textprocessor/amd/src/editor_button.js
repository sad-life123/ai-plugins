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
 * Pure JS approach - no PHP editor plugins needed.
 *
 * @module     aiplacement_textprocessor/editor_button
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/modal_save_cancel',
    'core/modal_events',
    'core/ajax',
    'core/notification',
    'core/templates',
    'core/str'
], function(
    ModalSaveCancel,
    ModalEvents,
    Ajax,
    Notification,
    Templates,
    Str
) {

    /**
     * Current editor instance reference.
     * @type {Object}
     */
    let currentEditor = null;

    /**
     * Editor type: 'tinymce' or 'atto'.
     * @type {String}
     */
    let editorType = null;

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
        console.log('TextProcessor: init() called with contextId:', ctxid);
        contextId = ctxid;

        // Wait for editors to be ready.
        if (typeof M !== 'undefined' && M.util && M.util.js_pending) {
            M.util.js_pending('aiplacement_textprocessor_init');
        }

        // Setup both editors.
        console.log('TextProcessor: Setting up TinyMCE...');
        setupTinyMCE();
        console.log('TextProcessor: Setting up Atto...');
        setupAtto();

        if (typeof M !== 'undefined' && M.util && M.util.js_complete) {
            setTimeout(function() {
                M.util.js_complete('aiplacement_textprocessor_init');
            }, 500);
        }
        console.log('TextProcessor: init() completed');
    }

    /**
     * Setup TinyMCE integration.
     */
    function setupTinyMCE() {
        // Check if TinyMCE is already loaded.
        if (typeof tinymce !== 'undefined' && tinymce.EditorManager) {
            addButtonsToTinyMCEEditors();
            setupTinyMCEObserver();
            return;
        }

        // Wait for TinyMCE to load.
        let attempts = 0;
        const maxAttempts = 50; // 10 seconds max.

        const checkTinyMCE = setInterval(function() {
            attempts++;

            if (typeof tinymce !== 'undefined' && tinymce.EditorManager) {
                clearInterval(checkTinyMCE);
                addButtonsToTinyMCEEditors();
                setupTinyMCEObserver();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkTinyMCE);
            }
        }, 200);
    }

    /**
     * Setup observer for new TinyMCE editors.
     */
    function setupTinyMCEObserver() {
        if (typeof tinymce === 'undefined' || !tinymce.EditorManager) {
            return;
        }

        // Listen for new editors.
        tinymce.EditorManager.on('AddEditor', function(e) {
            setTimeout(function() {
                addTinyMCEButton(e.editor);
            }, 100);
        });
    }

    /**
     * Add buttons to all existing TinyMCE editors.
     */
    function addButtonsToTinyMCEEditors() {
        if (typeof tinymce === 'undefined') {
            return;
        }

        // Try different ways to get editors (TinyMCE 5/6/7 compatibility).
        let editors = null;
        if (tinymce.EditorManager && tinymce.EditorManager.editors) {
            editors = tinymce.EditorManager.editors;
        } else if (tinymce.editors) {
            editors = tinymce.editors;
        }

        if (!editors) {
            return;
        }

        // Convert to array if needed.
        const editorsArray = Array.isArray(editors) ? editors : Object.values(editors);
        if (!editorsArray.length) {
            return;
        }

        editorsArray.forEach(function(editor) {
            addTinyMCEButton(editor);
        });
    }

    /**
     * Add button to a TinyMCE editor.
     * @param {Object} editor TinyMCE editor instance.
     */
    function addTinyMCEButton(editor) {
        if (!editor || !editor.ui || !editor.ui.registry) {
            return;
        }

        // Check if button already exists.
        const buttons = editor.ui.registry.getAll().buttons || {};
        if (buttons.ai_textprocessor) {
            return;
        }

        // Register button.
        editor.ui.registry.addButton('ai_textprocessor', {
            icon: 'format',
            tooltip: 'AI TextProcessor',
            onAction: function() {
                currentEditor = editor;
                editorType = 'tinymce';
                openDialog();
            }
        });

        // Register menu item.
        editor.ui.registry.addMenuItem('ai_textprocessor', {
            icon: 'format',
            text: 'AI TextProcessor',
            onAction: function() {
                currentEditor = editor;
                editorType = 'tinymce';
                openDialog();
            }
        });
    }

    /**
     * Setup Atto editor integration.
     */
    function setupAtto() {
        // Check if Atto is on the page.
        if (document.querySelector('.editor_atto_toolbar')) {
            addAttoButtons();
        }

        // Use MutationObserver to detect new Atto editors.
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.classList && node.classList.contains('editor_atto_toolbar')) {
                        addAttoButton(node);
                    }
                    // Check children too.
                    if (node.querySelectorAll) {
                        const toolbars = node.querySelectorAll('.editor_atto_toolbar');
                        toolbars.forEach(function(toolbar) {
                            addAttoButton(toolbar);
                        });
                    }
                });
            });
        });

        observer.observe(document.body, {childList: true, subtree: true});

        // Also check after a delay for dynamically loaded editors.
        setTimeout(function() {
            addAttoButtons();
        }, 1000);
    }

    /**
     * Add buttons to all Atto toolbars.
     */
    function addAttoButtons() {
        const toolbars = document.querySelectorAll('.editor_atto_toolbar');
        toolbars.forEach(function(toolbar) {
            addAttoButton(toolbar);
        });
    }

    /**
     * Add button to an Atto toolbar.
     * @param {Element} toolbar The toolbar element.
     */
    function addAttoButton(toolbar) {
        // Check if button already exists.
        if (toolbar.querySelector('.atto_ai_textprocessor_button')) {
            return;
        }

        // Find or create a button group.
        let buttonGroup = toolbar.querySelector('.atto_group.ai_tools');
        if (!buttonGroup) {
            buttonGroup = document.createElement('div');
            buttonGroup.className = 'atto_group ai_tools';
            toolbar.appendChild(buttonGroup);
        }

        // Create button.
        const button = document.createElement('button');
        button.className = 'atto_button atto_ai_textprocessor_button';
        button.title = 'AI TextProcessor';
        button.innerHTML = '<span class="icon" aria-hidden="true">✨</span>';
        button.type = 'button';

        // Debug: log button creation.
        console.log('TextProcessor: Creating Atto button for toolbar:', toolbar.id);

        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('TextProcessor: Atto button clicked!');

            // Find associated editor.
            const toolbarId = toolbar.id || '';
            const editorId = toolbarId.replace('editor_atto_toolbar_', '');
            const textarea = document.getElementById(editorId);

            console.log('TextProcessor: Editor ID:', editorId, 'Textarea:', textarea);

            // Get context ID from textarea data or fallback to page context.
            let ctxId = contextId;
            if (!ctxId && textarea) {
                // Try to get context from form element.
                const form = textarea.closest('form');
                if (form) {
                    const contextInput = form.querySelector('input[name="contextid"]');
                    if (contextInput) {
                        ctxId = parseInt(contextInput.value, 10);
                    }
                }
                // Try to get from M.cfg.
                if (!ctxId && typeof M !== 'undefined' && M.cfg && M.cfg.contextid) {
                    ctxId = M.cfg.contextid;
                }
            }
            // Ensure contextId is set.
            if (!ctxId) {
                ctxId = 1; // Fallback to system context.
            }
            contextId = ctxId;

            console.log('TextProcessor: Using contextId:', contextId);

            currentEditor = {
                type: 'atto',
                textarea: textarea,
                editorId: editorId,
                toolbar: toolbar
            };
            editorType = 'atto';
            
            console.log('TextProcessor: Calling openDialog...');
            openDialog().catch(function(err) {
                console.error('TextProcessor: openDialog error:', err);
            });
        });

        buttonGroup.appendChild(button);
        console.log('TextProcessor: Atto button appended to toolbar');
    }

    /**
     * Open the TextProcessor dialog.
     */
    async function openDialog() {
        console.log('TextProcessor: openDialog() started');
        try {
            // Get strings.
            console.log('TextProcessor: Loading strings...');
            const strings = await Str.get_strings([
                {key: 'pluginname', component: 'aiplacement_textprocessor'},
                {key: 'process', component: 'aiplacement_textprocessor'},
                {key: 'insert', component: 'aiplacement_textprocessor'}
            ]);
            console.log('TextProcessor: Strings loaded:', strings);

            // Build modal body.
            console.log('TextProcessor: Rendering template with contextId:', contextId);
            const bodyContent = await Templates.render('aiplacement_textprocessor/editor_dialog', {
                contextid: contextId
            });
            console.log('TextProcessor: Template rendered, body length:', bodyContent.length);

            // Create modal using Moodle 4.3+ API.
            console.log('TextProcessor: Creating modal...');
            modal = await ModalSaveCancel.create({
                title: strings[0],
                body: bodyContent,
                buttons: {
                    save: strings[2]
                },
                large: true
            });
            console.log('TextProcessor: Modal created');

            // Handle save button.
            modal.getRoot().on(ModalEvents.save, function(e) {
                e.preventDefault();
                processAndInsert();
            });

            // Show modal.
            console.log('TextProcessor: Showing modal...');
            modal.show();
            console.log('TextProcessor: Modal shown successfully');

        } catch (error) {
            console.error('TextProcessor: openDialog error:', error);
            Notification.exception(error);
        }
    }

    /**
     * Process content and insert into editor.
     */
    async function processAndInsert() {
        const root = modal.getRoot();

        // Get form values.
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
            content = textInput;
        }

        console.log('TextProcessor: Content length:', content ? content.length : 0);
        console.log('TextProcessor: Filename:', filename || 'none');

        if (!content || !content.trim()) {
            Notification.alert('Error', 'Please enter text or select a file');
            return;
        }

        // Show loading.
        const loadingEl = root.find('.textprocessor-loading');
        const processBtn = root.find('[data-action="save"]');
        const originalBtnText = processBtn.text();

        loadingEl.removeClass('hidden');
        processBtn.prop('disabled', true).text('Processing...');

        try {
            console.log('TextProcessor: Calling API with contextId:', contextId);

            // Call the API.
            const response = await Ajax.call([{
                methodname: 'aiplacement_textprocessor_process',
                args: {
                    contextid: contextId,
                    content: content,
                    filename: filename
                }
            }])[0];

            console.log('TextProcessor: API response:', response);
            console.log('TextProcessor: success:', response.success);
            console.log('TextProcessor: html length:', response.html ? response.html.length : 0);
            console.log('TextProcessor: message:', response.message);

            if (response.success && response.html) {
                // Insert into editor.
                console.log('TextProcessor: Inserting HTML into editor...');
                insertIntoEditor(response.html);
                modal.hide();
                console.log('TextProcessor: Done!');
            } else {
                console.error('TextProcessor: Processing failed:', response.message);
                Notification.alert('Error', response.message || 'Processing failed');
            }

        } catch (error) {
            console.error('TextProcessor: API error:', error);
            Notification.exception(error);
        } finally {
            processBtn.prop('disabled', false).text(originalBtnText);
            loadingEl.addClass('hidden');
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
                // Remove data URL prefix.
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
        console.log('TextProcessor: insertIntoEditor called');
        console.log('TextProcessor: editorType:', editorType);
        console.log('TextProcessor: currentEditor:', currentEditor);
        console.log('TextProcessor: html preview:', html ? html.substring(0, 200) + '...' : 'empty');

        if (!html) {
            console.log('TextProcessor: No HTML to insert');
            return;
        }

        // TinyMCE.
        if (editorType === 'tinymce' && currentEditor && typeof currentEditor.insertContent === 'function') {
            console.log('TextProcessor: Using TinyMCE insertContent');
            currentEditor.insertContent(html);
            return;
        }

        // Atto.
        if (editorType === 'atto' && currentEditor) {
            console.log('TextProcessor: Processing Atto editor');
            const editorId = currentEditor.editorId;
            console.log('TextProcessor: Looking for editorId:', editorId);

            // Atto creates a contenteditable div with id="id_editornameeditable"
            // Try multiple selectors.
            let contenteditable = document.querySelector('[contenteditable="true"][id="' + editorId + 'editable"]');
            console.log('TextProcessor: contenteditable (by editorId):', contenteditable);

            if (!contenteditable) {
                // Try finding by pattern: ideditable or id_editornameeditable.
                contenteditable = document.querySelector('[contenteditable="true"][id$="editable"]');
                console.log('TextProcessor: contenteditable (any ending with editable):', contenteditable);
            }

            if (!contenteditable) {
                // Find the contenteditable inside Atto editor wrapper.
                const attoWrapper = document.querySelector('.editor_atto_content');
                if (attoWrapper) {
                    contenteditable = attoWrapper.querySelector('[contenteditable="true"]');
                }
                console.log('TextProcessor: contenteditable (in atto wrapper):', contenteditable);
            }

            if (contenteditable) {
                console.log('TextProcessor: Found contenteditable, inserting HTML');
                contenteditable.focus();
                document.execCommand('insertHTML', false, html);
                console.log('TextProcessor: HTML inserted via execCommand');
                return;
            }

            // Fallback: try to find the original textarea.
            const textarea = document.getElementById(editorId);
            if (textarea && textarea.tagName === 'TEXTAREA') {
                console.log('TextProcessor: Using textarea fallback');
                const value = textarea.value || '';
                const start = textarea.selectionStart || 0;
                const end = textarea.selectionEnd || start;
                textarea.value = value.substring(0, start) + html + value.substring(end);
                textarea.selectionStart = textarea.selectionEnd = start + html.length;
                textarea.dispatchEvent(new Event('change', {bubbles: true}));
                console.log('TextProcessor: HTML inserted into textarea');
                return;
            }

            console.log('TextProcessor: No Atto insertion method found');
        }

        // Final fallback: copy to clipboard.
        console.log('TextProcessor: Using clipboard fallback');
        navigator.clipboard.writeText(html).then(function() {
            Notification.alert('Copied', 'HTML copied to clipboard. Paste it in the editor.');
        });
    }

    /**
     * Public API.
     */
    return {
        /**
         * Initialize - called from PHP hook.
         *
         * @param {Number} ctxid Context ID
         */
        init: function(ctxid) {
            init(ctxid);
        },

        /**
         * Open dialog manually (for external use).
         *
         * @param {Object} editor Editor instance
         * @param {Number} ctxid Context ID
         */
        openDialogExternal: function(editor, ctxid) {
            currentEditor = editor;
            contextId = ctxid;
            editorType = editor && typeof editor.insertContent === 'function' ? 'tinymce' : 'atto';
            openDialog();
        }
    };
});