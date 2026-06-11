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
                //    return true;
            }

            try {
                $response = Http::post($this->apiUrl, [
                    'chat_id' => 8365289758,
                    'text' => $message,
                ]);

                $response = Http::post($this->apiUrl, [
                    'chat_id' => 8365289758,
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
