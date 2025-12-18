# Настройка переменных окружения для Docker

## Важно!

В Docker контейнере Laravel должен подключаться к PostgreSQL используя **имя сервиса** `postgres`, а не `127.0.0.1`.

## Настройка .env файла

Создайте или обновите файл `.env` в корне проекта:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres          # ⚠️ Имя сервиса из docker-compose.yml, НЕ 127.0.0.1!
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=password

REDIS_HOST=redis          # ⚠️ Имя сервиса из docker-compose.yml
REDIS_PORT=6379
```

## Альтернатива: переменные окружения в docker-compose.yml

Переменные окружения уже настроены в `docker-compose.yml` для PHP контейнера. Они будут использоваться, если не заданы в `.env` файле.

## После изменения .env

```bash
# Перезапустите контейнеры
docker-compose restart php

# Или пересоберите
docker-compose up -d --build

# Очистите кеш конфигурации
docker-compose exec php php artisan config:clear
```

## Проверка подключения

```bash
# Проверьте подключение к PostgreSQL из PHP контейнера
docker-compose exec php php artisan tinker
>>> DB::connection()->getPdo();
```

Если видите объект PDO - подключение работает!

