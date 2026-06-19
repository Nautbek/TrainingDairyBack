<?php

namespace App\Http\Controllers\Api\TripSplit;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripSplit\SettleTripRequest;
use App\Models\User;
use App\Services\TripSplit\InsufficientCreditsException;
use App\Services\TripSplit\TripSplitSettlementService;
use Illuminate\Http\JsonResponse;

class SettleTripController extends Controller
{
    public function __invoke(SettleTripRequest $request, TripSplitSettlementService $settlementService): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->where('uuid', $validated['uuid'])->first();

        if ($user === null) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $result = $settlementService->settle($user, $validated['trip']);

            return response()->json($result);
        } catch (InsufficientCreditsException $e) {
            return response()->json([
                'error' => 'insufficient_credits',
                'usage_count' => $e->usageCount(),
            ], 402);
        }
    }
}
