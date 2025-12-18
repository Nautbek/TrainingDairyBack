# Docker Setup

## Запуск контейнеров

```bash
# Сборка и запуск всех сервисов
docker-compose up -d --build

# Просмотр логов
docker-compose logs -f

# Остановка
docker-compose down

# Остановка с удалением volumes
docker-compose down -v
```

## Установка зависимостей

```bash
# Установка Composer зависимостей
docker-compose exec php composer install

# Установка NPM зависимостей (на хосте)
npm install

# Генерация ключа приложения
docker-compose exec php php artisan key:generate

# Запуск миграций
docker-compose exec php php artisan migrate
```

## Доступ к сервисам

- **Nginx**: http://localhost
- **Redis**: localhost:6379
- **PHP-FPM**: доступен через nginx

## Полезные команды

```bash
# Выполнение artisan команд
docker-compose exec php php artisan [command]

# Доступ к контейнеру PHP
docker-compose exec php sh

# Просмотр логов PHP
docker-compose logs -f php

# Просмотр логов Redis
docker-compose logs -f redis

# Перезапуск сервиса
docker-compose restart php
```

## Размеры образов

- `php:8.4-fpm-alpine` - ~150MB
- `redis:7-alpine` - ~30MB
- `nginx:alpine` - ~25MB

**Общий размер: ~205MB** (без зависимостей)

