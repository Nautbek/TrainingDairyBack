# Миграции и модели для NautbekInfo

Этот проект содержит Laravel миграции и модели, созданные на основе Go-проекта NautbekInfo.

## Структура файлов

```
database/migrations/
  ├── 2024_01_01_000001_create_user_visits_table.php
  └── 2024_01_01_000002_create_user_feedback_table.php

app/Models/
  ├── UserVisit.php
  └── UserFeedback.php
```

## Установка

1. Скопируйте файлы миграций в директорию `database/migrations/` вашего Laravel проекта
2. Скопируйте модели в директорию `app/Models/` вашего Laravel проекта

## Запуск миграций

```bash
php artisan migrate
```

## Использование моделей

### UserVisit - отслеживание посещений

```php
use App\Models\UserVisit;

// Увеличить счетчик посещений (аналог IncrementVisitCount из Go)
UserVisit::incrementVisitCount($request->ip(), 'my-app');
```

### UserFeedback - сохранение отзывов

```php
use App\Models\UserFeedback;

// Сохранить отзыв пользователя (аналог SaveUserFeedback из Go)
UserFeedback::saveFeedback($request->ip(), 'my-app', $request->input('text'));
```

## Описание таблиц

### user_visits
- Отслеживает количество посещений по IP-адресу, дате и приложению
- Уникальный индекс на (`visit_date`, `visit_ip`) предотвращает дублирование
- При повторном посещении счетчик автоматически увеличивается

### user_feedback
- Хранит отзывы и обратную связь от пользователей
- Индексы на `visit_date` и `app` для быстрого поиска

## Примечания

- Проект использует PostgreSQL
- В оригинальном Go-проекте `visit_date` хранился как VARCHAR, в Laravel используется тип DATE
- Метод `incrementVisitCount` использует PostgreSQL-специфичный синтаксис `ON CONFLICT` для атомарного обновления счетчика

