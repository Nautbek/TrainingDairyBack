<?php

namespace App\Http\Controllers\Api\Donation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donation\UserUuidRequest;
use App\Models\DonationPayment;
use App\Services\DonationPaymentService;
use Illuminate\Http\JsonResponse;

class DonationPaymentStatusController extends Controller
{
    public function __invoke(
        string $paymentUuid,
        UserUuidRequest $request,
        DonationPaymentService $donationPaymentService,
    ): JsonResponse {
        $payment = DonationPayment::query()
            ->where('uuid', $paymentUuid)
            ->where('user_uuid', $request->validated('uuid'))
            ->first();

        if ($payment === null) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        $payment = $donationPaymentService->syncPayment($payment);

        return response()->json([
            'payment_uuid' => $payment->uuid,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'months' => $payment->months,
            'paid_at' => $payment->paid_at?->toIso8601String(),
        ]);
    }
}
