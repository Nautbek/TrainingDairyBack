<?php

namespace Modules\TrainingDiary\Tests\Feature;

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

    /**
     * GET /training-diary/exercises (pull side) is deliberately unrouted — see
     * Modules/TrainingDiary/routes/api.php. It read a user's whole history by bare uuid
     * with no proof of ownership. ExerciseEntryIndexController and its tests are gone from
     * here until it's re-enabled behind real per-device auth.
     */
    public function test_get_training_diary_exercises_is_not_routed(): void
    {
        $response = $this->getJson('/api/training-diary/exercises?uuid=550e8400-e29b-41d4-a716-446655440000');

        $response->assertStatus(405);
    }
}
