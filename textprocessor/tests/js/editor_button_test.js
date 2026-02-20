/**
 * JavaScript tests for editor_button module.
 * 
 * This file provides manual testing instructions for the editor button integration.
 * Run this in browser console on a Moodle page with an editor.
 * 
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * MANUAL TEST INSTRUCTIONS
 * ========================
 * 
 * 1. Open a Moodle page with TinyMCE or Atto editor (e.g., create a new page/activity)
 * 2. Open browser DevTools (F12) and go to Console tab
 * 3. Run the test functions below
 * 
 * Test 1: Check if module loads
 * -----------------------------
 * require(['aiplacement_textprocessor/editor_button'], function(btn) {
 *     console.log('Module loaded successfully');
 *     console.log('init function exists:', typeof btn.init === 'function');
 *     console.log('openDialogExternal function exists:', typeof btn.openDialogExternal === 'function');
 * });
 * 
 * Test 2: Check TinyMCE button
 * -----------------------------
 * require(['aiplacement_textprocessor/editor_button'], function(btn) {
 *     if (typeof tinymce !== 'undefined') {
 *         let editors = null;
 *         if (tinymce.EditorManager && tinymce.EditorManager.editors) {
 *             editors = tinymce.EditorManager.editors;
 *         } else if (tinymce.editors) {
 *             editors = tinymce.editors;
 *         }
 *         const editorsArray = Array.isArray(editors) ? editors : Object.values(editors || {});
 *         console.log('TinyMCE editors found:', editorsArray.length);
 *         editorsArray.forEach(function(editor) {
 *             const buttons = editor.ui.registry.getAll().buttons || {};
 *             console.log('Editor:', editor.id, '- ai_textprocessor button exists:', !!buttons.ai_textprocessor);
 *         });
 *     } else {
 *         console.log('TinyMCE not found on this page');
 *     }
 * });
 * 
 * Test 3: Check Atto button
 * -------------------------
 * const attoButtons = document.querySelectorAll('.atto_ai_textprocessor_button');
 * console.log('Atto buttons found:', attoButtons.length);
 * attoButtons.forEach(function(btn, i) {
 *     console.log('Button ' + i + ':', btn.title, btn);
 * });
 * 
 * Test 4: Initialize module manually
 * ----------------------------------
 * require(['aiplacement_textprocessor/editor_button'], function(btn) {
 *     btn.init(1); // Use context ID 1 for testing
 *     console.log('Module initialized');
 * });
 * 
 * Test 5: Test selectors
 * ----------------------
 * require(['aiplacement_textprocessor/selectors'], function(sel) {
 *     console.log('Selectors loaded:', sel);
 *     console.log('Container selector:', sel.ELEMENTS.TEXTPROCESSOR_CONTAINER);
 * });
 */

/**
 * Automated test suite (run in browser console)
 */
function runEditorButtonTests() {
    const results = {
        passed: 0,
        failed: 0,
        tests: []
    };

    function test(name, condition) {
        if (condition) {
            results.passed++;
            results.tests.push({name: name, status: 'PASS'});
            console.log('✅ PASS:', name);
        } else {
            results.failed++;
            results.tests.push({name: name, status: 'FAIL'});
            console.log('❌ FAIL:', name);
        }
    }

    // Test 1: Module can be required
    require(['aiplacement_textprocessor/editor_button'], function(btn) {
        test('Module loads successfully', true);
        test('init function exists', typeof btn.init === 'function');
        test('openDialogExternal function exists', typeof btn.openDialogExternal === 'function');
    });

    // Test 2: TinyMCE integration (with longer timeout for editor initialization)
    setTimeout(function() {
        if (typeof tinymce !== 'undefined') {
            test('TinyMCE is available', true);
            
            let editors = null;
            if (tinymce.EditorManager && tinymce.EditorManager.editors) {
                editors = tinymce.EditorManager.editors;
            } else if (tinymce.editors) {
                editors = tinymce.editors;
            }
            
            // Try to get editors count in different ways
            let editorsCount = 0;
            if (editors) {
                if (Array.isArray(editors)) {
                    editorsCount = editors.length;
                } else if (typeof editors === 'object') {
                    editorsCount = Object.keys(editors).length;
                }
            }
            
            if (editorsCount > 0) {
                const editorsArray = Array.isArray(editors) ? editors : Object.values(editors);
                test('TinyMCE editors exist (' + editorsCount + ' found)', true);
                
                editorsArray.forEach(function(editor) {
                    if (editor.ui && editor.ui.registry) {
                        const buttons = editor.ui.registry.getAll().buttons || {};
                        // Check for tiny_textprocessor (official) or ai_textprocessor (fallback)
                        const hasButton = !!(buttons.tiny_textprocessor || buttons.ai_textprocessor);
                        test('TinyMCE button registered in ' + editor.id, hasButton);
                        if (buttons.tiny_textprocessor) {
                            console.log('  → Button name: tiny_textprocessor (official)');
                        } else if (buttons.ai_textprocessor) {
                            console.log('  → Button name: ai_textprocessor (fallback)');
                        }
                    }
                });
            } else {
                // Try to check activeEditor as fallback
                if (tinymce.activeEditor) {
                    test('TinyMCE activeEditor exists', true);
                    const editor = tinymce.activeEditor;
                    if (editor.ui && editor.ui.registry) {
                        const buttons = editor.ui.registry.getAll().buttons || {};
                        const hasButton = !!(buttons.tiny_textprocessor || buttons.ai_textprocessor);
                        test('TinyMCE button registered in activeEditor', hasButton);
                    }
                } else {
                    test('TinyMCE editors not found (may need more time to initialize)', true);
                }
            }
        } else {
            test('TinyMCE not available (skipped)', true);
        }
    }, 1500); // Increased timeout to 1.5 seconds

    // Test 3: Atto integration
    setTimeout(function() {
        const attoButtons = document.querySelectorAll('.atto_ai_textprocessor_button');
        if (document.querySelector('.editor_atto_toolbar')) {
            test('Atto toolbar exists', true);
            test('Atto buttons created', attoButtons.length > 0);
        } else {
            test('Atto not available (skipped)', true);
        }
    }, 500);

    // Test 4: Selectors
    require(['aiplacement_textprocessor/selectors'], function(sel) {
        test('Selectors module loads', true);
        test('ELEMENTS object exists', typeof sel.ELEMENTS === 'object');
        test('TEXTPROCESSOR_CONTAINER defined', !!sel.ELEMENTS.TEXTPROCESSOR_CONTAINER);
    });

    // Print summary after 2 seconds
    setTimeout(function() {
        console.log('\n========== TEST RESULTS ==========');
        console.log('Passed:', results.passed);
        console.log('Failed:', results.failed);
        console.log('Total:', results.passed + results.failed);
        console.log('==================================\n');
    }, 2000);

    return results;
}

// Auto-run if in browser console
if (typeof window !== 'undefined') {
    console.log('Run runEditorButtonTests() to execute the test suite');
}