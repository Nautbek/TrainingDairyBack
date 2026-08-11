<?php

namespace App\Http\Controllers\Api\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\CreateFeedbackMessageRequest;
use App\Models\FeedbackMessage;
use App\Models\FeedbackThread;
use App\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedbackMessageController extends Controller
{
    public function __construct(
        private readonly TelegramNotificationService $telegramService,
    ) {
    }

    /**
     * Добавить сообщение пользователя в существующий тред.
     * 409, если тред уже закрыт — писать в закрытый тред нельзя.
     */
    public function store(CreateFeedbackMessageRequest $request, int $threadId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $userId = $this->resolveUserId($validated['uuid']);
            $thread = FeedbackThread::query()->find($threadId);

            if ($thread === null) {
                return response()->json(['error' => 'not_found'], 404);
            }

            if ($userId === null || $thread->user_id !== $userId) {
                return response()->json(['error' => 'forbidden'], 403);
            }

            if ($thread->isClosed()) {
                return response()->json([
                    'error' => 'thread_closed',
                    'message' => 'Обращение уже закрыто',
                ], 409);
            }

            $message = DB::transaction(function () use ($thread, $validated) {
                $message = FeedbackMessage::query()->create([
                    'thread_id' => $thread->id,
                    'sender' => FeedbackMessage::SENDER_USER,
                    'body' => $validated['text'],
                ]);

                $thread->touch();

                return $message;
            });

            $this->telegramService->sendMessage(
                "Новое сообщение в обращении №{$thread->id} ({$thread->app}):\n{$validated['text']}"
            );

            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'sender' => $message->sender,
                    'body' => $message->body,
                    'created_at' => $message->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating feedback message: '.$e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    private function resolveUserId(string $uuid): ?int
    {
        $user = DB::table('users')->where('uuid', $uuid)->first();

        return $user?->id;
    }
}
