<?php

namespace Modules\TrainingDiary\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Modules\TrainingDiary\Http\Requests\IndexExerciseEntryRequest;
use Modules\TrainingDiary\Models\ApproachEntry;
use Modules\TrainingDiary\Models\ExerciseEntry;

/**
 * GET /api/training-diary/exercises — incremental pull for the "sync between
 * devices" feature (see Android sync/ExercisePullManager). The client sends
 * the server_time it stored from its previous call as `since`; we filter on
 * created_at (when the row was synced up), not logged_at (when the user says
 * the workout happened) — a backdated entry synced today must still show up
 * on an incremental pull started after "today".
 *
 * `server_time` in the response is the cursor the client should persist for
 * its next call — never the device's own clock, to avoid clock-skew gaps.
 *
 * Re-enabled behind `device_token` (see "Аккаунт по email" plan) — this route was
 * unrouted for a while because uuid alone isn't a secret (shown in-app, could leak via
 * a screenshot); DeviceToken proves this specific device actually authenticated
 * (register/login/reset-password), not just that it knows someone's uuid.
 */
class ExerciseEntryIndexController extends Controller
{
    public function __invoke(IndexExerciseEntryRequest $request): JsonResponse
    {
        $uuid = $request->string('uuid')->toString();
        $deviceToken = $request->string('device_token')->toString();
        $since = $request->string('since')->toString();

        if (! User::query()->where('uuid', $uuid)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! DeviceToken::query()->where('user_uuid', $uuid)->where('token', $deviceToken)->exists()) {
            return response()->json(['error' => 'invalid_device_token'], 401);
        }

        // Read the cutoff before running the query so nothing created while the
        // query runs is silently skipped by the response's own server_time.
        $serverTime = Carbon::now('UTC');

        $exercises = ExerciseEntry::query()
            ->with('approaches')
            ->where('user_uuid', $uuid)
            ->when(
                $since !== '',
                // Pass a DateTimeInterface, not the raw string — the query grammar then
                // formats it to match the column's stored format. Binding the ISO-8601
                // string directly compares "2026-…T10:…Z" against a stored "2026-… 10:…"
                // byte-for-byte, and ' ' sorts before 'T', so every row looks "too old".
                fn ($query) => $query->where('created_at', '>=', Carbon::parse($since)),
            )
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'server_time' => $serverTime->toIso8601ZuluString(),
            'exercises' => $exercises->map(fn (ExerciseEntry $exercise) => [
                'uuid' => $exercise->uuid,
                'title' => $exercise->title,
                'measurement_type' => $exercise->measurement_type,
                'logged_at' => $exercise->logged_at->toIso8601ZuluString(),
                'created_at' => $exercise->created_at->toIso8601ZuluString(),
                'approaches' => $exercise->approaches->map(fn (ApproachEntry $approach) => [
                    'uuid' => $approach->uuid,
                    'weight' => $approach->weight,
                    'repeat_count' => $approach->repeat_count,
                    'comment' => $approach->comment,
                    'duration_seconds' => $approach->duration_seconds,
                    'distance_meters' => $approach->distance_meters,
                ]),
            ]),
        ]);
    }
}
