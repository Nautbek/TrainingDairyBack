<?php

namespace App\Services;

use App\Models\MyCarSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;

class MyCarSubscriptionService
{
    public function extend(User $user, int $months): Carbon
    {
        $subscription = MyCarSubscription::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        $base = $subscription->premium_until && $subscription->premium_until->isFuture()
            ? $subscription->premium_until
            : now();

        $premiumUntil = $base->copy()->addMonths($months);

        $subscription->update(['premium_until' => $premiumUntil]);

        return $premiumUntil;
    }

    public function isPremium(User $user): bool
    {
        $subscription = MyCarSubscription::query()
            ->where('user_id', $user->id)
            ->first();

        return $subscription !== null
            && $subscription->premium_until !== null
            && $subscription->premium_until->isFuture();
    }

    public function getPremiumUntil(User $user): ?Carbon
    {
        return MyCarSubscription::query()
            ->where('user_id', $user->id)
            ->value('premium_until');
    }
}
