<?php

namespace App\Services;

use App\Exceptions\YooKassaApiCallException;
use App\Models\DonationPayment;
use App\Services\YooKassa\YooKassaRequestLogger;
use YooKassa\Client;
use YooKassa\Common\Exceptions\ApiException;
use YooKassa\Model\CurrencyCode;
use YooKassa\Model\Payment\ConfirmationType;
use YooKassa\Model\Payment\PaymentInterface;
use YooKassa\Request\Payments\CreatePaymentResponse;

class YooKassaService
{
    private const PAYMENTS_PATH = '/payments';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client;
        $this->client->setAuth(
            (string) config('services.yookassa.shop_id'),
            (string) config('services.yookassa.secret_key'),
        );
    }

    public function createDonationPayment(DonationPayment $payment, string $idempotenceKey): CreatePaymentResponse
    {
        $payload = [
            'amount' => [
                'value' => number_format($payment->amount, 2, '.', ''),
                'currency' => CurrencyCode::RUB,
            ],
            'capture' => true,
            'confirmation' => [
                'type' => ConfirmationType::REDIRECT,
                'return_url' => (string) config('services.yookassa.return_url'),
            ],
            'description' => "Поддержка приложения — {$payment->months} мес. без рекламы",
            'metadata' => $this->donationMetadata($payment),
        ];

        return $this->createPayment($payload, $idempotenceKey);
    }

    public function createDonationPaymentWithToken(
        DonationPayment $payment,
        string $paymentToken,
        string $idempotenceKey,
    ): CreatePaymentResponse {
        $payload = [
            'amount' => [
                'value' => number_format($payment->amount, 2, '.', ''),
                'currency' => CurrencyCode::RUB,
            ],
            'capture' => true,
            'payment_token' => $paymentToken,
            'description' => "Поддержка приложения — {$payment->months} мес. без рекламы",
            'metadata' => $this->donationMetadata($payment),
        ];

        return $this->createPayment($payload, $idempotenceKey);
    }

    public function createDonationSbpPayment(DonationPayment $payment, string $idempotenceKey): CreatePaymentResponse
    {
        $payload = [
            'amount' => [
                'value' => number_format($payment->amount, 2, '.', ''),
                'currency' => CurrencyCode::RUB,
            ],
            'capture' => true,
            'payment_method_data' => [
                'type' => 'sbp',
            ],
            'confirmation' => [
                'type' => ConfirmationType::REDIRECT,
                'return_url' => (string) config('services.yookassa.return_url'),
            ],
            'description' => "Поддержка приложения — {$payment->months} мес. без рекламы",
            'metadata' => $this->donationMetadata($payment),
        ];

        return $this->createPayment($payload, $idempotenceKey);
    }

    public function createTripSplitPayment(DonationPayment $payment, string $idempotenceKey): CreatePaymentResponse
    {
        $payload = [
            'amount' => [
                'value' => number_format($payment->amount, 2, '.', ''),
                'currency' => CurrencyCode::RUB,
            ],
            'capture' => true,
            'confirmation' => [
                'type' => ConfirmationType::REDIRECT,
                'return_url' => (string) config('services.yookassa.return_url'),
            ],
            'description' => "TripSplit — {$payment->months} подсчёт(ов) итогов",
            'metadata' => $this->tripSplitMetadata($payment),
        ];

        return $this->createPayment($payload, $idempotenceKey);
    }

    public function createTripSplitPaymentWithToken(
        DonationPayment $payment,
        string $paymentToken,
        string $idempotenceKey,
    ): CreatePaymentResponse {
        $payload = [
            'amount' => [
                'value' => number_format($payment->amount, 2, '.', ''),
                'currency' => CurrencyCode::RUB,
            ],
            'capture' => true,
            'payment_token' => $paymentToken,
            'description' => "TripSplit — {$payment->months} подсчёт(ов) итогов",
            'metadata' => $this->tripSplitMetadata($payment),
        ];

        return $this->createPayment($payload, $idempotenceKey);
    }

    public function createTripSplitSbpPayment(DonationPayment $payment, string $idempotenceKey): CreatePaymentResponse
    {
        $payload = [
            'amount' => [
                'value' => number_format($payment->amount, 2, '.', ''),
                'currency' => CurrencyCode::RUB,
            ],
            'capture' => true,
            'payment_method_data' => [
                'type' => 'sbp',
            ],
            'confirmation' => [
                'type' => ConfirmationType::REDIRECT,
                'return_url' => (string) config('services.yookassa.return_url'),
            ],
            'description' => "TripSplit — {$payment->months} подсчёт(ов) итогов",
            'metadata' => $this->tripSplitMetadata($payment),
        ];

        return $this->createPayment($payload, $idempotenceKey);
    }

    public function getPayment(string $yookassaPaymentId): ?PaymentInterface
    {
        $path = self::PAYMENTS_PATH.'/'.$yookassaPaymentId;

        try {
            return $this->client->getPaymentInfo($yookassaPaymentId);
        } catch (ApiException $e) {
            throw new YooKassaApiCallException(
                YooKassaRequestLogger::buildRequestContext('GET', $path),
                $e,
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function donationMetadata(DonationPayment $payment): array
    {
        $metadata = [
            'donation_payment_uuid' => $payment->uuid,
            'user_uuid' => $payment->user_uuid,
            'months' => (string) $payment->months,
        ];

        if ($payment->app !== null && $payment->app !== '') {
            $metadata['app'] = $payment->app;
        }

        return $metadata;
    }

    /**
     * @return array<string, string>
     */
    private function tripSplitMetadata(DonationPayment $payment): array
    {
        return [
            'donation_payment_uuid' => $payment->uuid,
            'user_uuid' => $payment->user_uuid,
            'credits' => (string) $payment->months,
            'app' => (string) config('tripsplit.app'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createPayment(array $payload, string $idempotenceKey): CreatePaymentResponse
    {
        $headers = [
            'Idempotence-Key' => $idempotenceKey,
            'Content-Type' => 'application/json',
        ];

        try {
            return $this->client->createPayment($payload, $idempotenceKey);
        } catch (ApiException $e) {
            throw new YooKassaApiCallException(
                YooKassaRequestLogger::buildRequestContext('POST', self::PAYMENTS_PATH, $headers, $payload),
                $e,
            );
        }
    }
}
