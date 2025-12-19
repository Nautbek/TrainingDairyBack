<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserFeedbackRequest;
use App\Models\UserFeedback;
use App\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserFeedbackController extends Controller
{
    public function __construct(
        private readonly TelegramNotificationService $telegramService
    ) {
    }

    /**
     * Сохранить отзыв пользователя
     * Аналог /api/user_feedback из Go проекта
     *
     * @param UserFeedbackRequest $request
     * @return JsonResponse
     */
    public function __invoke(UserFeedbackRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $visitIp = $request->ip();
            
            UserFeedback::saveFeedback($visitIp, $validated['app'], $validated['text']);

            // Отправка уведомления в Telegram
            $this->telegramService->sendFeedbackNotification($validated['app'], $validated['text']);

            return response()->json([
                'status' => 'Ok'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error saving feedback: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Internal Server Error'
            ], 500);
        }
    }
}
