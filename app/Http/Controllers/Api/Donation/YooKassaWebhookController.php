<?php

namespace App\Http\Controllers\Api\Donation;

use App\Http\Controllers\Controller;
use App\Services\DonationPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YooKassaWebhookController extends Controller
{
    public function __invoke(Request $request, DonationPaymentService $donationPaymentService): JsonResponse
    {
        try {
            $donationPaymentService->handleWebhook($request->all());

            return response()->json(['status' => 'Ok']);
        } catch (\Exception $e) {
            Log::error('YooKassa webhook error: '.$e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
