<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class AdFreeSubscriptionService
{
    public function extend(User $user, int $months): Carbon
    {
        $base = $user->ad_free_until && $user->ad_free_until->isFuture()
            ? $user->ad_free_until
            : now();

        $adFreeUntil = $base->copy()->addMonths($months);

        $user->update(['ad_free_until' => $adFreeUntil]);

        return $adFreeUntil;
    }

    public function isAdFree(User $user): bool
    {
        return $user->ad_free_until !== null && $user->ad_free_until->isFuture();
    }
}
