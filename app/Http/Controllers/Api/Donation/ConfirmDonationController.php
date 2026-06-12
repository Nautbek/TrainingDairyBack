<?php

namespace App\Http\Controllers\Api\Donation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donation\ConfirmDonationRequest;
use App\Models\User;
use App\Services\DonationPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ConfirmDonationController extends Controller
{
    public function __invoke(ConfirmDonationRequest $request, DonationPaymentService $donationPaymentService): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! User::query()->where('uuid', $validated['uuid'])->exists()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $payment = $donationPaymentService->createPaymentWithToken(
                $validated['uuid'],
                (int) $validated['tier'],
                $validated['payment_token'],
            );

            return response()->json($payment, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error confirming donation payment: '.$e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
