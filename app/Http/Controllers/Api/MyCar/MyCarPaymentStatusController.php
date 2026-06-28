<?php

namespace App\Http\Controllers\Api\MyCar;

use App\Http\Controllers\Controller;
use App\Http\Requests\MyCar\UserUuidRequest;
use App\Models\DonationPayment;
use App\Models\User;
use App\Services\MyCarPaymentService;
use App\Services\MyCarSubscriptionService;
use Illuminate\Http\JsonResponse;

class MyCarPaymentStatusController extends Controller
{
    public function __invoke(
        string $paymentUuid,
        UserUuidRequest $request,
        MyCarPaymentService $myCarPaymentService,
        MyCarSubscriptionService $myCarSubscriptionService,
    ): JsonResponse {
        $payment = DonationPayment::query()
            ->where('uuid', $paymentUuid)
            ->where('user_uuid', $request->validated('uuid'))
            ->where('app', (string) config('mycar.app'))
            ->first();

        if ($payment === null) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        $payment = $myCarPaymentService->syncPayment($payment);
        $user = User::query()->where('uuid', $payment->user_uuid)->first();
        $premiumUntil = $user !== null ? $myCarSubscriptionService->getPremiumUntil($user) : null;

        return response()->json([
            'payment_uuid' => $payment->uuid,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'months' => $payment->months,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'premium_until' => $premiumUntil?->toIso8601String(),
        ]);
    }
}
