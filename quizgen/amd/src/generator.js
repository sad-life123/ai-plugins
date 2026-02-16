// /ai/placement/quizgen/amd/src/generator.js

define(['core/ajax', 'core/str', 'core/notification', 'core/templates'], 
    function(Ajax, Str, Notification, Templates) {
    
    class QuizGenerator {
        
        constructor(containerId, config = {}) {
            this.container = document.getElementById(containerId);
            this.config = config;
            this.questions = [];
            this.selectedQuestions = new Set();
            
            this.init();
        }
        
        init() {
            if (!this.container) return;
            
            this.form = this.container.querySelector('.quizgen-form');
            this.textarea = this.container.querySelector('.quizgen-textarea');
            this.countSelect = this.container.querySelector('.quizgen-question-count');
            this.typeSelect = this.container.querySelector('.quizgen-question-type');
            this.difficultySelect = this.container.querySelector('.quizgen-difficulty');
            this.languageSelect = this.container.querySelector('.quizgen-language');
            this.generateBtn = this.container.querySelector('.quizgen-generate-btn');
            this.progressBar = this.container.querySelector('.quizgen-progress');
            this.questionsGrid = this.container.querySelector('.quizgen-questions-grid');
            this.saveAllBtn = this.container.querySelector('.quizgen-save-all-btn');
            
            this.bindEvents();
            this.loadDefaults();
        }
        
        bindEvents() {
            // Генерация
            this.generateBtn.addEventListener('click', () => this.generate());
            
            // Авто-отправка по Ctrl+Enter
            this.textarea.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    this.generate();
                }
            });
            
            // Сохранение всех
            if (this.saveAllBtn) {
                this.saveAllBtn.addEventListener('click', () => this.saveAll());
            }
            
            // Обновление счетчика символов
            this.textarea.addEventListener('input', () => this.updateCharCount());
        }
        
        async generate() {
            const text = this.textarea.value.trim();
            if (!text) {
                Notification.alert('Ошибка', 'Введите текст для генерации теста');
                return;
            }
            
            const params = {
                text: text,
                count: parseInt(this.countSelect?.value || 5),
                type: this.typeSelect?.value || 'multichoice',
                difficulty: this.difficultySelect?.value || 'medium',
                language: this.languageSelect?.value || 'ru',
                contextid: this.config.contextid || 0
            };
            
            this.setLoading(true);
            this.questions = [];
            this.selectedQuestions.clear();
            
            try {
                const response = await Ajax.call([{
                    methodname: 'quizgen_generate',
                    args: params
                }])[0];
                
                if (response.success) {
                    this.questions = JSON.parse(response.questions) || [];
                    await this.renderQuestions();
                    
                    Str.get_string('success_generated', 'quizgen', this.questions.length)
                        .then(s => Notification.addNotification({
                            message: s,
                            type: 'success'
                        }));
                    
                } else {
                    throw new Error(response.error || 'Generation failed');
                }
                
            } catch (error) {
                Notification.exception(error);
                this.questionsGrid.innerHTML = `
                    <div class="alert alert-danger">
                        ❌ Ошибка генерации: ${error.message}
                    </div>
                `;
            } finally {
                this.setLoading(false);
            }
        }
        
        async renderQuestions() {
            if (!this.questionsGrid || this.questions.length === 0) {
                this.questionsGrid.innerHTML = `
                    <div class="alert alert-info">
                        🤖 Нажмите "Сгенерировать" для создания вопросов
                    </div>
                `;
                return;
            }
            
            const html = await Templates.render('aiplacement_quizgen/question_preview', {
                questions: this.questions,
                showSelect: true
            });
            
            this.questionsGrid.innerHTML = html;
            
            // Добавляем обработчики для каждого вопроса
            this.questions.forEach((q, index) => {
                const card = this.questionsGrid.querySelector(`[data-question-index="${index}"]`);
                if (card) {
                    this.bindQuestionEvents(card, q, index);
                }
            });
        }
        
        bindQuestionEvents(card, question, index) {
            // Выбор вопроса
            const selectBtn = card.querySelector('.question-select');
            if (selectBtn) {
                selectBtn.addEventListener('click', () => {
                    if (this.selectedQuestions.has(index)) {
                        this.selectedQuestions.delete(index);
                        selectBtn.classList.remove('btn-primary');
                        selectBtn.classList.add('btn-outline-primary');
                    } else {
                        this.selectedQuestions.add(index);
                        selectBtn.classList.remove('btn-outline-primary');
                        selectBtn.classList.add('btn-primary');
                    }
                });
            }
            
            // Регенерация
            const regenBtn = card.querySelector('.question-regenerate');
            if (regenBtn) {
                regenBtn.addEventListener('click', () => this.regenerateQuestion(index));
            }
            
            // Сохранение
            const saveBtn = card.querySelector('.question-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => this.saveQuestion(question));
            }
            
            // Редактирование
            const editBtn = card.querySelector('.question-edit');
            if (editBtn) {
                editBtn.addEventListener('click', () => this.editQuestion(question, card));
            }
        }
        
        async saveAll() {
            if (this.questions.length === 0) return;
            
            const questionsToSave = this.selectedQuestions.size > 0 
                ? this.questions.filter((_, i) => this.selectedQuestions.has(i))
                : this.questions;
            
            this.setSaving(true);
            
            try {
                const response = await Ajax.call([{
                    methodname: 'quizgen_save_to_bank',
                    args: {
                        questions: JSON.stringify(questionsToSave),
                        courseid: this.config.courseid || 0
                    }
                }])[0];
                
                if (response.success) {
                    Str.get_string('success_saved', 'quizgen', response.saved_count)
                        .then(s => Notification.addNotification({
                            message: s,
                            type: 'success'
                        }));
                    
                    this.selectedQuestions.clear();
                    await this.renderQuestions();
                }
                
            } catch (error) {
                Notification.exception(error);
            } finally {
                this.setSaving(false);
            }
        }
        
        async saveQuestion(question) {
            try {
                const response = await Ajax.call([{
                    methodname: 'quizgen_save_to_bank',
                    args: {
                        questions: JSON.stringify([question]),
                        courseid: this.config.courseid || 0
                    }
                }])[0];
                
                if (response.success) {
                    Notification.addNotification({
                        message: '✅ Вопрос сохранен в банк',
                        type: 'success'
                    });
                }
                
            } catch (error) {
                Notification.exception(error);
            }
        }
        
        async regenerateQuestion(index) {
            // TODO: регенерация конкретного вопроса
        }
        
        editQuestion(question, card) {
            // TODO: редактирование вопроса
        }
        
        setLoading(loading) {
            if (loading) {
                this.generateBtn.disabled = true;
                this.generateBtn.innerHTML = '<span class="quizgen-spinner"></span> Генерация...';
                this.progressBar?.classList.add('active');
            } else {
                this.generateBtn.disabled = false;
                this.generateBtn.innerHTML = '🎯 Сгенерировать';
                this.progressBar?.classList.remove('active');
            }
        }
        
        setSaving(saving) {
            if (this.saveAllBtn) {
                this.saveAllBtn.disabled = saving;
                this.saveAllBtn.innerHTML = saving 
                    ? '💾 Сохранение...' 
                    : '💾 Сохранить все в банк';
            }
        }
        
        updateCharCount() {
            const counter = this.container.querySelector('.quizgen-char-counter');
            if (counter) {
                const len = this.textarea.value.length;
                counter.textContent = `${len} символов`;
            }
        }
        
        loadDefaults() {
            // Загружаем настройки по умолчанию из конфига
            if (this.config.defaults) {
                if (this.countSelect) this.countSelect.value = this.config.defaults.count || 5;
                if (this.typeSelect) this.typeSelect.value = this.config.defaults.type || 'multichoice';
                if (this.difficultySelect) this.difficultySelect.value = this.config.defaults.difficulty || 'medium';
            }
        }
    }
    
    return {
        init: function(containerId, config) {
            return new QuizGenerator(containerId, config);
        }
    };
});