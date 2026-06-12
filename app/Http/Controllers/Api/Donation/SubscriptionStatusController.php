<?php

namespace App\Http\Controllers\Api\Donation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donation\UserUuidRequest;
use App\Models\User;
use App\Services\AdFreeSubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionStatusController extends Controller
{
    public function __invoke(UserUuidRequest $request, AdFreeSubscriptionService $adFreeSubscriptionService): JsonResponse
    {
        $user = User::query()->where('uuid', $request->validated('uuid'))->first();

        if ($user === null) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'is_ad_free' => $adFreeSubscriptionService->isAdFree($user),
            'ad_free_until' => $user->ad_free_until?->toIso8601String(),
        ]);
    }
}
