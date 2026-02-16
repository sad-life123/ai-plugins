# Проверка Ollama
curl http://localhost:11434/api/tags

# Установка модели (рекомендуется для русского языка)
ollama pull qwen2.5:7b      # 4.1GB - лучший русский
ollama pull llama3.1        # 4.7GB - лучший общий
ollama pull mistral         # 4.1GB - быстрый

# Проверка модели
ollama run qwen2.5:7b "Привет"

# Создаем структуру
cd /var/lms/lms.bsuir.by/www/ai/placement/
mkdir -p coursechat/{lang/ru,classes/{external,observers},amd/src,templates,db}

# Копируем все файлы
# ...

# Устанавливаем права
chmod -R 755 coursechat/
chown -R www-data:www-data coursechat/

# Запускаем установку
cd /var/lms/lms.bsuir.by/www
php admin/cli/upgrade.php

3️⃣ Настройка в админке:
Администрирование → Плагины → AI → Course Chat

Укажите: http://localhost:11434

Выберите модель: qwen2.5:7b (или другая)

Включите контекст: файлы, активности, структура

Сохраните