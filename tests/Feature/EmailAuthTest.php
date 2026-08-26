<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_without_uuid_creates_fresh_account_with_device_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'new@example.com',
            'password' => 'correct-horse',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['uuid', 'device_token']);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        $this->assertDatabaseHas('device_tokens', [
            'user_uuid' => $response->json('uuid'),
            'token' => $response->json('device_token'),
        ]);
    }

    public function test_register_with_uuid_attaches_email_to_existing_anonymous_account(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $response = $this->postJson('/api/auth/register', [
            'email' => 'attach@example.com',
            'password' => 'correct-horse',
            'uuid' => $uuid,
        ]);

        $response->assertStatus(201)->assertJson(['uuid' => $uuid]);

        $this->assertDatabaseHas('users', [
            'uuid' => $uuid,
            'email' => 'attach@example.com',
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_register_with_uuid_rejects_unknown_uuid(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'ghost@example.com',
            'password' => 'correct-horse',
            'uuid' => (string) Str::uuid(),
        ]);

        $response->assertStatus(404)->assertJson(['error' => 'not_found']);
    }

    public function test_register_with_uuid_rejects_account_that_already_has_email(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');
        $this->postJson('/api/auth/register', [
            'email' => 'first@example.com',
            'password' => 'correct-horse',
            'uuid' => $uuid,
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'second@example.com',
            'password' => 'correct-horse',
            'uuid' => $uuid,
        ]);

        $response->assertStatus(409)->assertJson(['error' => 'already_registered']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'taken@example.com',
            'password' => 'correct-horse',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'taken@example.com',
            'password' => 'another-pass',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_login_succeeds_with_correct_credentials_and_issues_new_device_token(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create([
            'uuid' => $uuid,
            'email' => 'me@example.com',
            'password' => Hash::make('correct-horse'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'me@example.com',
            'password' => 'correct-horse',
        ]);

        $response->assertStatus(200)
            ->assertJson(['uuid' => $uuid])
            ->assertJsonStructure(['device_token']);

        $this->assertDatabaseHas('device_tokens', ['user_uuid' => $uuid]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'me@example.com',
            'password' => Hash::make('correct-horse'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'me@example.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertStatus(401)->assertJson(['error' => 'invalid_credentials']);
    }

    public function test_login_fails_for_unknown_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        $response->assertStatus(401)->assertJson(['error' => 'invalid_credentials']);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'target@example.com',
            'password' => Hash::make('correct-horse'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'target@example.com',
                'password' => 'wrong',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'target@example.com',
            'password' => 'correct-horse',
        ])->assertStatus(429);
    }

    /** Anonymous /register accounts have a placeholder password no one knows — login must not work for them. */
    public function test_login_fails_for_anonymous_account_without_email_login(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');
        $user = User::query()->where('uuid', $uuid)->first();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'anything',
        ]);

        $response->assertStatus(401)->assertJson(['error' => 'invalid_credentials']);
    }
}
