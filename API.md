# API Documentation

API эндпоинты, перенесенные из Go проекта NautbekInfo в Laravel.

## Эндпоинты

### POST /api/register

Регистрация нового пользователя. Генерирует уникальный UUID и сохраняет его в базе.

**Заголовки:**
- Не требуются

**Параметры:**
- Не требуются (все генерируется на сервере)

**Пример запроса:**
```bash
curl -X POST http://45.146.167.233:123/api/register
```

**Успешный ответ (201):**
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Ошибки:**
- `500` - Internal Server Error

**Важно:** Этот эндпоинт должен вызываться только один раз при первом запуске приложения, если в локальном хранилище еще нет UUID.

---

### POST /api/user_open

Увеличивает счетчик посещений для указанного приложения.

**Заголовки:**
- `X-User-UUID` (optional, string) - UUID пользователя, полученный при регистрации

**Параметры:**
- `app` (required, string, max:40) - Название приложения

**Пример запроса:**
```bash
curl -X POST http://localhost/api/user_open \
  -H "X-User-UUID: 550e8400-e29b-41d4-a716-446655440000" \
  -d "app=my-app"
```

**Успешный ответ:**
```json
{
  "status": "Ok"
}
```

**Ошибки:**
- `400` - App param required
- `500` - Internal Server Error

---

### POST /api/user_feedback

Сохраняет отзыв пользователя и отправляет уведомление в Telegram.

**Заголовки:**
- `X-User-UUID` (optional, string) - UUID пользователя, полученный при регистрации

**Параметры:**
- `app` (required, string, max:40) - Название приложения
- `text` (required, string) - Текст отзыва

**Пример запроса:**
```bash
curl -X POST http://localhost/api/user_feedback \
  -H "X-User-UUID: 550e8400-e29b-41d4-a716-446655440000" \
  -d "app=my-app" \
  -d "text=Great app!"
```

**Успешный ответ:**
```json
{
  "status": "Ok"
}
```

**Ошибки:**
- `400` - App param required / Text param required
- `500` - Internal Server Error

---

### POST /api/nutrition/products

Создаёт продукт в справочнике питания со статусом `Draft` (0).

**Заголовки:**
- `X-User-UUID` (optional, string) — UUID пользователя-автора (альтернатива полю `uuid` в теле запроса)

**Параметры:**
- `uuid` (required, uuid) — UUID пользователя-автора
- `name` (required, string, max:255) — название продукта
- `description` (optional, string) — описание
- `proteins` (required, numeric, min:0) — белки
- `fats` (required, numeric, min:0) — жиры
- `carbs` (required, numeric, min:0) — углеводы
- `calories` (required, numeric, min:0) — калорийность

**Пример запроса:**
```bash
curl -X POST http://localhost/api/nutrition/products \
  -H "X-User-UUID: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Овсянка",
    "description": "Геркулес",
    "proteins": 12.3,
    "fats": 6.1,
    "carbs": 59.5,
    "calories": 342
  }'
```

**Успешный ответ (201):**
```json
{
  "id": 1,
  "status": 0
}
```

**Статусы продукта:**
- `0` — Draft
- `1` — Active
- `2` — Decline

**Ошибки:**
- `401` — пользователь с указанным UUID не найден
- `422` — ошибка валидации
- `500` — Internal Server Error

---

### GET /api/nutrition/products/search

Поиск продуктов по названию (регистронезависимый, подстрока). Пагинация — 20 записей на страницу.

**Параметры (query):**
- `name` (required, string, min:1, max:255) — строка поиска по названию
- `page` (optional, integer, min:1) — номер страницы

**Пример запроса:**
```bash
curl "http://localhost/api/nutrition/products/search?name=овсян&page=1"
```

**Успешный ответ (200):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Овсянка",
      "description": "Геркулес",
      "proteins": 12.3,
      "fats": 6.1,
      "carbs": 59.5,
      "calories": 342,
      "author_uuid": "550e8400-e29b-41d4-a716-446655440000",
      "status": 1,
      "created_at": "2026-06-11T12:00:00.000000Z",
      "updated_at": "2026-06-11T12:00:00.000000Z"
    }
  ],
  "per_page": 20,
  "total": 1,
  "last_page": 1
}
```

**Ошибки:**
- `422` — ошибка валидации
- `500` — Internal Server Error

---

## Настройка Telegram уведомлений

Добавьте в `.env` файл:

```env
TELEGRAM_API_URL=https://api.telegram.org/bot<YOUR_BOT_TOKEN>/sendMessage
TELEGRAM_CHAT_ID=your_chat_id
```

## Архитектура

### Контроллеры
- `App\Http\Controllers\Api\UserOpenController` - обработка посещений
- `App\Http\Controllers\Api\UserFeedbackController` - обработка отзывов

### Модели (Eloquent)
- `App\Models\UserVisit` - модель посещений
- `App\Models\UserFeedback` - модель отзывов

### Request классы (валидация)
- `App\Http\Requests\UserOpenRequest` - валидация запроса посещений
- `App\Http\Requests\UserFeedbackRequest` - валидация запроса отзывов

### Сервисы
- `App\Services\TelegramNotificationService` - отправка уведомлений в Telegram

## Отличия от Go версии

1. **Валидация**: Используются Laravel Request классы вместо ручной проверки
2. **ООП подход**: Все операции через Eloquent модели вместо прямых SQL запросов
3. **Dependency Injection**: Сервисы внедряются через конструктор
4. **Обработка ошибок**: Используется Laravel Log вместо прямого вывода
5. **HTTP ответы**: Стандартизированные JSON ответы через JsonResponse
