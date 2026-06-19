<?php

namespace App\Http\Controllers\Api\TripSplit;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripSplit\UserUuidRequest;
use App\Models\DonationPayment;
use App\Models\User;
use App\Services\TripSplitCreditsService;
use App\Services\TripSplitPaymentService;
use Illuminate\Http\JsonResponse;

class TripSplitPaymentStatusController extends Controller
{
    public function __invoke(
        string $paymentUuid,
        UserUuidRequest $request,
        TripSplitPaymentService $tripSplitPaymentService,
        TripSplitCreditsService $tripSplitCreditsService,
    ): JsonResponse {
        $payment = DonationPayment::query()
            ->where('uuid', $paymentUuid)
            ->where('user_uuid', $request->validated('uuid'))
            ->where('app', (string) config('tripsplit.app'))
            ->first();

        if ($payment === null) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        $payment = $tripSplitPaymentService->syncPayment($payment);
        $user = User::query()->where('uuid', $payment->user_uuid)->first();

        return response()->json([
            'payment_uuid' => $payment->uuid,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'credits' => $payment->months,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'usage_count' => $user !== null ? $tripSplitCreditsService->getCountForUser($user) : 0,
        ]);
    }
}
