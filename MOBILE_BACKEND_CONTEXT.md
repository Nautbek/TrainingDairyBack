# TrainingDairyBack — контекст для мобильного приложения

Документ описывает текущее состояние бэкенда (июнь 2026). Используй его как отправную точку при разработке мобильного клиента журнала питания.

---

## Проект

- **Репозиторий:** `TrainingDairyBack`
- **Стек:** Laravel 12, PHP 8.4, PostgreSQL, Redis
- **Назначение:** бэкенд для мобильных приложений (журнал тренировок + журнал питания)
- **Сейчас реализовано:** аналитика пользователей (legacy) + **модуль питания: справочник продуктов**
- **Реализовано также:** донаты через ЮKassa (отключение рекламы)
- **Ещё не реализовано:** приёмы пищи, дневник питания, журнал тренировок

---

## Продакшен

| Параметр | Значение |
|----------|----------|
| Хост | `45.146.167.233` |
| API (мобилки) | `http://45.146.167.233:123/api/...` |
| Браузер | порт **123 заблокирован** Chrome/Firefox (`ERR_UNSAFE_PORT`) — для веб-админки нужен **80/443/8080** |
| Проверка API с сервера | `curl http://127.0.0.1:123/api/register` |

Префикс всех API-роутов: `/api`

---

## Аутентификация

Классической авторизации нет. Модель — **анонимный пользователь с UUID**.

1. При первом запуске приложение вызывает `POST /api/register`
2. Сервер возвращает `uuid`, приложение сохраняет локально
3. В последующих запросах передаётся:
   - заголовок `X-User-UUID: <uuid>`, **или**
   - поле `uuid` в теле JSON / form-data

Для эндпоинтов питания UUID **обязателен** при создании продукта. Сервер проверяет, что такой пользователь есть в `users.uuid`. Если нет → `401 Unauthorized`.

---

## Модуль питания — доменная модель

### Таблица `nutrition_products`

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | bigint PK | ID продукта |
| `uuid` | uuid, unique | Публичный идентификатор продукта |
| `name` | string | Название |
| `description` | text, nullable | Описание |
| `proteins` | decimal(8,2) | Белки на 100 г |
| `fats` | decimal(8,2) | Жиры на 100 г |
| `carbs` | decimal(8,2) | Углеводы на 100 г |
| `calories` | decimal(8,2) | Калорийность на 100 г |
| `author_uuid` | uuid | UUID создателя |
| `status` | tinyint | Статус модерации |
| `created_at`, `updated_at` | timestamp | |

### Статусы продукта (`App\Enums\Nutrition\ProductStatus`)

| Значение | Enum | Смысл |
|----------|------|-------|
| `0` | `Draft` | Черновик, на модерации |
| `1` | `Active` | Одобрен, виден пользователям |
| `2` | `Decline` | Отклонён |

**Важно для мобилки:** при поиске продуктов API **пока не фильтрует** по статусу — возвращает все совпадения. В UI клиента показывай только `status === 1` (Active), либо договорись о доработке бэка.

Новый продукт из приложения всегда создаётся со статусом `Draft` (0). Модерация — через веб-админку.

---

## API — журнал питания (продукты)

### POST `/api/nutrition/products`

Создание продукта пользователем.

**Заголовки:**
- `Content-Type: application/json`
- `X-User-UUID` (опционально, альтернатива полю `uuid`)

**Тело (JSON):**

```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "Овсянка",
  "description": "Геркулес",
  "proteins": 12.3,
  "fats": 6.1,
  "carbs": 59.5,
  "calories": 342
}
```

| Поле | Обязательно | Правила |
|------|-------------|---------|
| `uuid` | да | uuid, пользователь должен существовать |
| `name` | да | string, max 255 |
| `description` | нет | string |
| `proteins` | да | numeric, min 0 |
| `fats` | да | numeric, min 0 |
| `carbs` | да | numeric, min 0 |
| `calories` | да | numeric, min 0 |

**Ответ 201:**
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440001",
  "status": 0
}
```

`uuid` генерируется на сервере при создании — клиент не передаёт.

**Ошибки:**
- `401` — `{"error": "Unauthorized"}` (UUID не найден)
- `422` — ошибки валидации
- `500` — `{"error": "Internal Server Error"}`

---

### GET `/api/nutrition/products/search`

Поиск продуктов по названию (подстрока, без учёта регистра).

**Query-параметры:**

| Параметр | Обязательно | Описание |
|----------|-------------|----------|
| `name` | да | Строка поиска, 1–255 символов |
| `page` | нет | Номер страницы, default 1 |

**Пример:**
```
GET /api/nutrition/products/search?name=овсян&page=1
```

**Ответ 200** — стандартная Laravel-пагинация, **20 записей** на страницу:

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440001",
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
  "first_page_url": "...",
  "from": 1,
  "last_page": 1,
  "last_page_url": "...",
  "links": [...],
  "next_page_url": null,
  "path": "...",
  "per_page": 20,
  "prev_page_url": null,
  "to": 1,
  "total": 1
}
```

**Ошибки:**
- `422` — нет `name` или невалидные параметры
- `500`

---

## API — донаты / отключение рекламы (ЮKassa)

### Тарифы

| `tier` | Сумма | Месяцев без рекламы |
|--------|-------|---------------------|
| `100` | 100 ₽ | 1 |
| `200` | 200 ₽ | 2 |
| `300` | 300 ₽ | 3 |

### POST `/api/donations/create`

Создаёт платёж в ЮKassa, возвращает URL для оплаты в WebView.

**Заголовки:** `X-User-UUID` (опционально)

**Тело:**
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "tier": 100
}
```

**Ответ 201:**
```json
{
  "payment_uuid": "...",
  "confirmation_url": "https://yoomoney.ru/...",
  "amount": 100,
  "months": 1,
  "status": "pending"
}
```

Приложение открывает `confirmation_url` в WebView/браузере.

### GET `/api/user/subscription`

**Query или заголовок:** `uuid` / `X-User-UUID`

**Ответ 200:**
```json
{
  "is_ad_free": true,
  "ad_free_until": "2026-07-11T12:00:00+00:00"
}
```

### GET `/api/donations/{payment_uuid}/status`

Проверка статуса платежа (опрос после возврата из WebView). Синхронизирует статус с ЮKassa.

**Query:** `uuid` — владелец платежа

**Ответ 200:**
```json
{
  "payment_uuid": "...",
  "status": "succeeded",
  "amount": 100,
  "months": 1,
  "paid_at": "2026-06-12T12:00:00+00:00"
}
```

Статусы платежа: `pending`, `succeeded`, `canceled`.

### POST `/api/yookassa/webhook`

Webhook для ЮKassa (настраивается в ЛК). При `payment.succeeded` продлевает `users.ad_free_until`.

### Сценарий в приложении

1. `GET /api/user/subscription` — показать/скрыть рекламу
2. Юзер выбирает тариф → `POST /api/donations/create`
3. WebView на `confirmation_url`
4. После возврата → `GET /api/donations/{payment_uuid}/status` или снова `GET /api/user/subscription`
5. Если `is_ad_free` — скрыть рекламу

### .env на сервере

```env
YOOKASSA_SHOP_ID=...
YOOKASSA_SECRET_KEY=...
YOOKASSA_RETURN_URL=http://45.146.167.233:123/payment/return
```

Webhook URL в ЛК ЮKassa: `http://45.146.167.233:123/api/yookassa/webhook`

---

## API — общие (legacy, уже используются приложениями)

### POST `/api/register`

Регистрация анонимного пользователя. Вызывать **один раз** при первом запуске.

**Тело:** пустое

**Ответ 201:**
```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

---

### POST `/api/user_open`

Аналитика открытий приложения.

**Заголовки:** `X-User-UUID` (опционально)

**Тело (form / JSON):**
- `app` (required, string, max 40) — идентификатор приложения, напр. `nutrition_diary`

**Ответ 200:** `{"status": "Ok"}`

---

### POST `/api/user_feedback`

Отзыв пользователя + уведомление в Telegram.

**Заголовки:** `X-User-UUID` (опционально)

**Тело:**
- `app` (required, string, max 40)
- `text` (required, string)

**Ответ 200:** `{"status": "Ok"}`

---

## Веб-админка (не для мобилки)

| URL | Описание |
|-----|----------|
| `GET /admin23432150732412134` | Список продуктов |
| `POST /admin23432150732412134/products/{id}/approve` | Одобрить черновик → Active |
| `POST /admin23432150732412134/products/{id}/decline` | Отклонить → Decline |
| `POST /admin23432150732412134/products/{id}/delete` | Удалить |

Функции: табы по статусам (жёлтый / зелёный / красный), поиск по названию, пагинация 20 шт.

Без авторизации (осознанное решение для стартапа).

---

## Структура кода (питание)

```
app/
├── Enums/Nutrition/ProductStatus.php
├── Models/Nutrition/Product.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/Nutrition/
│   │   │   ├── ProductStoreController.php
│   │   │   └── ProductSearchController.php
│   │   └── Admin/NutritionProductController.php
│   └── Requests/Nutrition/
│       ├── StoreProductRequest.php
│       └── SearchProductRequest.php
database/
├── migrations/2026_06_11_000001_create_nutrition_products_table.php
└── seeders/NutritionProductSeeder.php   # 25 черновиков + 5 active + 5 decline
routes/
├── api.php    # публичное API
└── web.php    # админка
```

---

## Типичные сценарии мобильного приложения

### Первый запуск
```
POST /api/register  →  сохранить uuid локально
POST /api/user_open  { app: "nutrition_diary" }  + X-User-UUID
```

### Поиск продукта при добавлении в дневник
```
GET /api/nutrition/products/search?name=молоко&page=1
→ отфильтровать на клиенте status === 1
→ показать список, пользователь выбирает продукт по id
```

### Пользователь не нашёл продукт — создаёт свой
```
POST /api/nutrition/products
  X-User-UUID: <uuid>
  { name, proteins, fats, carbs, calories, description? }
→ получить id, status=0
→ сообщить пользователю «на модерации» или использовать локально до одобрения
```

---

## Деплой на сервере

```bash
cd /var/www/TrainingDairyBack
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan route:clear
php artisan config:clear
php artisan view:clear
# опционально: php artisan db:seed --class=NutritionProductSeeder
```

---

## Что логично делать дальше (ещё нет на бэке)

- Фильтр `status=Active` в публичном поиске (или отдельный эндпоинт)
- `GET /api/nutrition/products/{id}` — карточка продукта
- Приёмы пищи: `meals`, `meal_entries` (продукт + вес + дата)
- Дневная сводка КБЖУ
- Журнал тренировок (отдельный модуль)

---

## Быстрая шпаргалка curl

```bash
# Регистрация
curl -X POST http://45.146.167.233:123/api/register

# Поиск
curl "http://45.146.167.233:123/api/nutrition/products/search?name=молоко"

# Создание продукта
curl -X POST http://45.146.167.233:123/api/nutrition/products \
  -H "Content-Type: application/json" \
  -H "X-User-UUID: YOUR-UUID-HERE" \
  -d '{"name":"Тест","proteins":10,"fats":5,"carbs":20,"calories":150}'
```
