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

    public function test_index_returns_uploaded_exercises_with_approaches(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $this->postJson('/api/training-diary/exercises', [
            'uuid' => $uuid,
            'title' => 'Жим лёжа',
            'logged_at' => '2026-08-19T10:15:00Z',
            'client_id' => 123,
            'approaches' => [
                ['weight' => 60, 'repeat_count' => 8, 'comment' => null, 'client_id' => 1],
            ],
        ]);

        $response = $this->getJson('/api/training-diary/exercises?uuid='.$uuid);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'server_time',
                'exercises' => [['uuid', 'title', 'measurement_type', 'logged_at', 'created_at', 'approaches']],
            ]);

        $this->assertSame('Жим лёжа', $response->json('exercises.0.title'));
        // json() decodes 60.0 back as an int when it round-trips as an integral value.
        $this->assertEquals(60.0, $response->json('exercises.0.approaches.0.weight'));
        $this->assertSame(8, $response->json('exercises.0.approaches.0.repeat_count'));
    }

    /**
     * created_at (server sync time), not logged_at (user-entered workout time), drives the
     * filter — a backdated entry synced "now" must still show up on a pull started "now".
     */
    public function test_index_filters_by_since(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        \Illuminate\Support\Carbon::setTestNow('2026-08-19T10:00:00Z');
        $this->postJson('/api/training-diary/exercises', [
            'uuid' => $uuid,
            'title' => 'Присед',
            'logged_at' => '2026-08-01T10:15:00Z',
        ]);

        $cutoff = '2026-08-19T10:05:00Z';

        \Illuminate\Support\Carbon::setTestNow('2026-08-19T10:10:00Z');
        $this->postJson('/api/training-diary/exercises', [
            'uuid' => $uuid,
            // Logged in the past, synced after the cutoff — must still appear.
            'title' => 'Тяга',
            'logged_at' => '2026-07-01T10:15:00Z',
        ]);
        \Illuminate\Support\Carbon::setTestNow();

        $response = $this->getJson('/api/training-diary/exercises?uuid='.$uuid.'&since='.$cutoff);

        $response->assertStatus(200);
        $titles = collect($response->json('exercises'))->pluck('title');
        $this->assertFalse($titles->contains('Присед'));
        $this->assertTrue($titles->contains('Тяга'));
    }

    public function test_index_returns_unauthorized_for_unknown_user(): void
    {
        $response = $this->getJson('/api/training-diary/exercises?uuid=550e8400-e29b-41d4-a716-446655440000');

        $response->assertStatus(401)->assertJson(['error' => 'Unauthorized']);
    }

    public function test_index_only_returns_own_user_exercises(): void
    {
        $uuidA = $this->postJson('/api/register')->json('uuid');
        $uuidB = $this->postJson('/api/register')->json('uuid');

        $this->postJson('/api/training-diary/exercises', [
            'uuid' => $uuidA,
            'title' => 'Жим лёжа',
            'logged_at' => '2026-08-19T10:15:00Z',
        ]);

        $response = $this->getJson('/api/training-diary/exercises?uuid='.$uuidB);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('exercises'));
    }
}
