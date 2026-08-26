<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_code_for_known_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'me@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->postJson('/api/auth/forgot-password', ['email' => 'me@example.com']);

        $response->assertStatus(200);
        Notification::assertSentTo($user, ResetPasswordCodeNotification::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'me@example.com']);
    }

    /** Same response either way — a different one would let someone enumerate registered emails. */
    public function test_forgot_password_gives_generic_response_for_unknown_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertStatus(200);
        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'nobody@example.com']);
    }

    public function test_forgot_password_does_not_send_code_for_anonymous_account(): void
    {
        Notification::fake();

        $uuid = $this->postJson('/api/register')->json('uuid');
        $anonymousEmail = $uuid.'@temp.local';

        $response = $this->postJson('/api/auth/forgot-password', ['email' => $anonymousEmail]);

        $response->assertStatus(200);
        Notification::assertNothingSent();
    }

    public function test_reset_password_succeeds_with_correct_code(): void
    {
        $uuid = (string) \Illuminate\Support\Str::uuid();
        User::factory()->create([
            'uuid' => $uuid,
            'email' => 'me@example.com',
            'password' => Hash::make('old-password'),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'me@example.com',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'me@example.com',
            'code' => '123456',
            'password' => 'brand-new-password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['uuid' => $uuid])
            ->assertJsonStructure(['device_token']);

        $this->assertTrue(Hash::check('brand-new-password', User::query()->where('uuid', $uuid)->first()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'me@example.com']);
    }

    public function test_reset_password_rejects_wrong_code(): void
    {
        User::factory()->create(['email' => 'me@example.com']);

        DB::table('password_reset_tokens')->insert([
            'email' => 'me@example.com',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'me@example.com',
            'code' => '000000',
            'password' => 'brand-new-password',
        ]);

        $response->assertStatus(400)->assertJson(['error' => 'invalid_or_expired_code']);
    }

    public function test_reset_password_rejects_expired_code(): void
    {
        User::factory()->create(['email' => 'me@example.com']);

        DB::table('password_reset_tokens')->insert([
            'email' => 'me@example.com',
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(31),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'me@example.com',
            'code' => '123456',
            'password' => 'brand-new-password',
        ]);

        $response->assertStatus(400)->assertJson(['error' => 'invalid_or_expired_code']);
    }

    public function test_reset_password_rejects_when_no_code_was_requested(): void
    {
        User::factory()->create(['email' => 'me@example.com']);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'me@example.com',
            'code' => '123456',
            'password' => 'brand-new-password',
        ]);

        $response->assertStatus(400)->assertJson(['error' => 'invalid_or_expired_code']);
    }

    public function test_reset_password_code_is_single_use(): void
    {
        User::factory()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'me@example.com',
            'password' => Hash::make('old-password'),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'me@example.com',
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $first = $this->postJson('/api/auth/reset-password', [
            'email' => 'me@example.com',
            'code' => '123456',
            'password' => 'first-new-password',
        ]);
        $first->assertStatus(200);

        $second = $this->postJson('/api/auth/reset-password', [
            'email' => 'me@example.com',
            'code' => '123456',
            'password' => 'second-new-password',
        ]);
        $second->assertStatus(400)->assertJson(['error' => 'invalid_or_expired_code']);
    }
}
