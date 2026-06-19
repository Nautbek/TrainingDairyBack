<?php

namespace Tests\Feature;

use App\Enums\Donation\PaymentStatus;
use App\Models\DonationPayment;
use App\Models\TripSplit\UsageBalance;
use App\Models\User;
use App\Services\TelegramNotificationService;
use App\Services\TripSplitPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class TripSplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_credits_status_for_user_without_balance(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create(['uuid' => $uuid]);

        $response = $this->getJson('/api/tripsplit/credits?uuid='.$uuid);

        $response->assertStatus(200)
            ->assertJson([
                'usage_count' => 0,
            ]);
    }

    public function test_webhook_marks_tripsplit_payment_succeeded_and_grants_credits(): void
    {
        $uuid = (string) Str::uuid();
        $user = User::factory()->create(['uuid' => $uuid]);

        $payment = DonationPayment::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_uuid' => $uuid,
            'app' => 'tripsplit',
            'yookassa_payment_id' => 'yk-tripsplit-payment-id',
            'amount' => 99,
            'months' => 1,
            'status' => PaymentStatus::Pending,
        ]);

        $this->instance(
            TelegramNotificationService::class,
            Mockery::mock(TelegramNotificationService::class, function ($mock) use ($uuid): void {
                $mock->shouldReceive('sendTripSplitPaymentNotification')
                    ->once()
                    ->withArgs(function (
                        int $amount,
                        int $credits,
                        string $userUuid,
                        ?string $paymentMethodType,
                        ?string $yookassaPaymentId,
                        ?int $usageCount,
                    ) use ($uuid): bool {
                        return $amount === 99
                            && $credits === 1
                            && $userUuid === $uuid
                            && $paymentMethodType === 'bank_card'
                            && $yookassaPaymentId === 'yk-tripsplit-payment-id'
                            && $usageCount === 1;
                    })
                    ->andReturn(true);
            }),
        );

        $response = $this->postJson('/api/yookassa/webhook', [
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'yk-tripsplit-payment-id',
                'status' => 'succeeded',
                'payment_method' => [
                    'type' => 'bank_card',
                ],
                'metadata' => [
                    'donation_payment_uuid' => $payment->uuid,
                    'user_uuid' => $uuid,
                    'credits' => '1',
                    'app' => 'tripsplit',
                ],
            ],
        ]);

        $response->assertStatus(200);

        $payment->refresh();

        $this->assertEquals(PaymentStatus::Succeeded, $payment->status);
        $this->assertDatabaseHas('tripsplit_usage_balances', [
            'user_id' => $user->id,
            'count' => 1,
        ]);
    }

    public function test_create_tripsplit_payment_returns_confirmation_url(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create(['uuid' => $uuid]);

        $this->mock(TripSplitPaymentService::class, function ($mock): void {
            $mock->shouldReceive('createPayment')
                ->once()
                ->withArgs(fn (string $userUuid, int $tier): bool => $tier === 1)
                ->andReturn([
                    'payment_uuid' => 'pay-uuid',
                    'confirmation_url' => 'https://yoomoney.ru/pay',
                    'amount' => 99,
                    'credits' => 1,
                    'status' => 'pending',
                ]);
        });

        $response = $this->postJson('/api/tripsplit/payments/create', [
            'uuid' => $uuid,
            'tier' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'confirmation_url' => 'https://yoomoney.ru/pay',
                'amount' => 99,
                'credits' => 1,
            ]);
    }

    public function test_create_tripsplit_payment_fails_for_invalid_tier(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create(['uuid' => $uuid]);

        $response = $this->postJson('/api/tripsplit/payments/create', [
            'uuid' => $uuid,
            'tier' => 99,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tier']);
    }

    public function test_consume_credits_decrements_balance(): void
    {
        $user = User::factory()->create();
        UsageBalance::query()->create([
            'user_id' => $user->id,
            'count' => 3,
        ]);

        $service = app(\App\Services\TripSplitCreditsService::class);

        $this->assertTrue($service->consume($user, 1));
        $this->assertEquals(2, $service->getCountForUser($user));
        $this->assertFalse($service->consume($user, 5));
        $this->assertEquals(2, $service->getCountForUser($user));
    }
}
