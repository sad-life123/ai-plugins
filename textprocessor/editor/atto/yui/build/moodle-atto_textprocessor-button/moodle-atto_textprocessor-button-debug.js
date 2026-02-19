/**
 * Atto plugin for AI TextProcessor.
 *
 * @module     moodle-atto_textprocessor-button
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var COMPONENTNAME = 'atto_textprocessor';

Y.namespace('M.atto_textprocessor').Button = Y.Base.create('atto_textprocessor', Y.M.editor_atto.EditorPlugin, [], {

    /**
     * Initialize the button.
     */
    initializer: function() {
        var contextId = this.get('host').get('contextid');

        // Add the button.
        this.addButton({
            icon: 'e/format',
            title: 'ai_textprocessor',
            buttonName: COMPONENTNAME,
            callback: this._openDialog,
            callbackArgs: contextId
        });
    },

    /**
     * Open TextProcessor dialog.
     *
     * @param {Event} e
     * @param {Number} contextId
     */
    _openDialog: function(e, contextId) {
        var host = this.get('host');

        // Use Moodle's AMD module.
        require(['aiplacement_textprocessor/editor_button'], function(editorButton) {
            var editor = {
                type: 'atto',
                insertContent: function(html) {
                    host.insertContentAtFocusPoint(html);
                }
            };
            editorButton.openDialog(editor, contextId);
        });
    }
}, {
    ATTRS: {
        // No additional attributes.
    }
});