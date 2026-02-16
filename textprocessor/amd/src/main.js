// /ai/placement/textprocessor/amd/src/main.js

define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    
    var textprocessorInstance = null;
    
    /**
     * Класс для UI текст процессора
     */
    var TextProcessorUI = function(elementId, config) {
        this.container = document.getElementById(elementId);
        this.config = config || {};
        this.ollamaConfigured = config.ollama_configured !== false;
        this.currentAction = 'to_html';
        this.init();
    };

    TextProcessorUI.prototype.init = function() {
        if (!this.container) {
            console.error('TextProcessor container not found:', elementId);
            return;
        }
        
        this.input = this.container.querySelector('.textprocessor-input');
        this.output = this.container.querySelector('.textprocessor-output');
        this.preview = this.container.querySelector('.textprocessor-preview');
        this.processBtn = this.container.querySelector('.textprocessor-process-btn');
        this.copyBtn = this.container.querySelector('.textprocessor-copy-btn');
        this.insertBtn = this.container.querySelector('.textprocessor-insert-btn');
        this.actionBtns = this.container.querySelectorAll('.textprocessor-action-btn');
        
        this.bindEvents();
        
        // Show warning if Ollama is not configured.
        if (!this.ollamaConfigured && this.processBtn) {
            this.processBtn.disabled = true;
            this.processBtn.title = 'Ollama is not configured. Please configure Ollama in plugin settings.';
        }
        
        console.log('TextProcessor initialized, container:', this.container.id, 'Ollama configured:', this.ollamaConfigured);
    };

    TextProcessorUI.prototype.bindEvents = function() {
        var self = this;
        
        if (this.processBtn) {
            this.processBtn.addEventListener('click', function() { 
                self.process(); 
            });
        }
        
        if (this.copyBtn) {
            this.copyBtn.addEventListener('click', function() { 
                self.copyToClipboard(); 
            });
        }
        
        if (this.insertBtn) {
            this.insertBtn.addEventListener('click', function() { 
                self.insertToEditor(); 
            });
        }
        
        if (this.actionBtns && this.actionBtns.length) {
            this.actionBtns.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    var action = e.target.dataset.action;
                    self.setAction(action);
                    self.process();
                });
            });
        }
    };

    TextProcessorUI.prototype.setAction = function(action) {
        this.currentAction = action;
        
        if (this.actionBtns && this.actionBtns.length) {
            this.actionBtns.forEach(function(btn) {
                if (btn.dataset.action === action) {
                    btn.classList.add('active', 'btn-primary');
                } else {
                    btn.classList.remove('active', 'btn-primary');
                }
            });
        }
    };

    TextProcessorUI.prototype.process = function() {
        // Check if Ollama is configured.
        if (!this.ollamaConfigured) {
            Notification.alert('Ollama not configured', 'Please configure Ollama in plugin settings to process text.');
            return;
        }
        
        var self = this;
        var text = this.input ? this.input.value.trim() : '';
        
        if (!text) {
            Notification.alert('Ошибка', 'Введите текст для обработки');
            return;
        }
        
        this.setLoading(true);
        
        Ajax.call([{
            methodname: 'aiplacement_textprocessor_process',
            args: {
                text: text,
                action: this.currentAction,
                contextid: this.config.contextid || 0
            }
        }]).then(function(response) {
            if (response.success) {
                if (self.output) {
                    self.output.value = response.html;
                }
                if (self.preview) {
                    self.preview.innerHTML = response.html;
                }
            } else {
                throw new Error(response.message || 'Ошибка обработки');
            }
        }).catch(function(error) {
            Notification.exception(error);
            if (self.output) {
                self.output.value = 'Ошибка: ' + error.message;
            }
            Notification.alert('Ошибка', error.message);
        }).finally(function() {
            self.setLoading(false);
        });
    };

    TextProcessorUI.prototype.copyToClipboard = function() {
        var self = this;
        var html = this.output ? this.output.value : '';
        
        if (!html) return;
        
        navigator.clipboard.writeText(html).then(function() {
            var original = self.copyBtn.textContent;
            self.copyBtn.textContent = '✓ Скопировано!';
            setTimeout(function() {
                self.copyBtn.textContent = original;
            }, 2000);
        }).catch(function() {
            Notification.alert('Ошибка', 'Не удалось скопировать');
        });
    };

    TextProcessorUI.prototype.insertToEditor = function() {
        var html = this.output ? this.output.value : '';
        if (!html) return;
        
        // Вставка в TinyMCE
        if (this.config.editor) {
            this.config.editor.insertContent(html);
        } 
        // Вставка в textarea
        else if (this.config.editorId) {
            var textarea = document.getElementById(this.config.editorId);
            if (textarea) {
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, start) + 
                                html + 
                                textarea.value.substring(end);
            }
        }
        
        // Закрыть диалог
        if (this.config.dialog && this.config.dialog.hide) {
            this.config.dialog.hide();
        }
    };

    TextProcessorUI.prototype.setLoading = function(loading) {
        if (!this.processBtn) return;
        
        if (loading) {
            this.processBtn.disabled = true;
            this.processBtn.innerHTML = '⏳ Обработка...';
        } else {
            this.processBtn.disabled = false;
            this.processBtn.innerHTML = '✨ Обработать';
        }
    };
    
    TextProcessorUI.prototype.show = function() {
        if (this.container) {
            this.container.style.display = 'block';
        }
    };
    
    TextProcessorUI.prototype.hide = function() {
        if (this.container) {
            this.container.style.display = 'none';
        }
    };
    
    TextProcessorUI.prototype.toggle = function() {
        if (this.container) {
            var currentDisplay = this.container.style.display;
            this.container.style.display = (currentDisplay === 'none' || currentDisplay === '') ? 'block' : 'none';
        }
    };

    return {
        init: function(elementId, config) {
            textprocessorInstance = new TextProcessorUI(elementId, config);
            
            // Set up toggle handler for the action button
            setTimeout(function() {
                var toggleBtn = document.querySelector('.aiplacement-textprocessor-action button');
                if (toggleBtn && textprocessorInstance) {
                    toggleBtn.onclick = function(e) {
                        e.preventDefault();
                        textprocessorInstance.toggle();
                    };
                    console.log('TextProcessor toggle button handler attached');
                }
            }, 500);
            
            return textprocessorInstance;
        },
        open: function() {
            if (textprocessorInstance) {
                textprocessorInstance.toggle();
            }
        },
        getInstance: function() {
            return textprocessorInstance;
        }
    };
});
