<?php

namespace Modules\TripSplit\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\TripSplit\Http\Requests\SettleTripRequest;
use App\Models\User;
use Modules\TripSplit\Exceptions\InsufficientCreditsException;
use Modules\TripSplit\Services\TripSplitSettlementService;
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
