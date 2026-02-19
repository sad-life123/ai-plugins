/**
 * TinyMCE plugin for AI TextProcessor.
 *
 * @module     aiplacement_textprocessor/tinymce
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

tinymce.PluginManager.add('aiplacement_textprocessor', function(editor) {
    // Get context ID from editor configuration.
    var contextId = editor.getParam('aiplacement_textprocessor_contextid', 0);

    /**
     * Open TextProcessor dialog.
     */
    function openDialog() {
        // Use Moodle's AMD module.
        require(['aiplacement_textprocessor/editor_button'], function(editorButton) {
            editorButton.openDialog(editor, contextId);
        });
    }

    // Register button.
    editor.ui.registry.addButton('aiplacement_textprocessor', {
        icon: 'format',
        tooltip: 'AI TextProcessor',
        onAction: openDialog
    });

    // Register menu item.
    editor.ui.registry.addMenuItem('aiplacement_textprocessor', {
        icon: 'format',
        text: 'AI TextProcessor',
        onAction: openDialog
    });

    // Return metadata.
    return {
        name: 'AI TextProcessor',
        url: 'https://moodle.org/'
    };
});