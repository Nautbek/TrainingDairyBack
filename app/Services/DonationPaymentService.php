<?php

namespace App\Services;

use App\Enums\Donation\PaymentStatus;
use App\Models\DonationPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Payment\Confirmation\ConfirmationMobileApplication;
use YooKassa\Model\Payment\Confirmation\ConfirmationRedirect;
use YooKassa\Model\Payment\PaymentStatus as YooKassaPaymentStatus;

class DonationPaymentService
{
    public function __construct(
        private readonly YooKassaService $yooKassaService,
        private readonly AdFreeSubscriptionService $adFreeSubscriptionService,
    ) {}

    /**
     * @return array{
     *     payment_uuid: string,
     *     status: string,
     *     amount: int,
     *     months: int,
     *     confirmation_url?: string|null,
     *     confirmation_type?: string|null,
     *     payment_method_type?: string|null,
     *     ad_free_until?: string|null
     * }
     */
    public function createPaymentWithToken(string $userUuid, int $tierAmount, string $paymentToken): array
    {
        $tier = config("donations.tiers.{$tierAmount}");

        if ($tier === null) {
            throw new \InvalidArgumentException('Invalid donation tier');
        }

        do {
            $paymentUuid = (string) Str::uuid();
        } while (DonationPayment::query()->where('uuid', $paymentUuid)->exists());

        $payment = DonationPayment::query()->create([
            'uuid' => $paymentUuid,
            'user_uuid' => $userUuid,
            'amount' => $tier['amount'],
            'months' => $tier['months'],
            'status' => PaymentStatus::Pending,
        ]);

        $yooKassaPayment = $this->yooKassaService->createDonationPaymentWithToken(
            $payment,
            $paymentToken,
            $paymentUuid,
        );

        $payment->update([
            'yookassa_payment_id' => $yooKassaPayment->getId(),
        ]);

        if ($yooKassaPayment->getStatus() === YooKassaPaymentStatus::SUCCEEDED) {
            $this->applySucceeded($payment->fresh());
            $user = User::query()->where('uuid', $userUuid)->first();

            return $this->buildTokenPaymentResponse($payment->fresh(), $user);
        }

        return $this->buildTokenPaymentResponse($payment->fresh(), null, $yooKassaPayment);
    }

    /**
     * @return array{payment_uuid: string, confirmation_url: string, amount: int, months: int, status: string}
     */
    public function createPayment(string $userUuid, int $tierAmount): array
    {
        $tier = config("donations.tiers.{$tierAmount}");

        if ($tier === null) {
            throw new \InvalidArgumentException('Invalid donation tier');
        }

        do {
            $paymentUuid = (string) Str::uuid();
        } while (DonationPayment::query()->where('uuid', $paymentUuid)->exists());

        $payment = DonationPayment::query()->create([
            'uuid' => $paymentUuid,
            'user_uuid' => $userUuid,
            'amount' => $tier['amount'],
            'months' => $tier['months'],
            'status' => PaymentStatus::Pending,
        ]);

        $yooKassaPayment = $this->yooKassaService->createDonationPayment($payment, $paymentUuid);

        $payment->update([
            'yookassa_payment_id' => $yooKassaPayment->getId(),
        ]);

        return $this->buildRedirectPaymentResponse($payment, $yooKassaPayment);
    }

    /**
     * СБП через redirect (без payment_token) — у части магазинов токен SDK для СБП не принимается.
     *
     * @return array{payment_uuid: string, confirmation_url: string, amount: int, months: int, status: string, confirmation_type?: string|null, payment_method_type?: string}
     */
    public function createSbpPayment(string $userUuid, int $tierAmount): array
    {
        $tier = config("donations.tiers.{$tierAmount}");

        if ($tier === null) {
            throw new \InvalidArgumentException('Invalid donation tier');
        }

        do {
            $paymentUuid = (string) Str::uuid();
        } while (DonationPayment::query()->where('uuid', $paymentUuid)->exists());

        $payment = DonationPayment::query()->create([
            'uuid' => $paymentUuid,
            'user_uuid' => $userUuid,
            'amount' => $tier['amount'],
            'months' => $tier['months'],
            'status' => PaymentStatus::Pending,
        ]);

        $yooKassaPayment = $this->yooKassaService->createDonationSbpPayment($payment, $paymentUuid);

        $payment->update([
            'yookassa_payment_id' => $yooKassaPayment->getId(),
        ]);

        return $this->buildRedirectPaymentResponse($payment, $yooKassaPayment, 'sbp');
    }

    public function handleWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $object = $payload['object'] ?? null;

        if (! is_array($object)) {
            return;
        }

        if ($event === NotificationEventType::PAYMENT_SUCCEEDED) {
            $this->markSucceeded($object);

            return;
        }

        if ($event === NotificationEventType::PAYMENT_CANCELED) {
            $this->markCanceled($object);
        }
    }

    public function syncPayment(DonationPayment $payment): DonationPayment
    {
        if ($payment->yookassa_payment_id === null) {
            return $payment;
        }

        $yooKassaPayment = $this->yooKassaService->getPayment($payment->yookassa_payment_id);

        if ($yooKassaPayment === null) {
            return $payment;
        }

        if ($yooKassaPayment->getStatus() === YooKassaPaymentStatus::SUCCEEDED) {
            $this->applySucceeded($payment);
        }

        if ($yooKassaPayment->getStatus() === YooKassaPaymentStatus::CANCELED) {
            $payment->update(['status' => PaymentStatus::Canceled]);
        }

        return $payment->fresh();
    }

    /**
     * @param  array<string, mixed>  $paymentObject
     */
    private function markSucceeded(array $paymentObject): void
    {
        $payment = $this->resolvePayment($paymentObject);

        if ($payment === null) {
            return;
        }

        $this->applySucceeded($payment);
    }

    /**
     * @param  array<string, mixed>  $paymentObject
     */
    private function markCanceled(array $paymentObject): void
    {
        $payment = $this->resolvePayment($paymentObject);

        if ($payment === null || $payment->status === PaymentStatus::Succeeded) {
            return;
        }

        $payment->update(['status' => PaymentStatus::Canceled]);
    }

    /**
     * @param  array<string, mixed>  $paymentObject
     */
    private function resolvePayment(array $paymentObject): ?DonationPayment
    {
        $yookassaPaymentId = $paymentObject['id'] ?? null;
        $metadata = $paymentObject['metadata'] ?? [];
        $donationPaymentUuid = is_array($metadata) ? ($metadata['donation_payment_uuid'] ?? null) : null;

        if (is_string($yookassaPaymentId)) {
            $payment = DonationPayment::query()->where('yookassa_payment_id', $yookassaPaymentId)->first();
            if ($payment !== null) {
                return $payment;
            }
        }

        if (is_string($donationPaymentUuid)) {
            return DonationPayment::query()->where('uuid', $donationPaymentUuid)->first();
        }

        return null;
    }

    /**
     * @return array{
     *     payment_uuid: string,
     *     status: string,
     *     amount: int,
     *     months: int,
     *     confirmation_url?: string|null,
     *     confirmation_type?: string|null,
     *     payment_method_type?: string|null,
     *     ad_free_until?: string|null
     * }
     */
    private function buildTokenPaymentResponse(
        DonationPayment $payment,
        ?User $user,
        ?\YooKassa\Model\Payment\PaymentInterface $yooKassaPayment = null,
    ): array {
        $response = [
            'payment_uuid' => $payment->uuid,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'months' => $payment->months,
        ];

        if ($payment->status === PaymentStatus::Succeeded && $user?->ad_free_until !== null) {
            $response['ad_free_until'] = $user->ad_free_until->toIso8601String();

            return $response;
        }

        if ($yooKassaPayment === null) {
            return $response;
        }

        if ($payment->status !== PaymentStatus::Succeeded) {
            $response['status'] = $yooKassaPayment->getStatus();
        }

        $confirmation = $yooKassaPayment->getConfirmation();

        if ($confirmation !== null) {
            $response['confirmation_type'] = $confirmation->getType();
            $response['confirmation_url'] = $this->resolveConfirmationUrl($confirmation);
        }

        $paymentMethod = $yooKassaPayment->getPaymentMethod();

        if ($paymentMethod !== null) {
            $response['payment_method_type'] = $paymentMethod->getType();
        }

        return $response;
    }

    private function resolveConfirmationUrl(
        \YooKassa\Model\Payment\Confirmation\AbstractConfirmation $confirmation,
    ): ?string {
        if ($confirmation instanceof ConfirmationRedirect
            || $confirmation instanceof ConfirmationMobileApplication) {
            return $confirmation->getConfirmationUrl();
        }

        return null;
    }

    /**
     * @return array{
     *     payment_uuid: string,
     *     confirmation_url: string|null,
     *     amount: int,
     *     months: int,
     *     status: string,
     *     confirmation_type?: string|null,
     *     payment_method_type?: string|null
     * }
     */
    private function buildRedirectPaymentResponse(
        DonationPayment $payment,
        \YooKassa\Model\Payment\PaymentInterface $yooKassaPayment,
        ?string $paymentMethodType = null,
    ): array {
        $confirmation = $yooKassaPayment->getConfirmation();
        $response = [
            'payment_uuid' => $payment->uuid,
            'confirmation_url' => $confirmation !== null ? $this->resolveConfirmationUrl($confirmation) : null,
            'amount' => $payment->amount,
            'months' => $payment->months,
            'status' => $yooKassaPayment->getStatus(),
        ];

        if ($confirmation !== null) {
            $response['confirmation_type'] = $confirmation->getType();
        }

        $method = $paymentMethodType ?? $yooKassaPayment->getPaymentMethod()?->getType();
        if ($method !== null) {
            $response['payment_method_type'] = $method;
        }

        return $response;
    }

    private function applySucceeded(DonationPayment $payment): void
    {
        if ($payment->status === PaymentStatus::Succeeded) {
            return;
        }

        DB::transaction(function () use ($payment): void {
            $payment->refresh();

            if ($payment->status === PaymentStatus::Succeeded) {
                return;
            }

            $user = User::query()->where('uuid', $payment->user_uuid)->first();

            if ($user === null) {
                return;
            }

            $this->adFreeSubscriptionService->extend($user, $payment->months);

            $payment->update([
                'status' => PaymentStatus::Succeeded,
                'paid_at' => now(),
            ]);
        });
    }
}
