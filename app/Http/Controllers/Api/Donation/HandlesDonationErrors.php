<?php

namespace App\Http\Controllers\Api\Donation;

use App\Exceptions\YooKassaApiCallException;
use App\Services\YooKassa\YooKassaRequestLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use YooKassa\Common\Exceptions\ApiException;

trait HandlesDonationErrors
{
    protected function handleDonationException(\Throwable $e, string $context): JsonResponse
    {
        if ($e instanceof YooKassaApiCallException) {
            $apiException = $e->getApiException();
            $httpCode = $apiException->getCode();
            if ($httpCode < 400 || $httpCode >= 600) {
                $httpCode = 502;
            }

            YooKassaRequestLogger::logFailedCall($context, $e->requestContext, $apiException);

            return response()->json([
                'error' => 'YooKassa error',
                'message' => config('app.debug') ? $apiException->getMessage() : 'Payment gateway error',
            ], $httpCode);
        }

        if ($e instanceof ApiException) {
            $httpCode = $e->getCode();
            if ($httpCode < 400 || $httpCode >= 600) {
                $httpCode = 502;
            }

            YooKassaRequestLogger::logFailedCall($context, [], $e);

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
