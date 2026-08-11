# Деплой: очередь сервисных уведомлений (Telegram)

`ServiceNotificationJob` (`app/Jobs/ServiceNotificationJob.php`) отправляет
уведомления в Telegram (донаты, оплаты MyCar/TripSplit, фидбек) в фоне —
`TelegramNotificationService::sendMessage()` только кладёт сообщение в
очередь (`service-notifications`, драйвер `database`, таблица `jobs`) и
сразу возвращает управление, не дожидаясь ответа от Telegram.

- **Ретраи:** 5 попыток, интервал 15 минут между ними (`$tries`/`$backoff`
  в `ServiceNotificationJob`). После 5-й неудачной попытки джоба падает в
  `failed_jobs`.
- **Таймаут запроса к Telegram:** 10 секунд на попытку
  (`TelegramNotificationService::deliver()`), чтобы зависший воркер не
  держал остальные джобы в очереди.

## Разовая настройка на сервере

Нужен постоянно работающий воркер очереди — без него сообщения просто
копятся в таблице `jobs` и никуда не уходят.

```bash
cp deploy/systemd/trainingdairyback-queue-worker.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now trainingdairyback-queue-worker
```

Проверить:

```bash
systemctl status trainingdairyback-queue-worker
journalctl -u trainingdairyback-queue-worker -f
```

## После каждого деплоя (`git pull`)

Воркер держит код в памяти на всё время работы процесса — после пула
код в фоне не подхватится сам:

```bash
systemctl restart trainingdairyback-queue-worker
```

(Как и `systemctl restart php8.4-fpm` для самого API — это отдельный процесс.)
