<?php

namespace App\Http\Controllers\Api\MyCar;

use App\Http\Controllers\Api\Donation\HandlesDonationErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\MyCar\CreateMyCarPaymentRequest;
use App\Models\User;
use App\Services\MyCarPaymentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CreateMyCarPaymentController extends Controller
{
    use HandlesDonationErrors;

    public function __invoke(CreateMyCarPaymentRequest $request, MyCarPaymentService $myCarPaymentService): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! User::query()->where('uuid', $validated['uuid'])->exists()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $payment = ($validated['payment_method'] ?? null) === 'sbp'
                ? $myCarPaymentService->createSbpPayment($validated['uuid'], (int) $validated['tier'])
                : $myCarPaymentService->createPayment($validated['uuid'], (int) $validated['tier']);

            return response()->json($payment, 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->handleDonationException($e, 'create My Car payment failed');
        }
    }
}
