<?php

namespace Modules\TrainingDiary\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\TrainingDiary\Http\Requests\StoreExerciseEntryRequest;
use Modules\TrainingDiary\Models\ApproachEntry;
use Modules\TrainingDiary\Models\ExerciseEntry;

/**
 * POST /api/training-diary/exercises — an exercise log entry together with
 * whichever approaches ("подходы") had been recorded for it by the time the
 * device's hourly sync worker got to it. That worker is the only caller:
 * once an hour it picks its single oldest not-yet-synced exercise and sends
 * it here with all of its approaches in one request. This endpoint always
 * creates a new row; the client is responsible for only calling it once per
 * not-yet-synced exercise (tracked locally by the uuid this returns).
 */
class ExerciseEntryStoreController extends Controller
{
    public function __invoke(StoreExerciseEntryRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! User::query()->where('uuid', $validated['uuid'])->exists()) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 401);
            }

            $exercise = DB::transaction(function () use ($validated) {
                do {
                    $exerciseUuid = (string) Str::uuid();
                } while (ExerciseEntry::query()->where('uuid', $exerciseUuid)->exists());

                $exercise = ExerciseEntry::query()->create([
                    'uuid' => $exerciseUuid,
                    'user_uuid' => $validated['uuid'],
                    'title' => $validated['title'],
                    'logged_at' => $validated['logged_at'],
                    'client_id' => $validated['client_id'] ?? null,
                ]);

                foreach ($validated['approaches'] ?? [] as $approach) {
                    do {
                        $approachUuid = (string) Str::uuid();
                    } while (ApproachEntry::query()->where('uuid', $approachUuid)->exists());

                    $exercise->approaches()->create([
                        'uuid' => $approachUuid,
                        'weight' => $approach['weight'],
                        'repeat_count' => $approach['repeat_count'],
                        'comment' => $approach['comment'] ?? null,
                        'client_id' => $approach['client_id'] ?? null,
                    ]);
                }

                return $exercise;
            });

            return response()->json([
                'id' => $exercise->id,
                'uuid' => $exercise->uuid,
                'approaches' => $exercise->approaches->map(fn (ApproachEntry $approach) => [
                    'client_id' => $approach->client_id,
                    'uuid' => $approach->uuid,
                ]),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error saving training diary exercise: '.$e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
            ], 500);
        }
    }
}
