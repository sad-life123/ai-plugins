// /ai/placement/coursechat/amd/src/chat.js

define(['core/ajax', 'core/str', 'core/notification'], function(Ajax, Str, Notification) {
    
    class CourseChat {
        
        constructor(containerId, config) {
            this.container = document.getElementById(containerId);
            this.config = config;
            this.courseid = config.courseid;
            this.contextid = config.contextid;
            this.messages = [];
            this.typingTimer = null;
            
            this.init();
        }
        
        init() {
            if (!this.container) return;
            
            this.messagesContainer = this.container.querySelector('.coursechat-messages');
            this.input = this.container.querySelector('.coursechat-input');
            this.sendBtn = this.container.querySelector('.coursechat-send');
            this.typingIndicator = this.container.querySelector('.coursechat-typing');
            
            this.bindEvents();
            this.loadHistory();
        }
        
        bindEvents() {
            // Отправка по кнопке
            this.sendBtn.addEventListener('click', () => this.sendMessage());
            
            // Отправка по Enter (Ctrl+Enter для новой строки)
            this.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            // Активация кнопки
            this.input.addEventListener('input', () => {
                this.sendBtn.disabled = this.input.value.trim().length === 0;
            });
        }
        
        async sendMessage() {
            const text = this.input.value.trim();
            if (!text) return;
            
            // Добавляем сообщение пользователя
            this.addMessage('user', text);
            
            // Очищаем input
            this.input.value = '';
            this.sendBtn.disabled = true;
            
            // Показываем "печатает"
            this.setTyping(true);
            
            try {
                const response = await Ajax.call([{
                    methodname: 'coursechat_send_message',
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
                    this.addMessage('system', response.message || 'Ошибка');
                }
                
            } catch (error) {
                this.setTyping(false);
                Notification.exception(error);
                this.addMessage('system', 'Ошибка соединения');
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
            // Простое форматирование
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
                // Авто-скролл
                setTimeout(() => {
                    this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
                }, 100);
            }
        }
        
        async loadHistory() {
            try {
                const response = await Ajax.call([{
                    methodname: 'coursechat_get_history',
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
                methodname: 'coursechat_clear_history',
                args: { courseid: this.courseid }
            }]);
            
            this.messagesContainer.innerHTML = '';
            this.messages = [];
            
            // Приветственное сообщение
            this.addMessage('assistant', '👋 Здравствуйте! Я AI помощник этого курса. Спрашивайте меня о материалах, заданиях, структуре курса.');
        }
    }
    
    return {
        init: function(containerId, config) {
            return new CourseChat(containerId, config);
        }
    };
});