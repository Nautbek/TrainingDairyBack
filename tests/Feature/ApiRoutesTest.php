<?php

namespace Tests\Feature;

use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_open_returns_ok_on_valid_payload(): void
    {
        // Действие
        $response = $this->postJson(
            '/api/user_open',
            [
                'app' => 'test_app',
            ],
            [
            'X-Forwarded-For' => '127.0.0.1',
            ]
        );

        // Проверка
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Ok',
            ]);
    }

    public function test_user_open_fails_validation_without_app(): void
    {
        $response = $this->postJson('/api/user_open', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['app']);
    }

    public function test_user_feedback_returns_ok_and_calls_telegram_service(): void
    {
        // Подменяем TelegramNotificationService мок-объектом
        $this->instance(
            TelegramNotificationService::class,
            Mockery::mock(TelegramNotificationService::class, function ($mock) {
                $mock->shouldReceive('sendFeedbackNotification')
                    ->once()
                    ->with('test_app', 'some feedback');
            })
        );

        $response = $this->postJson(
            '/api/user_feedback',
            [
                'app'  => 'test_app',
                'text' => 'some feedback',
            ],
            [
                'X-Forwarded-For' => '127.0.0.1',
            ]
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'Ok',
            ]);
    }

    public function test_user_feedback_fails_validation_without_required_fields(): void
    {
        $response = $this->postJson('/api/user_feedback', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['app', 'text']);
    }
}
