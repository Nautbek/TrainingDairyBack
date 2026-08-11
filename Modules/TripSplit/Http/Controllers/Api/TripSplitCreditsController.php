<?php

namespace Modules\TripSplit\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\TripSplit\Http\Requests\UserUuidRequest;
use App\Models\User;
use Modules\TripSplit\Services\TripSplitCreditsService;
use Illuminate\Http\JsonResponse;

class TripSplitCreditsController extends Controller
{
    public function __invoke(UserUuidRequest $request, TripSplitCreditsService $tripSplitCreditsService): JsonResponse
    {
        $uuid = $request->validated('uuid');
        $user = User::query()->where('uuid', $uuid)->first();

        if ($user === null) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'usage_count' => $tripSplitCreditsService->getCountForUser($user),
        ]);
    }
}
