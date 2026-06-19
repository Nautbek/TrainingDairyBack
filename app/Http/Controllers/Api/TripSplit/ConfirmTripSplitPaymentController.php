<?php

namespace App\Http\Controllers\Api\TripSplit;

use App\Http\Controllers\Api\Donation\HandlesDonationErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\TripSplit\ConfirmTripSplitPaymentRequest;
use App\Models\User;
use App\Services\TripSplitPaymentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class ConfirmTripSplitPaymentController extends Controller
{
    use HandlesDonationErrors;

    public function __invoke(ConfirmTripSplitPaymentRequest $request, TripSplitPaymentService $tripSplitPaymentService): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! User::query()->where('uuid', $validated['uuid'])->exists()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $payment = $tripSplitPaymentService->createPaymentWithToken(
                $validated['uuid'],
                (int) $validated['tier'],
                $validated['payment_token'],
            );

            return response()->json($payment, 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->handleDonationException($e, 'confirm TripSplit payment failed');
        }
    }
}
