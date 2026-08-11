<?php

namespace Modules\TripSplit\Http\Controllers\Api;

use App\Http\Controllers\Api\Donation\HandlesDonationErrors;
use App\Http\Controllers\Controller;
use Modules\TripSplit\Http\Requests\CreateTripSplitPaymentRequest;
use App\Models\User;
use Modules\TripSplit\Services\TripSplitPaymentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CreateTripSplitPaymentController extends Controller
{
    use HandlesDonationErrors;

    public function __invoke(CreateTripSplitPaymentRequest $request, TripSplitPaymentService $tripSplitPaymentService): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! User::query()->where('uuid', $validated['uuid'])->exists()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $payment = ($validated['payment_method'] ?? null) === 'sbp'
                ? $tripSplitPaymentService->createSbpPayment($validated['uuid'], (int) $validated['tier'])
                : $tripSplitPaymentService->createPayment($validated['uuid'], (int) $validated['tier']);

            return response()->json($payment, 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->handleDonationException($e, 'create TripSplit payment failed');
        }
    }
}
