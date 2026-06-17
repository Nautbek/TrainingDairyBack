<?php

namespace Tests\Feature;

use App\Enums\Donation\PaymentStatus;
use App\Models\DonationPayment;
use App\Models\User;
use App\Services\DonationPaymentService;
use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class DonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_status_for_user_without_donation(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create(['uuid' => $uuid]);

        $response = $this->getJson('/api/user/subscription?uuid='.$uuid);

        $response->assertStatus(200)
            ->assertJson([
                'is_ad_free' => false,
                'ad_free_until' => null,
            ]);
    }

    public function test_webhook_marks_payment_succeeded_and_extends_subscription(): void
    {
        $uuid = (string) Str::uuid();
        $user = User::factory()->create(['uuid' => $uuid]);

        $payment = DonationPayment::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_uuid' => $uuid,
            'app' => 'nutrition_diary',
            'yookassa_payment_id' => 'yk-test-payment-id',
            'amount' => 300,
            'months' => 3,
            'status' => PaymentStatus::Pending,
        ]);

        $this->instance(
            TelegramNotificationService::class,
            Mockery::mock(TelegramNotificationService::class, function ($mock) use ($uuid): void {
                $mock->shouldReceive('sendDonationPaymentNotification')
                    ->once()
                    ->withArgs(function (
                        int $amount,
                        int $months,
                        string $userUuid,
                        ?string $paymentMethodType,
                        ?string $yookassaPaymentId,
                        ?string $adFreeUntil,
                        ?string $app,
                    ) use ($uuid): bool {
                        return $amount === 300
                            && $months === 3
                            && $userUuid === $uuid
                            && $paymentMethodType === 'sbp'
                            && $yookassaPaymentId === 'yk-test-payment-id'
                            && $adFreeUntil !== null
                            && $app === 'nutrition_diary';
                    })
                    ->andReturn(true);
            }),
        );

        $response = $this->postJson('/api/yookassa/webhook', [
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'yk-test-payment-id',
                'status' => 'succeeded',
                'payment_method' => [
                    'type' => 'sbp',
                ],
                'metadata' => [
                    'donation_payment_uuid' => $payment->uuid,
                    'user_uuid' => $uuid,
                    'months' => '3',
                ],
            ],
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $payment->refresh();

        $this->assertTrue($user->ad_free_until->isFuture());
        $this->assertEquals(PaymentStatus::Succeeded, $payment->status);
    }

    public function test_create_donation_returns_confirmation_url(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create(['uuid' => $uuid]);

        $this->mock(DonationPaymentService::class, function ($mock): void {
            $mock->shouldReceive('createPayment')
                ->once()
                ->withArgs(fn (string $userUuid, int $tier, ?string $app): bool => $tier === 1 && $app === null)
                ->andReturn([
                    'payment_uuid' => 'pay-uuid',
                    'confirmation_url' => 'https://yoomoney.ru/pay',
                    'amount' => 120,
                    'months' => 1,
                    'status' => 'pending',
                ]);
        });

        $response = $this->postJson('/api/donations/create', [
            'uuid' => $uuid,
            'tier' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'confirmation_url' => 'https://yoomoney.ru/pay',
                'amount' => 120,
                'months' => 1,
            ]);
    }

    public function test_create_donation_fails_for_invalid_tier(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create(['uuid' => $uuid]);

        $response = $this->postJson('/api/donations/create', [
            'uuid' => $uuid,
            'tier' => 500,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tier']);
    }
}
