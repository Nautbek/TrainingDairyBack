<?php

namespace Tests\Feature;

use App\Services\TelegramNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_register_creates_user_and_links_visits_and_feedback(): void
    {
        // Подменяем TelegramNotificationService мок-объектом
        $this->instance(
            TelegramNotificationService::class,
            Mockery::mock(TelegramNotificationService::class, function ($mock) {
                $mock->shouldReceive('sendFeedbackNotification')
                    ->times(2)
                    ->andReturn(true);
            })
        );

        // 1. Регистрируем пользователя
        $registerResponse = $this->postJson('/api/register');

        $registerResponse->assertStatus(201)
            ->assertJsonStructure(['uuid']);

        $uuid = $registerResponse->json('uuid');

        // Проверяем, что пользователь создан в БД
        $this->assertDatabaseHas('users', [
            'uuid' => $uuid,
        ]);

        $user = DB::table('users')->where('uuid', $uuid)->first();
        $this->assertNotNull($user, 'User should be created in database');
        $userId = $user->id;

        // 2. Создаем несколько входов (user_open) с UUID пользователя
        $this->postJson(
            '/api/user_open',
            ['app' => 'test_app_1'],
            [
                'X-Forwarded-For' => '127.0.0.1',
                'X-User-UUID' => $uuid,
            ]
        )->assertStatus(200);

        $this->postJson(
            '/api/user_open',
            ['app' => 'test_app_2'],
            [
                'X-Forwarded-For' => '127.0.0.1',
                'X-User-UUID' => $uuid,
            ]
        )->assertStatus(200);

        // Проверяем, что входы созданы с правильным user_id
        // Примечание: так как используется ON CONFLICT DO NOTHING и уникальный индекс на (visit_date, visit_ip),
        // при одинаковом IP и дате будет создана только одна запись
        $visits = DB::table('user_visits')
            ->where('user_id', $userId)
            ->get();

        // Может быть 1 или 2 записи в зависимости от того, одинаковый ли IP
        $this->assertGreaterThanOrEqual(1, $visits->count(), 'Should have at least 1 visit linked to user');
        
        // Проверяем, что записи созданы с правильным user_id
        foreach ($visits as $visit) {
            $this->assertEquals($userId, $visit->user_id, 'Visit should be linked to user');
        }

        // 3. Создаем несколько фидбеков (user_feedback) с UUID пользователя
        $this->postJson(
            '/api/user_feedback',
            [
                'app' => 'test_app_1',
                'text' => 'First feedback',
            ],
            [
                'X-Forwarded-For' => '127.0.0.1',
                'X-User-UUID' => $uuid,
            ]
        )->assertStatus(200);

        $this->postJson(
            '/api/user_feedback',
            [
                'app' => 'test_app_2',
                'text' => 'Second feedback',
            ],
            [
                'X-Forwarded-For' => '127.0.0.1',
                'X-User-UUID' => $uuid,
            ]
        )->assertStatus(200);

        // Проверяем, что фидбеки созданы с правильным user_id
        $feedbacks = DB::table('user_feedback')
            ->where('user_id', $userId)
            ->get();

        $this->assertCount(2, $feedbacks, 'Should have 2 feedbacks linked to user');
        
        // Проверяем, что оба фидбека присутствуют (независимо от порядка)
        $feedbackApps = $feedbacks->pluck('app')->toArray();
        $feedbackTexts = $feedbacks->pluck('text')->toArray();
        
        $this->assertContains('test_app_1', $feedbackApps);
        $this->assertContains('test_app_2', $feedbackApps);
        $this->assertContains('First feedback', $feedbackTexts);
        $this->assertContains('Second feedback', $feedbackTexts);
    }
}
