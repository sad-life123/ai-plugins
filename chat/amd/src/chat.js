// /ai/placement/chat/amd/src/chat.js

define(['core/ajax', 'core/str', 'core/notification'], function(Ajax, Str, Notification) {

    var chatInstance = null;

    class CourseChat {

        constructor(containerId, config) {
            this.container = document.getElementById(containerId);
            this.config = config;
            this.courseid = config.courseid;
            this.contextid = config.contextid;
            this.ollamaConfigured = config.ollama_configured !== false;
            this.messages = [];
            this.typingTimer = null;

            this.init();
        }

        init() {
            if (!this.container) {
                console.error('Chat container not found:', this.container);
                return;
            }

            this.messagesContainer = this.container.querySelector('.coursechat-messages');
            this.input = this.container.querySelector('.coursechat-input');
            this.sendBtn = this.container.querySelector('.coursechat-send');
            this.typingIndicator = this.container.querySelector('.coursechat-typing');

            this.bindEvents();
            
            // Show warning if Ollama is not configured.
            if (!this.ollamaConfigured) {
                this.addMessage('system', '⚠️ Ollama is not configured. Please configure Ollama in plugin settings to use chat.');
                this.sendBtn.disabled = true;
                this.input.disabled = true;
            } else {
                this.loadHistory();
            }
            
            console.log('Chat initialized, container:', this.container.id, 'Ollama configured:', this.ollamaConfigured);
        }

        bindEvents() {
            // Send on button click.
            this.sendBtn.addEventListener('click', () => this.sendMessage());

            // Send on Enter (Ctrl+Enter for new line).
            this.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Enable/disable button.
            this.input.addEventListener('input', () => {
                this.sendBtn.disabled = this.input.value.trim().length === 0 || !this.ollamaConfigured;
            });
        }

        async sendMessage() {
            // Check if Ollama is configured.
            if (!this.ollamaConfigured) {
                Notification.alert('Ollama not configured', 'Please configure Ollama in plugin settings to use chat.');
                return;
            }

            const text = this.input.value.trim();
            if (!text) return;

            // Add user message.
            this.addMessage('user', text);

            // Clear input.
            this.input.value = '';
            this.sendBtn.disabled = true;

            // Show typing indicator.
            this.setTyping(true);

            try {
                const response = await Ajax.call([{
                    methodname: 'aiplacement_chat_send_message',
                    args: {
                        message: text,
                        courseid: this.courseid,
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

        addMessage(role, content) {
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

        formatMessage(text) {
            // Simple formatting.
            text = text.replace(/\n/g, '<br>');
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
            text = text.replace(/`(.*?)`/g, '<code>$1</code>');
            return text;
        }

        setTyping(typing) {
            if (this.typingIndicator) {
                this.typingIndicator.style.display = typing ? 'flex' : 'none';
            }

            if (typing) {
                // Auto-scroll.
                setTimeout(() => {
                    this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
                }, 100);
            }
        }

        async loadHistory() {
            try {
                const response = await Ajax.call([{
                    methodname: 'aiplacement_chat_get_history',
                    args: {
                        courseid: this.courseid,
                        limit: 20
                    }
                }])[0];

                const history = JSON.parse(response.history);

                history.forEach(msg => {
                    this.addMessage(msg.role, msg.content);
                });

            } catch (error) {
                console.log('No history');
            }
        }

        clearHistory() {
            Ajax.call([{
                methodname: 'aiplacement_chat_clear_history',
                args: { courseid: this.courseid }
            }]);

            this.messagesContainer.innerHTML = '';
            this.messages = [];

            // Welcome message.
            this.addMessage('assistant', '👋 Hello! I am the AI assistant for this course. Ask me about the materials, assignments, or course structure.');
        }

        show() {
            if (this.container) {
                this.container.style.display = 'flex';
            }
        }

        hide() {
            if (this.container) {
                this.container.style.display = 'none';
            }
        }
        
        toggle() {
            if (this.container) {
                var currentDisplay = this.container.style.display;
                this.container.style.display = (currentDisplay === 'none' || currentDisplay === '') ? 'flex' : 'none';
            }
        }
    }

    return {
        init: function(containerId, config) {
            chatInstance = new CourseChat(containerId, config);
            
            // Set up toggle handler for the action button
            setTimeout(function() {
                var toggleBtn = document.querySelector('.aiplacement-chat-action button');
                if (toggleBtn && chatInstance) {
                    toggleBtn.onclick = function(e) {
                        e.preventDefault();
                        chatInstance.toggle();
                    };
                    console.log('Chat toggle button handler attached');
                }
            }, 500);
            
            return chatInstance;
        },
        toggle: function() {
            if (chatInstance) {
                chatInstance.toggle();
            }
        },
        getInstance: function() {
            return chatInstance;
        }
    };
});
