<?php

namespace App\Services;

use App\Models\DonationPayment;
use YooKassa\Client;
use YooKassa\Model\ConfirmationType;
use YooKassa\Model\CurrencyCode;
use YooKassa\Model\Payment\PaymentInterface;
use YooKassa\Request\Payments\CreatePaymentResponse;

class YooKassaService
{
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
        return $this->client->createPayment([
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
            'metadata' => [
                'donation_payment_uuid' => $payment->uuid,
                'user_uuid' => $payment->user_uuid,
                'months' => (string) $payment->months,
            ],
        ], $idempotenceKey);
    }

    public function createDonationPaymentWithToken(
        DonationPayment $payment,
        string $paymentToken,
        string $idempotenceKey,
    ): CreatePaymentResponse {
        return $this->client->createPayment([
            'amount' => [
                'value' => number_format($payment->amount, 2, '.', ''),
                'currency' => CurrencyCode::RUB,
            ],
            'capture' => true,
            'payment_token' => $paymentToken,
            'description' => "Поддержка приложения — {$payment->months} мес. без рекламы",
            'metadata' => [
                'donation_payment_uuid' => $payment->uuid,
                'user_uuid' => $payment->user_uuid,
                'months' => (string) $payment->months,
            ],
        ], $idempotenceKey);
    }

    public function getPayment(string $yookassaPaymentId): ?PaymentInterface
    {
        return $this->client->getPaymentInfo($yookassaPaymentId);
    }
}
