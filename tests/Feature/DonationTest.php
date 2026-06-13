<?php

namespace Tests\Feature;

use App\Enums\Donation\PaymentStatus;
use App\Models\DonationPayment;
use App\Models\User;
use App\Services\DonationPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
            'yookassa_payment_id' => 'yk-test-payment-id',
            'amount' => 300,
            'months' => 3,
            'status' => PaymentStatus::Pending,
        ]);

        $response = $this->postJson('/api/yookassa/webhook', [
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'yk-test-payment-id',
                'status' => 'succeeded',
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
                ->withArgs(fn (string $userUuid, int $tier): bool => $tier === 1)
                ->andReturn([
                    'payment_uuid' => 'pay-uuid',
                    'confirmation_url' => 'https://yoomoney.ru/pay',
                    'amount' => 1,
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
                'amount' => 1,
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
