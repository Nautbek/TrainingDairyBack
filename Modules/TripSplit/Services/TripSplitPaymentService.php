<?php

namespace Modules\TripSplit\Services;

use App\Contracts\PaymentAppHandler;
use App\Enums\Donation\PaymentStatus;
use App\Models\DonationPayment;
use App\Models\User;
use App\Services\TelegramNotificationService;
use App\Services\YooKassaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use YooKassa\Model\CurrencyCode;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Payment\Confirmation\AbstractConfirmation;
use YooKassa\Model\Payment\Confirmation\ConfirmationMobileApplication;
use YooKassa\Model\Payment\Confirmation\ConfirmationRedirect;
use YooKassa\Model\Payment\ConfirmationType;
use YooKassa\Model\Payment\PaymentInterface;
use YooKassa\Model\Payment\PaymentStatus as YooKassaPaymentStatus;
use YooKassa\Request\Payments\CreatePaymentResponse;

class TripSplitPaymentService implements PaymentAppHandler
{
    public function __construct(
        private readonly YooKassaService $yooKassaService,
        private readonly TripSplitCreditsService $tripSplitCreditsService,
        private readonly TelegramNotificationService $telegramNotificationService,
    ) {}

    /**
     * @return array{
     *     payment_uuid: string,
     *     status: string,
     *     amount: int,
     *     credits: int,
     *     confirmation_url?: string|null,
     *     confirmation_type?: string|null,
     *     payment_method_type?: string|null,
     *     usage_count?: int
     * }
     */
    public function createPaymentWithToken(string $userUuid, int $tierKey, string $paymentToken): array
    {
        $tier = $this->resolveTier($tierKey);
        $payment = $this->createPendingPayment($userUuid, $tier);

        $yooKassaPayment = $this->requestPaymentWithToken($payment, $paymentToken, $payment->uuid);

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
     * @return array{
     *     payment_uuid: string,
     *     confirmation_url: string|null,
     *     amount: int,
     *     credits: int,
     *     status: string,
     *     confirmation_type?: string|null,
     *     payment_method_type?: string|null
     * }
     */
    public function createPayment(string $userUuid, int $tierKey): array
    {
        $tier = $this->resolveTier($tierKey);
        $payment = $this->createPendingPayment($userUuid, $tier);

        $yooKassaPayment = $this->requestRedirectPayment($payment, $payment->uuid);

        $payment->update([
            'yookassa_payment_id' => $yooKassaPayment->getId(),
        ]);

        return $this->buildRedirectPaymentResponse($payment, $yooKassaPayment);
    }

    /**
     * @return array{
     *     payment_uuid: string,
     *     confirmation_url: string|null,
     *     amount: int,
     *     credits: int,
     *     status: string,
     *     confirmation_type?: string|null,
     *     payment_method_type?: string
     * }
     */
    public function createSbpPayment(string $userUuid, int $tierKey): array
    {
        $tier = $this->resolveTier($tierKey);
        $payment = $this->createPendingPayment($userUuid, $tier);

        $yooKassaPayment = $this->requestSbpPayment($payment, $payment->uuid);

        $payment->update([
            'yookassa_payment_id' => $yooKassaPayment->getId(),
        ]);

        return $this->buildRedirectPaymentResponse($payment, $yooKassaPayment, 'sbp');
    }

    public function supports(DonationPayment $payment): bool
    {
        return $payment->app === (string) config('tripsplit.app');
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
     * @return array{amount: int, credits: int, label: string}
     */
    private function resolveTier(int $tierKey): array
    {
        $tier = config("tripsplit.tiers.{$tierKey}");

        if ($tier === null) {
            throw new \InvalidArgumentException('Invalid TripSplit payment tier');
        }

        return $tier;
    }

    /**
     * @param  array{amount: int, credits: int, label: string}  $tier
     */
    private function createPendingPayment(string $userUuid, array $tier): DonationPayment
    {
        do {
            $paymentUuid = (string) Str::uuid();
        } while (DonationPayment::query()->where('uuid', $paymentUuid)->exists());

        return DonationPayment::query()->create([
            'uuid' => $paymentUuid,
            'user_uuid' => $userUuid,
            'app' => (string) config('tripsplit.app'),
            'amount' => $tier['amount'],
            'months' => $tier['credits'],
            'status' => PaymentStatus::Pending,
        ]);
    }

    private function requestRedirectPayment(DonationPayment $payment, string $idempotenceKey): CreatePaymentResponse
    {
        return $this->yooKassaService->createPayment([
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
            'metadata' => $this->metadata($payment),
        ], $idempotenceKey);
    }

    private function requestPaymentWithToken(
        DonationPayment $payment,
        string $paymentToken,
        string $idempotenceKey,
    ): CreatePaymentResponse {
        return $this->yooKassaService->createPayment([
            'amount' => [
                'value' => number_format($payment->amount, 2, '.', ''),
                'currency' => CurrencyCode::RUB,
            ],
            'capture' => true,
            'payment_token' => $paymentToken,
            'description' => "TripSplit — {$payment->months} подсчёт(ов) итогов",
            'metadata' => $this->metadata($payment),
        ], $idempotenceKey);
    }

    private function requestSbpPayment(DonationPayment $payment, string $idempotenceKey): CreatePaymentResponse
    {
        return $this->yooKassaService->createPayment([
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
            'metadata' => $this->metadata($payment),
        ], $idempotenceKey);
    }

    /**
     * @return array<string, string>
     */
    private function metadata(DonationPayment $payment): array
    {
        return [
            'donation_payment_uuid' => $payment->uuid,
            'user_uuid' => $payment->user_uuid,
            'credits' => (string) $payment->months,
            'app' => (string) config('tripsplit.app'),
        ];
    }

    /**
     * @param  array<string, mixed>  $paymentObject
     */
    private function markSucceeded(array $paymentObject): void
    {
        $payment = DonationPayment::resolveFromYooKassaObject($paymentObject);

        if ($payment === null || ! $this->supports($payment)) {
            return;
        }

        $wasAlreadySucceeded = $payment->status === PaymentStatus::Succeeded;
        $this->applySucceeded($payment);

        if ($wasAlreadySucceeded) {
            return;
        }

        $payment->refresh();
        if ($payment->status !== PaymentStatus::Succeeded) {
            return;
        }

        $user = User::query()->where('uuid', $payment->user_uuid)->first();
        $paymentMethodType = null;
        if (isset($paymentObject['payment_method']) && is_array($paymentObject['payment_method'])) {
            $type = $paymentObject['payment_method']['type'] ?? null;
            if (is_string($type)) {
                $paymentMethodType = $type;
            }
        }

        $yookassaPaymentId = $paymentObject['id'] ?? $payment->yookassa_payment_id;
        $usageCount = $user !== null ? $this->tripSplitCreditsService->getCountForUser($user) : 0;

        $this->notifyPaymentSucceeded(
            $payment,
            $paymentMethodType,
            is_string($yookassaPaymentId) ? $yookassaPaymentId : null,
            $usageCount,
        );
    }

    /**
     * @param  array<string, mixed>  $paymentObject
     */
    private function markCanceled(array $paymentObject): void
    {
        $payment = DonationPayment::resolveFromYooKassaObject($paymentObject);

        if ($payment === null || ! $this->supports($payment) || $payment->status === PaymentStatus::Succeeded) {
            return;
        }

        $payment->update(['status' => PaymentStatus::Canceled]);
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

            $this->tripSplitCreditsService->grant($user, $payment->months);

            $payment->update([
                'status' => PaymentStatus::Succeeded,
                'paid_at' => now(),
            ]);
        });
    }

    private function notifyPaymentSucceeded(
        DonationPayment $payment,
        ?string $paymentMethodType,
        ?string $yookassaPaymentId,
        int $usageCount,
    ): void {
        $method = $paymentMethodType ?: 'не указан';
        $message = "TripSplit оплата: {$payment->amount} ₽, {$payment->months} подсчёт(ов)\n"
            ."Пользователь: {$payment->user_uuid}\n"
            ."Способ: {$method}";

        if ($yookassaPaymentId !== null && $yookassaPaymentId !== '') {
            $message .= "\nЮKassa: {$yookassaPaymentId}";
        }

        $message .= "\nБаланс: {$usageCount}";

        $this->telegramNotificationService->sendMessage($message);
    }

    /**
     * @return array{
     *     payment_uuid: string,
     *     status: string,
     *     amount: int,
     *     credits: int,
     *     confirmation_url?: string|null,
     *     confirmation_type?: string|null,
     *     payment_method_type?: string|null,
     *     usage_count?: int
     * }
     */
    private function buildTokenPaymentResponse(
        DonationPayment $payment,
        ?User $user,
        ?PaymentInterface $yooKassaPayment = null,
    ): array {
        $response = [
            'payment_uuid' => $payment->uuid,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'credits' => $payment->months,
        ];

        if ($payment->status === PaymentStatus::Succeeded && $user !== null) {
            $response['usage_count'] = $this->tripSplitCreditsService->getCountForUser($user);

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

    /**
     * @return array{
     *     payment_uuid: string,
     *     confirmation_url: string|null,
     *     amount: int,
     *     credits: int,
     *     status: string,
     *     confirmation_type?: string|null,
     *     payment_method_type?: string|null
     * }
     */
    private function buildRedirectPaymentResponse(
        DonationPayment $payment,
        PaymentInterface $yooKassaPayment,
        ?string $paymentMethodType = null,
    ): array {
        $confirmation = $yooKassaPayment->getConfirmation();
        $response = [
            'payment_uuid' => $payment->uuid,
            'confirmation_url' => $confirmation !== null ? $this->resolveConfirmationUrl($confirmation) : null,
            'amount' => $payment->amount,
            'credits' => $payment->months,
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

    private function resolveConfirmationUrl(AbstractConfirmation $confirmation): ?string
    {
        if ($confirmation instanceof ConfirmationRedirect
            || $confirmation instanceof ConfirmationMobileApplication) {
            return $confirmation->getConfirmationUrl();
        }

        return null;
    }
}
