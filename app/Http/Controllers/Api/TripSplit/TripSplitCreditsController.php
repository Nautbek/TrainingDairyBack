<?php

namespace App\Http\Controllers\Api\TripSplit;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripSplit\UserUuidRequest;
use App\Models\User;
use App\Services\TripSplitCreditsService;
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
