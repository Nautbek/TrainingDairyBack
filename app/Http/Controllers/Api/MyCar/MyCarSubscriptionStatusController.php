<?php

namespace App\Http\Controllers\Api\MyCar;

use App\Http\Controllers\Controller;
use App\Http\Requests\MyCar\UserUuidRequest;
use App\Models\User;
use App\Services\MyCarSubscriptionService;
use Illuminate\Http\JsonResponse;

class MyCarSubscriptionStatusController extends Controller
{
    public function __invoke(UserUuidRequest $request, MyCarSubscriptionService $myCarSubscriptionService): JsonResponse
    {
        $user = User::query()->where('uuid', $request->validated('uuid'))->first();

        if ($user === null) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'is_premium' => $myCarSubscriptionService->isPremium($user),
            'premium_until' => $myCarSubscriptionService->getPremiumUntil($user)?->toIso8601String(),
        ]);
    }
}
