<?php

namespace App\Http\Controllers\Api\Donation;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use YooKassa\Common\Exceptions\ApiException;

trait HandlesDonationErrors
{
    protected function handleDonationException(\Throwable $e, string $context): JsonResponse
    {
        if ($e instanceof ApiException) {
            $httpCode = $e->getCode();
            if ($httpCode < 400 || $httpCode >= 600) {
                $httpCode = 502;
            }

            Log::error($context, [
                'yookassa_message' => $e->getMessage(),
                'yookassa_http_code' => $e->getCode(),
                'yookassa_response' => $e->getResponseBody(),
            ]);

            return response()->json([
                'error' => 'YooKassa error',
                'message' => config('app.debug') ? $e->getMessage() : 'Payment gateway error',
            ], $httpCode);
        }

        Log::error($context.': '.$e->getMessage(), [
            'exception' => $e::class,
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'error' => 'Internal Server Error',
            'message' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}
