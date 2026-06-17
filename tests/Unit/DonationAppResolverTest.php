<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\DonationAppResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class DonationAppResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_explicit_app_when_passed(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create(['uuid' => $uuid]);

        $resolver = new DonationAppResolver;

        $this->assertSame('training_diary', $resolver->resolve('training_diary', $uuid));
    }

    public function test_falls_back_to_latest_user_visit_app(): void
    {
        $uuid = (string) Str::uuid();
        $user = User::factory()->create(['uuid' => $uuid]);

        DB::table('user_visits')->insert([
            [
                'visit_date' => '2026-06-10',
                'visit_ip' => '127.0.0.1',
                'app' => 'old_app',
                'user_id' => $user->id,
            ],
            [
                'visit_date' => '2026-06-15',
                'visit_ip' => '127.0.0.2',
                'app' => 'nutrition_diary',
                'user_id' => $user->id,
            ],
        ]);

        $resolver = new DonationAppResolver;

        $this->assertSame('nutrition_diary', $resolver->resolve(null, $uuid));
    }

    public function test_returns_null_when_app_not_passed_and_no_visits(): void
    {
        $uuid = (string) Str::uuid();
        User::factory()->create(['uuid' => $uuid]);

        $resolver = new DonationAppResolver;

        $this->assertNull($resolver->resolve(null, $uuid));
    }
}
