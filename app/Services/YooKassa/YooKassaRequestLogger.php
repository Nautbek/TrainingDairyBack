<?php

namespace App\Services\YooKassa;

use Illuminate\Support\Facades\Log;
use YooKassa\Common\Exceptions\ApiException;

class YooKassaRequestLogger
{
    private const API_BASE_URL = 'https://api.yookassa.ru/v3';

    /**
     * @param  array<string, mixed>  $requestContext
     */
    public static function logFailedCall(string $context, array $requestContext, ApiException $exception): void
    {
        Log::error($context, [
            'yookassa_request' => $requestContext,
            'yookassa_response' => [
                'http_code' => $exception->getCode(),
                'headers' => $exception->getResponseHeaders(),
                'body' => $exception->getResponseBody(),
                'message' => $exception->getMessage(),
            ],
        ]);
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    public static function buildRequestContext(
        string $method,
        string $path,
        array $headers = [],
        ?array $body = null,
    ): array {
        return [
            'method' => $method,
            'url' => self::API_BASE_URL.$path,
            'auth' => self::authContext(),
            'headers' => $headers,
            'body' => $body === null ? null : self::maskBody($body),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function authContext(): array
    {
        $shopId = (string) config('services.yookassa.shop_id');
        $secretKey = (string) config('services.yookassa.secret_key');

        return [
            'shop_id' => $shopId,
            'secret_key' => self::maskValue($secretKey),
            'authorization' => 'Basic '.self::maskValue(base64_encode($shopId.':'.$secretKey)),
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private static function maskBody(array $body): array
    {
        if (isset($body['payment_token']) && is_string($body['payment_token'])) {
            $body['payment_token'] = self::maskToken($body['payment_token']);
        }

        return $body;
    }

    private static function maskToken(string $token): string
    {
        if ($token === '') {
            return '';
        }

        if (strlen($token) <= 12) {
            return '***';
        }

        return substr($token, 0, 8).'...'.substr($token, -4).' (len='.strlen($token).')';
    }

    private static function maskValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (strlen($value) <= 12) {
            return substr($value, 0, 4).'***';
        }

        return substr($value, 0, 8).'...'.substr($value, -4);
    }
}
