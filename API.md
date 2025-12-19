# API Documentation

API эндпоинты, перенесенные из Go проекта NautbekInfo в Laravel.

## Эндпоинты

### POST /api/user_open

Увеличивает счетчик посещений для указанного приложения.

**Параметры:**
- `app` (required, string, max:40) - Название приложения

**Пример запроса:**
```bash
curl -X POST http://localhost/api/user_open \
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

**Параметры:**
- `app` (required, string, max:40) - Название приложения
- `text` (required, string) - Текст отзыва

**Пример запроса:**
```bash
curl -X POST http://localhost/api/user_feedback \
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

