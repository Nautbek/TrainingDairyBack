<?php

namespace Modules\MyCar\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\MyCar\Http\Requests\UserUuidRequest;
use App\Models\User;
use Modules\MyCar\Services\MyCarSubscriptionService;
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
