<?php

namespace App\Services;

use App\Jobs\ServiceNotificationJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    private string $apiUrl;

    private int $chatId;

    public function __construct(
        private readonly MobileAppPresenter $mobileAppPresenter,
    ) {
        $this->apiUrl = config('services.telegram.api_url');
        $this->chatId = config('services.telegram.chat_id');
    }

    /**
     * Отправить уведомление о новом отзыве
     */
    public function sendFeedbackNotification(string $app, string $text): bool
    {
        $message = $this->mobileAppPresenter->formatLabel($app).': '.$text;

        return $this->sendMessage($message);
    }

    public function sendDonationPaymentNotification(
        int $amount,
        int $months,
        string $userUuid,
        ?string $paymentMethodType = null,
        ?string $yookassaPaymentId = null,
        ?string $adFreeUntil = null,
        ?string $app = null,
    ): bool {
        $method = $paymentMethodType ?: 'не указан';
        $message = "Оплата подписки: {$amount} ₽, {$months} мес.\n"
            ."Пользователь: {$userUuid}\n"
            ."Способ: {$method}";

        if ($app !== null && $app !== '') {
            $message .= "\nПриложение: {$app}";
        }

        if ($yookassaPaymentId !== null && $yookassaPaymentId !== '') {
            $message .= "\nЮKassa: {$yookassaPaymentId}";
        }

        if ($adFreeUntil !== null && $adFreeUntil !== '') {
            $message .= "\nБез рекламы до: {$adFreeUntil}";
        }

        return $this->sendMessage($message);
    }

    /**
     * Поставить сообщение в очередь на отправку в Telegram.
     *
     * Публичный, чтобы модули (MyCar, TripSplit, ...) могли отправлять свои
     * собственные уведомления, не добавляя module-specific методы сюда, в core.
     *
     * Возвращает true сразу после постановки в очередь (ServiceNotificationJob) —
     * не значит, что сообщение уже доставлено в Telegram. Саму отправку и
     * ретраи при сбое делает воркер очереди, см. ServiceNotificationJob::deliver().
     */
    public function sendMessage(string $message): bool
    {
        if (empty($this->apiUrl) || empty($this->chatId)) {
            Log::warning('Telegram configuration is missing');

            return false;
        }

        ServiceNotificationJob::dispatch($message);

        return true;
    }

    /**
     * Синхронно доставить сообщение в Telegram. Вызывается только из
     * ServiceNotificationJob (воркером очереди) — не вызывать напрямую из
     * контроллеров/сервисов, для этого есть sendMessage().
     */
    public function deliver(string $message): bool
    {
        try {
            $response = Http::timeout(10)->post($this->apiUrl, [
                'chat_id' => $this->chatId,
                'text' => $message,
            ]);

            if (! $response->successful()) {
                Log::error('Telegram API error: '.$response->body());

                return false;
            }

            try {
                // Михаил с аватаркой гусь тоже в деле!
                Http::timeout(10)->post($this->apiUrl, [
                    'chat_id' => 596684076,
                    'text' => $message,
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Telegram secondary send failed: '.$exception->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Error sending Telegram message: '.$e->getMessage());

            return false;
        }
    }
}
