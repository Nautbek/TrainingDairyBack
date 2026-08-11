<?php

namespace Tests\Feature;

use App\Models\FeedbackThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeedbackThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_thread_and_binds_to_user(): void
    {
        $uuid = (string) Str::uuid();
        $user = User::factory()->create(['uuid' => $uuid]);

        $response = $this->withHeader('X-User-UUID', $uuid)
            ->postJson('/api/feedback/threads', [
                'app' => 'com.example.trainingdiary',
                'text' => 'Кнопка сохранения не работает на экране программ',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('thread.status', 'open')
            ->assertJsonPath('thread.messages.0.sender', 'user');

        $this->assertDatabaseHas('feedback_threads', [
            'user_id' => $user->id,
            'app' => 'com.example.trainingdiary',
            'status' => 'open',
        ]);
    }

    public function test_rejects_thread_creation_without_uuid(): void
    {
        $response = $this->postJson('/api/feedback/threads', [
            'app' => 'com.example.trainingdiary',
            'text' => 'Кнопка сохранения не работает на экране программ',
        ]);

        $response->assertStatus(422);
    }

    public function test_lists_only_own_threads(): void
    {
        $uuid = (string) Str::uuid();
        $user = User::factory()->create(['uuid' => $uuid]);
        $otherUser = User::factory()->create(['uuid' => (string) Str::uuid()]);

        FeedbackThread::query()->create(['user_id' => $user->id, 'app' => 'a', 'status' => 'open']);
        FeedbackThread::query()->create(['user_id' => $otherUser->id, 'app' => 'a', 'status' => 'open']);

        $response = $this->withHeader('X-User-UUID', $uuid)->getJson('/api/feedback/threads');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'threads');
    }

    public function test_message_to_closed_thread_returns_409(): void
    {
        $uuid = (string) Str::uuid();
        $user = User::factory()->create(['uuid' => $uuid]);
        $thread = FeedbackThread::query()->create([
            'user_id' => $user->id,
            'app' => 'com.example.trainingdiary',
            'status' => 'closed',
        ]);

        $response = $this->withHeader('X-User-UUID', $uuid)
            ->postJson("/api/feedback/threads/{$thread->id}/messages", [
                'text' => 'Ещё сообщение',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('error', 'thread_closed');
    }

    public function test_cannot_access_foreign_thread(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create(['uuid' => $uuid]);
        $otherUser = User::factory()->create(['uuid' => (string) Str::uuid()]);
        $thread = FeedbackThread::query()->create([
            'user_id' => $otherUser->id,
            'app' => 'a',
            'status' => 'open',
        ]);

        $response = $this->withHeader('X-User-UUID', $uuid)
            ->getJson("/api/feedback/threads/{$thread->id}");

        $response->assertStatus(403);
    }
}
