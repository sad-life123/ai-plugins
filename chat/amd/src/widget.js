// /ai/placement/coursechat/amd/src/widget.js

define([
    'core/str',
    'core/notification',
    'core/templates',
    'aiplacement_coursechat/chat'
], function(Str, Notification, Templates, Chat) {
    
    class ChatWidget {
        
        constructor(config) {
            this.config = config;
            this.isOpen = false;
            
            this.init();
        }
        
        init() {
            // Создаем кнопку
            this.createButton();
            
            // Загружаем состояние из localStorage
            const savedState = localStorage.getItem('coursechat_' + this.config.courseid);
            if (savedState === 'open') {
                setTimeout(() => this.openChat(), 500);
            }
        }
        
        createButton() {
            const button = document.createElement('button');
            button.className = 'coursechat-widget-button';
            button.id = 'coursechat-widget-btn';
            button.innerHTML = '💬';
            button.setAttribute('aria-label', 'Открыть чат');
            
            // Позиция из настроек
            const position = this.config.position || 'right';
            if (position === 'left') button.classList.add('left');
            if (position === 'bottom') button.classList.add('bottom');
            
            button.addEventListener('click', () => this.toggleChat());
            
            document.body.appendChild(button);
        }
        
        toggleChat() {
            if (this.isOpen) {
                this.closeChat();
            } else {
                this.openChat();
            }
        }
        
        openChat() {
            if (this.isOpen) return;
            
            Templates.render('aiplacement_coursechat/chat', {
                courseid: this.config.courseid,
                contextid: this.config.contextid,
                uniqid: Date.now()
            }).then((html) => {
                const container = document.createElement('div');
                container.innerHTML = html;
                document.body.appendChild(container.firstChild);
                
                const chatContainer = document.querySelector('.coursechat-container');
                if (chatContainer) {
                    Chat.init(chatContainer.id, this.config);
                }
                
                this.isOpen = true;
                localStorage.setItem('coursechat_' + this.config.courseid, 'open');
                
                // Скрываем кнопку
                const btn = document.getElementById('coursechat-widget-btn');
                if (btn) btn.style.display = 'none';
                
            }).catch(Notification.exception);
        }
        
        closeChat() {
            const container = document.querySelector('.coursechat-container');
            if (container) container.remove();
            
            this.isOpen = false;
            localStorage.removeItem('coursechat_' + this.config.courseid);
            
            // Показываем кнопку
            const btn = document.getElementById('coursechat-widget-btn');
            if (btn) btn.style.display = 'flex';
        }
    }
    
    return {
        init: function(config) {
            return new ChatWidget(config);
        }
    };
});