🚀 ПРОСТАЯ УСТАНОВКА AI QUIZ GENERATOR
ШАГ 1: Копируем только нужные файлы
bash
cd /var/lms/lms.bsuir.by/www/ai/placement/

# СОЗДАЕМ СТРУКТУРУ
mkdir -p quizgen/{lang/ru,classes/{external},amd/src,templates,db}

# КОПИРУЕМ ТОЛЬКО НУЖНЫЕ ФАЙЛЫ:
📋 СПИСОК ФАЙЛОВ ДЛЯ КОПИРОВАНИЯ:
bash
# 1. Корневые файлы
cp /путь/к/version.php quizgen/
cp /путь/к/settings.php quizgen/
cp /путь/к/lib.php quizgen/
cp /путь/к/styles.css quizgen/

# 2. Языковой файл
cp /путь/к/quizgen.php quizgen/lang/ru/

# 3. Классы
cp /путь/к/placement.php quizgen/classes/
cp /путь/к/generator.php quizgen/classes/
cp /путь/к/question_bank.php quizgen/classes/
cp /путь/к/generate.php quizgen/classes/external/
cp /путь/к/save_to_bank.php quizgen/classes/external/

# 4. JavaScript
cp /путь/к/generator.js quizgen/amd/src/
cp /путь/к/preview.js quizgen/amd/src/

# 5. Шаблоны
cp /путь/к/generator.mustache quizgen/templates/
cp /путь/к/question_preview.mustache quizgen/templates/

# 6. DB конфиги
cp /путь/к/access.php quizgen/db/
cp /путь/к/services.php quizgen/db/
ШАГ 2: Устанавливаем права
bash
chmod -R 755 quizgen/
chown -R www-data:www-data quizgen/
ШАГ 3: Запускаем установку
bash
cd /var/lms/lms.bsuir.by/www
php admin/cli/upgrade.php
ШАГ 4: Настройка в админке
Администрирование → Плагины → AI → Quiz Generator

URL Ollama: http://localhost:11434

Модель: qwen2.5:7b (или llama3.1)

Количество вопросов: 5

Тип вопросов: Множественный выбор

Сохранить

ШАГ 5: Проверка работы
Способ 1: Через меню курса
Зайдите в любой курс

В меню курса появится пункт "📝 AI Генератор тестов"

Нажмите → вставьте текст → "Сгенерировать"

Способ 2: Через редактор
Создайте лекцию/страницу

В редакторе нажмите кнопку "📝 AI Тест"

Выделите текст → сгенерируйте