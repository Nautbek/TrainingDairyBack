<?php

namespace App\Http\Controllers\Api\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\CreateFeedbackThreadRequest;
use App\Http\Requests\Feedback\ListFeedbackThreadsRequest;
use App\Models\FeedbackMessage;
use App\Models\FeedbackThread;
use App\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedbackThreadController extends Controller
{
    public function __construct(
        private readonly TelegramNotificationService $telegramService,
    ) {
    }

    /**
     * Список тредов обращений текущего пользователя (по X-User-UUID), новые сверху.
     */
    public function index(ListFeedbackThreadsRequest $request): JsonResponse
    {
        $userId = $this->resolveUserId($request->validated()['uuid']);

        if ($userId === null) {
            return response()->json(['threads' => []]);
        }

        $threads = FeedbackThread::query()
            ->where('user_id', $userId)
            ->withCount('messages')
            ->with(['messages' => fn ($q) => $q->latest('created_at')->limit(1)])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'threads' => $threads->map(fn (FeedbackThread $thread) => $this->presentThread($thread))->all(),
        ]);
    }

    /**
     * Тред + все сообщения. 403, если тред не принадлежит пользователю с этим uuid.
     */
    public function show(ListFeedbackThreadsRequest $request, int $id): JsonResponse
    {
        $userId = $this->resolveUserId($request->validated()['uuid']);
        $thread = FeedbackThread::query()->with('messages')->find($id);

        if ($thread === null) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($userId === null || $thread->user_id !== $userId) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        return response()->json([
            'thread' => $this->presentThread($thread, includeMessages: true),
        ]);
    }

    /**
     * Создать новый тред + первое сообщение.
     */
    public function store(CreateFeedbackThreadRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $userId = $this->resolveUserId($validated['uuid']);

            if ($userId === null) {
                return response()->json(['error' => 'unknown_user'], 401);
            }

            $thread = DB::transaction(function () use ($validated, $userId, $request) {
                $thread = FeedbackThread::query()->create([
                    'user_id' => $userId,
                    'app' => $validated['app'],
                    'status' => FeedbackThread::STATUS_OPEN,
                    'visit_ip' => $request->ip(),
                ]);

                FeedbackMessage::query()->create([
                    'thread_id' => $thread->id,
                    'sender' => FeedbackMessage::SENDER_USER,
                    'body' => $validated['text'],
                ]);

                return $thread->fresh('messages');
            });

            $this->telegramService->sendMessage(
                "Новое обращение №{$thread->id} ({$validated['app']}):\n{$validated['text']}"
            );

            return response()->json([
                'thread' => $this->presentThread($thread, includeMessages: true),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating feedback thread: '.$e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    private function resolveUserId(string $uuid): ?int
    {
        $user = DB::table('users')->where('uuid', $uuid)->first();

        return $user?->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentThread(FeedbackThread $thread, bool $includeMessages = false): array
    {
        $data = [
            'id' => $thread->id,
            'app' => $thread->app,
            'status' => $thread->status,
            'created_at' => $thread->created_at?->toIso8601String(),
            'updated_at' => $thread->updated_at?->toIso8601String(),
        ];

        if ($includeMessages) {
            $data['messages'] = $thread->messages
                ->sortBy('created_at')
                ->values()
                ->map(fn (FeedbackMessage $m) => [
                    'id' => $m->id,
                    'sender' => $m->sender,
                    'body' => $m->body,
                    'created_at' => $m->created_at?->toIso8601String(),
                ])->all();
        } else {
            $last = $thread->messages->first();
            $data['last_message'] = $last === null ? null : [
                'sender' => $last->sender,
                'body' => $last->body,
                'created_at' => $last->created_at?->toIso8601String(),
            ];
        }

        return $data;
    }
}
