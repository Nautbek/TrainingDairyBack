<?php

namespace App\Http\Controllers\Api\Donation;

use App\Http\Controllers\Controller;
use App\Models\DonationPayment;
use App\Services\DonationPaymentService;
use App\Services\MyCarPaymentService;
use App\Services\TripSplitPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YooKassaWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        DonationPaymentService $donationPaymentService,
        TripSplitPaymentService $tripSplitPaymentService,
        MyCarPaymentService $myCarPaymentService,
    ): JsonResponse {
        Log::info('YooKassa webhook received', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        try {
            $payload = $request->all();
            $object = $payload['object'] ?? null;
            $payment = is_array($object)
                ? DonationPayment::resolveFromYooKassaObject($object)
                : null;

            if (MyCarPaymentService::isMyCarPayment($payment)) {
                $myCarPaymentService->handleWebhook($payload);
            } elseif (TripSplitPaymentService::isTripSplitPayment($payment)) {
                $tripSplitPaymentService->handleWebhook($payload);
            } else {
                $donationPaymentService->handleWebhook($payload);
            }

            return response()->json(['status' => 'Ok']);
        } catch (\Exception $e) {
            Log::error('YooKassa webhook error: '.$e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
