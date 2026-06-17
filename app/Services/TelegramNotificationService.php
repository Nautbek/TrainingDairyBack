<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    private string $apiUrl;

    private int $chatId;

    public function __construct()
    {
        $this->apiUrl = config('services.telegram.api_url');
        $this->chatId = config('services.telegram.chat_id');
    }

    /**
     * Отправить уведомление о новом отзыве
     */
    public function sendFeedbackNotification(string $app, string $text): bool
    {
        $message = "Feedback {$app}: {$text}";

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
     * Отправить сообщение в Telegram
     */
    private function sendMessage(string $message): bool
    {
        if (empty($this->apiUrl) || empty($this->chatId)) {
            Log::warning('Telegram configuration is missing');

            return false;
        }

        try {
            $response = Http::post($this->apiUrl, [
                'chat_id' => $this->chatId,
                'text' => $message,
            ]);

            if ($response->successful()) {
                //                return true;
            }

            try {
                // Михаил с аватаркой гусь тоже в деле!
                $response = Http::post($this->apiUrl, [
                    'chat_id' => 596684076,
                    'text' => $message,
                ]);
            } catch (\Exception $exception) {

            }

            return true;
            Log::error('Telegram API error: '.$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('Error sending Telegram message: '.$e->getMessage());

            return false;
        }
    }
}
