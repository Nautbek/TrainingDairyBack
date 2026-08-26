<?php

namespace Modules\TrainingDiary\Tests\Feature;

use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseEntrySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_exercise_creates_entry_with_approaches(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $response = $this->postJson('/api/training-diary/exercises', [
            'uuid' => $uuid,
            'title' => 'Жим лёжа',
            'logged_at' => '2026-08-19T10:15:00Z',
            'client_id' => 123,
            'approaches' => [
                ['weight' => 60, 'repeat_count' => 8, 'comment' => null, 'client_id' => 1],
                ['weight' => 65, 'repeat_count' => 6, 'comment' => 'тяжело', 'client_id' => 2],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'uuid', 'approaches' => [['client_id', 'uuid']]]);

        $this->assertDatabaseHas('training_diary_exercises', [
            'uuid' => $response->json('uuid'),
            'user_uuid' => $uuid,
            'title' => 'Жим лёжа',
            'client_id' => 123,
        ]);

        $this->assertDatabaseCount('training_diary_approaches', 2);
        $this->assertDatabaseHas('training_diary_approaches', [
            'weight' => 65,
            'repeat_count' => 6,
            'comment' => 'тяжело',
            'client_id' => 2,
        ]);
    }

    public function test_store_exercise_returns_unauthorized_for_unknown_user(): void
    {
        $response = $this->postJson('/api/training-diary/exercises', [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Присед',
            'logged_at' => '2026-08-19T10:15:00Z',
        ]);

        $response->assertStatus(401)->assertJson(['error' => 'Unauthorized']);
    }

    /** Старый клиент без measurement_type/duration_seconds/distance_meters — должен приниматься как раньше. */
    public function test_store_exercise_accepts_legacy_payload_without_measurement_fields(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $response = $this->postJson('/api/training-diary/exercises', [
            'uuid' => $uuid,
            'title' => 'Жим лёжа',
            'logged_at' => '2026-08-19T10:15:00Z',
            'client_id' => 124,
            'approaches' => [
                ['weight' => 60, 'repeat_count' => 8, 'comment' => null, 'client_id' => 1],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('training_diary_exercises', [
            'uuid' => $response->json('uuid'),
            'measurement_type' => 'reps',
        ]);

        $this->assertDatabaseHas('training_diary_approaches', [
            'weight' => 60,
            'repeat_count' => 8,
            'duration_seconds' => null,
            'distance_meters' => null,
        ]);
    }

    /** Упражнение типа "distance" — вес/повторы отсутствуют, вместо них дистанция и время. */
    public function test_store_exercise_accepts_distance_measurement_type(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $response = $this->postJson('/api/training-diary/exercises', [
            'uuid' => $uuid,
            'title' => 'Бег',
            'logged_at' => '2026-08-19T10:15:00Z',
            'measurement_type' => 'distance',
            'approaches' => [
                ['duration_seconds' => 1800, 'distance_meters' => 5200.5, 'client_id' => 1],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('training_diary_exercises', [
            'uuid' => $response->json('uuid'),
            'measurement_type' => 'distance',
        ]);

        $this->assertDatabaseHas('training_diary_approaches', [
            'duration_seconds' => 1800,
            'distance_meters' => 5200.5,
        ]);
    }

    /** (string) на mixed из json() не проходит phpstan level 9 (может быть массивом/объектом
     * без __toString) — is_string() честно сужает тип, без притворного каста. */
    private function registerUuid(): string
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        if (! is_string($uuid)) {
            $this->fail('Expected /api/register to return a string uuid.');
        }

        return $uuid;
    }

    public function test_pull_returns_history_with_valid_device_token(): void
    {
        $uuid = $this->registerUuid();
        $deviceToken = DeviceToken::issueFor($uuid)->token;

        $this->postJson('/api/training-diary/exercises', [
            'uuid' => $uuid,
            'title' => 'Жим лёжа',
            'logged_at' => '2026-08-19T10:15:00Z',
            'client_id' => 1,
            'approaches' => [
                ['weight' => 60, 'repeat_count' => 8, 'comment' => null, 'client_id' => 1],
            ],
        ]);

        $response = $this->getJson(
            "/api/training-diary/exercises?uuid={$uuid}",
            ['X-Device-Token' => $deviceToken],
        );

        $response->assertStatus(200)
            ->assertJsonStructure(['server_time', 'exercises' => [['uuid', 'title', 'approaches']]]);
        /** @var array<mixed> $exercises */
        $exercises = $response->json('exercises');
        $this->assertCount(1, $exercises);
    }

    public function test_pull_rejects_missing_device_token(): void
    {
        $uuid = $this->registerUuid();

        $response = $this->getJson("/api/training-diary/exercises?uuid={$uuid}");

        $response->assertStatus(422)->assertJsonValidationErrors('device_token');
    }

    public function test_pull_rejects_wrong_device_token(): void
    {
        $uuid = $this->registerUuid();
        DeviceToken::issueFor($uuid);

        $response = $this->getJson(
            "/api/training-diary/exercises?uuid={$uuid}",
            ['X-Device-Token' => 'not-the-real-token'],
        );

        $response->assertStatus(401)->assertJson(['error' => 'invalid_device_token']);
    }

    /** Токен, выданный другому аккаунту, не должен открывать чужую историю. */
    public function test_pull_rejects_device_token_belonging_to_another_account(): void
    {
        $uuid = $this->registerUuid();
        $otherUuid = $this->registerUuid();
        $otherDeviceToken = DeviceToken::issueFor($otherUuid)->token;

        $response = $this->getJson(
            "/api/training-diary/exercises?uuid={$uuid}",
            ['X-Device-Token' => $otherDeviceToken],
        );

        $response->assertStatus(401)->assertJson(['error' => 'invalid_device_token']);
    }
}
