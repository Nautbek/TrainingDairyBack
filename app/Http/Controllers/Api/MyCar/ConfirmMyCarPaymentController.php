<?php

namespace App\Http\Controllers\Api\MyCar;

use App\Http\Controllers\Api\Donation\HandlesDonationErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\MyCar\ConfirmMyCarPaymentRequest;
use App\Models\User;
use App\Services\MyCarPaymentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class ConfirmMyCarPaymentController extends Controller
{
    use HandlesDonationErrors;

    public function __invoke(ConfirmMyCarPaymentRequest $request, MyCarPaymentService $myCarPaymentService): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! User::query()->where('uuid', $validated['uuid'])->exists()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $payment = $myCarPaymentService->createPaymentWithToken(
                $validated['uuid'],
                (int) $validated['tier'],
                $validated['payment_token'],
            );

            return response()->json($payment, 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->handleDonationException($e, 'confirm My Car payment failed');
        }
    }
}
