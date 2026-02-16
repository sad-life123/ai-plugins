// /ai/placement/quizgen/amd/src/preview.js

define(['core/str', 'core/notification'], function(Str, Notification) {
    
    class QuestionPreview {
        
        constructor() {
            this.init();
        }
        
        init() {
            // Обработчики для динамически добавленных вопросов
            document.addEventListener('click', (e) => {
                // Показать/скрыть объяснение
                if (e.target.closest('.show-explanation')) {
                    const explanation = e.target.closest('.quizgen-question-card')
                        ?.querySelector('.quizgen-explanation');
                    if (explanation) {
                        explanation.style.display = 
                            explanation.style.display === 'none' ? 'block' : 'none';
                    }
                }
                
                // Копировать вопрос
                if (e.target.closest('.copy-question')) {
                    const questionText = e.target.closest('.quizgen-question-card')
                        ?.querySelector('.quizgen-question-text')?.textContent;
                    if (questionText) {
                        navigator.clipboard.writeText(questionText);
                        Notification.addNotification({
                            message: '✅ Вопрос скопирован',
                            type: 'success'
                        });
                    }
                }
            });
        }
        
        formatQuestionText(text) {
            if (!text) return '';
            
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code>$1</code>')
                .replace(/\n/g, '<br>');
        }
    }
    
    return new QuestionPreview();
});