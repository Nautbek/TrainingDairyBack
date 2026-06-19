<?php

namespace App\Services;

use App\Models\TripSplit\UsageBalance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TripSplitCreditsService
{
    public function getCountForUser(User $user): int
    {
        return (int) UsageBalance::query()
            ->where('user_id', $user->id)
            ->value('count');
    }

    public function getCountByUserUuid(string $userUuid): int
    {
        $user = User::query()->where('uuid', $userUuid)->first();

        if ($user === null) {
            return 0;
        }

        return $this->getCountForUser($user);
    }

    public function grant(User $user, int $credits): UsageBalance
    {
        if ($credits <= 0) {
            return $this->getOrCreateBalance($user);
        }

        return DB::transaction(function () use ($user, $credits): UsageBalance {
            $balance = UsageBalance::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($balance === null) {
                return UsageBalance::query()->create([
                    'user_id' => $user->id,
                    'count' => $credits,
                ]);
            }

            $balance->update([
                'count' => $balance->count + $credits,
            ]);

            return $balance->fresh();
        });
    }

    public function consume(User $user, int $credits = 1): bool
    {
        if ($credits <= 0) {
            return false;
        }

        return DB::transaction(function () use ($user, $credits): bool {
            $balance = UsageBalance::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($balance === null || $balance->count < $credits) {
                return false;
            }

            $balance->update([
                'count' => $balance->count - $credits,
            ]);

            return true;
        });
    }

    private function getOrCreateBalance(User $user): UsageBalance
    {
        return UsageBalance::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['count' => 0],
        );
    }
}
