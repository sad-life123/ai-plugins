// /ai/placement/textprocessor/amd/src/editor.js

define([
    'core/notification',
    'core/templates',
    'aiplacement_textprocessor/main'
], function(Notification, Templates, TextProcessorUI) {
    
    /**
     * Интеграция с редакторами Moodle
     */
    var EditorIntegration = function(config) {
        this.config = config || {};
        this.initTinyMCE();
        this.initAtto();
        this.initContextMenu();
    };

    EditorIntegration.prototype.initTinyMCE = function() {
        var self = this;
        
        // Если TinyMCE еще не загружен, ждем
        if (typeof window.tinymce === 'undefined') {
            document.addEventListener('tinymce-loaded', function() {
                self.initTinyMCE();
            });
            return;
        }
        
        // Регистрируем плагин для TinyMCE
        tinymce.PluginManager.add('textprocessor', function(editor, url) {
            
            // Добавляем кнопку на панель
            editor.addButton('textprocessor_btn', {
                text: '✨',
                tooltip: 'AI Text Processor (Ctrl+Shift+A)',
                onclick: function() {
                    self.openDialog(editor);
                }
            });
            
            // Добавляем пункт в меню
            editor.addMenuItem('textprocessor_menu', {
                text: 'AI Text Processor',
                context: 'tools',
                onclick: function() {
                    self.openDialog(editor);
                }
            });
            
            // Горячая клавиша
            editor.addShortcut('Ctrl+Shift+A', 'AI Text Processor', function() {
                self.openDialog(editor);
            });
            
            // Команда
            editor.addCommand('mceTextProcessor', function() {
                self.openDialog(editor);
            });
        });
    };

    EditorIntegration.prototype.initAtto = function() {
        var self = this;
        
        // Для Atto редактора
        if (typeof M.atto === 'undefined') {
            M.atto = {};
        }
        
        M.atto.textprocessor = {
            init: function(editorId) {
                var editor = document.getElementById(editorId);
                if (!editor) return;
                
                var toolbar = editor.closest('.editor_atto');
                if (!toolbar) return;
                
                toolbar = toolbar.querySelector('.atto_toolbar');
                if (!toolbar) return;
                
                // Проверяем, нет ли уже кнопки
                if (toolbar.querySelector('.atto_textprocessor_btn')) return;
                
                // Создаем кнопку
                var button = document.createElement('button');
                button.className = 'atto_button atto_textprocessor_btn';
                button.innerHTML = '✨';
                button.title = 'AI Text Processor';
                button.setAttribute('type', 'button');
                
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    self.openAttoDialog(editorId);
                });
                
                toolbar.appendChild(button);
            }
        };
    };

    EditorIntegration.prototype.initContextMenu = function() {
        var self = this;
        
        document.addEventListener('contextmenu', function(e) {
            var target = e.target;
            
            // Проверяем, кликнули ли на textarea
            if (target.tagName !== 'TEXTAREA') return;
            
            var textarea = target;
            
            // Проверяем, выделен ли текст
            if (textarea.selectionStart === textarea.selectionEnd) return;
            
            e.preventDefault();
            
            var selectedText = textarea.value.substring(
                textarea.selectionStart,
                textarea.selectionEnd
            );
            
            if (!selectedText) return;
            
            self.showContextMenu(e, textarea, selectedText);
        });
    };

    EditorIntegration.prototype.showContextMenu = function(e, textarea, selectedText) {
        var self = this;
        
        // Удаляем старое меню
        var oldMenu = document.querySelector('.ai-context-menu');
        if (oldMenu) oldMenu.remove();
        
        // Создаем меню
        var menu = document.createElement('div');
        menu.className = 'ai-context-menu';
        menu.style.left = e.pageX + 'px';
        menu.style.top = e.pageY + 'px';
        
        var actions = [
            {action: 'to_html', label: '📄 В HTML'},
            {action: 'from_markdown', label: '🔗 Из Markdown'},
            {action: 'to_table', label: '📊 В таблицу'},
            {action: 'clean_html', label: '✨ Очистить'}
        ];
        
        actions.forEach(function(item) {
            var div = document.createElement('div');
            div.className = 'ai-context-menu-item';
            div.textContent = item.label;
            
            div.addEventListener('click', function() {
                menu.remove();
                self.openDialog(null, {
                    initialText: selectedText,
                    textarea: textarea,
                    action: item.action
                });
            });
            
            menu.appendChild(div);
        });
        
        document.body.appendChild(menu);
        
        // Закрыть по клику вне меню
        setTimeout(function() {
            document.addEventListener('click', function closeMenu(e) {
                if (!menu.contains(e.target)) {
                    menu.remove();
                    document.removeEventListener('click', closeMenu);
                }
            });
        }, 100);
    };

    EditorIntegration.prototype.openDialog = function(editor, options) {
        var self = this;
        options = options || {};
        
        var selectedText = options.initialText || '';
        
        if (!selectedText && editor) {
            selectedText = editor.selection.getContent({format: 'text'});
        }
        
        var container = document.createElement('div');
        container.id = 'textprocessor-dialog-' + Date.now();
        document.body.appendChild(container);
        
        Templates.render('aiplacement_textprocessor/dialog', {
            uniqid: Date.now(),
            initialtext: selectedText,
            contextid: this.config.contextid || 0
        }).then(function(html) {
            container.innerHTML = html;
            
            // Создаем диалог Moodle
            var dialog = new M.core.dialogue({
                header: 'AI Text Processor',
                bodyContent: container,
                width: '900px',
                draggable: true,
                modal: true,
                render: true
            });
            
            dialog.show();
            
            // Находим ID контейнера с UI
            var uiContainer = container.querySelector('[id^="textprocessor-ui-"]');
            
            if (uiContainer) {
                // Инициализируем UI
                TextProcessorUI.init(uiContainer.id, {
                    editor: editor,
                    editorId: options.textarea ? options.textarea.id : null,
                    dialog: dialog,
                    contextid: self.config.contextid || 0
                });
            }
        }).catch(Notification.exception);
    };

    EditorIntegration.prototype.openAttoDialog = function(editorId) {
        var textarea = document.getElementById(editorId);
        if (!textarea) return;
        
        var selectedText = textarea.value.substring(
            textarea.selectionStart,
            textarea.selectionEnd
        );
        
        this.openDialog(null, {
            initialText: selectedText,
            textarea: textarea
        });
    };

    return {
        init: function(config) {
            return new EditorIntegration(config || {});
        }
    };
});