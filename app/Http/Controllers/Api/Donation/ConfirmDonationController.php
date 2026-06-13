<?php

namespace App\Http\Controllers\Api\Donation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donation\ConfirmDonationRequest;
use App\Models\User;
use App\Services\DonationPaymentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class ConfirmDonationController extends Controller
{
    use HandlesDonationErrors;

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
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->handleDonationException($e, 'confirm donation payment failed');
        }
    }
}
