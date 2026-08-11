# Модульная архитектура бэкенда

Бэкенд обслуживает несколько независимых мобильных приложений (My Car,
TripSplit, Nutrition Journal, Training Diary, ...). Чтобы логика каждого
приложения не расползалась по общему коду, весь бэкенд разделён на:

- **Ядро** (`app/`) — общая для всех приложений логика: пользователи,
  учёт заходов и фидбека, универсальный донат-флоу (отключение рекламы
  через ЮKassa).
- **Модули** (`Modules/<App>/`) — вся специфичная для конкретного
  приложения логика: контроллеры, запросы, модели, сервисы, миграции,
  роуты, конфиг.

## Правило

> Если у приложения появляется собственная логика на бэке (не просто
> заходы/фидбек/донаты) — она едет в `Modules/<App>/`, а не в `app/`.

Сейчас так организованы:

- `Modules/MyCar` — платные подписки My Car.
- `Modules/TripSplit` — платные подсчёты, баланс, расчёт долгов, PDF-отчёты.
- `Modules/Nutrition` — справочник продуктов, поиск, модерация.

У Training Diary и MedReminder своей бэкенд-логики пока нет — как только
она появится, для неё заводится `Modules/TrainingDiary` /
`Modules/MedReminder` по той же схеме.

## Структура модуля

```
Modules/<App>/
├── Http/
│   ├── Controllers/Api/     — контроллеры API
│   └── Requests/            — form request'ы
├── Models/                  — Eloquent-модели модуля
├── Services/                — бизнес-логика модуля
├── Database/
│   ├── Migrations/          — миграции таблиц модуля
│   └── Seeders/             — сидеры (если есть)
├── Providers/<App>ServiceProvider.php — единственная точка подключения
├── routes/api.php           — собственные роуты модуля
├── config/<app>.php         — конфиг модуля
├── resources/views/         — блейд-шаблоны модуля (если есть)
└── Tests/Feature|Unit/      — тесты модуля
```

`<App>ServiceProvider` регистрирует роуты (`Route::middleware('api')->
prefix('api')->group(...)`), миграции (`loadMigrationsFrom`), конфиг
(`mergeConfigFrom`) и, если нужно, вьюхи (`loadViewsFrom`). Он
подключается один раз — в `bootstrap/providers.php`.

## Как удалить модуль целиком

1. Удалить папку `Modules/<App>/`.
2. Убрать одну строчку регистрации провайдера в `bootstrap/providers.php`.
3. (Опционально) удалить таблицы модуля отдельной миграцией `down()`.

Больше нигде код модуля не упоминается — ни в `routes/api.php`, ни в
`routes/web.php`, ни в общих сервисах. Это обеспечивается тем, что core
никогда не импортирует классы модуля напрямую.

## Как модуль встраивается в общие платежи, не будучи известным ядру

Донаты (`DonationPayment`) — общая таблица-леджер, которой пользуются и
core (ad-free), и платные модули (My Car, TripSplit), различаясь по
колонке `app`. Раньше `YooKassaWebhookController` и
`DonationPaymentService` напрямую импортировали
`MyCarPaymentService::isMyCarPayment()` /
`TripSplitPaymentService::isTripSplitPayment()` — то есть ядро знало о
модулях, и удаление модуля сломало бы вебхук.

Теперь это устроено через контракт:

- `App\Contracts\PaymentAppHandler` — интерфейс с `supports(DonationPayment)`
  и `handleWebhook(array $payload)`.
- `App\Services\Payment\PaymentHandlerRegistry` — резолвит все сервисы,
  затегированные как `payment.app.handlers`, и находит подходящий под
  конкретный платёж.
- Каждый платёжный модуль в своём `register()` делает
  `$this->app->tag(XxxPaymentService::class, 'payment.app.handlers');`.

Ядро работает только с интерфейсом и реестром — ни одного `use
Modules\...` в `app/`.

Аналогично устроены Telegram-лейблы приложений
(`config('mobile_apps.apps')`) — модуль добавляет свою запись через
`App\Services\MobileAppRegistrar::register()` в своём провайдере, а не
редактирует общий массив в `MobileAppPresenter`.

## Добавление нового модуля

1. Создать `Modules/<App>/` с подпапками из структуры выше.
2. Namespace классов — `Modules\<App>\...`.
3. Написать `Modules\<App>\Providers\<App>ServiceProvider` (см.
   `Modules/Nutrition/Providers/NutritionServiceProvider.php` как
   образец без платежей, `Modules/MyCar/Providers/MyCarServiceProvider.php`
   — с платежами).
4. Зарегистрировать провайдер в `bootstrap/providers.php`.
5. Если у модуля есть платный тариф — реализовать
   `App\Contracts\PaymentAppHandler` в платёжном сервисе модуля и
   затегировать его как `payment.app.handlers`.
