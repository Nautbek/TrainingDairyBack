<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DonationAppResolver
{
    public function resolve(?string $app, string $userUuid): ?string
    {
        $app = $app !== null ? trim($app) : '';
        if ($app !== '') {
            return $app;
        }

        $userId = User::query()->where('uuid', $userUuid)->value('id');
        if ($userId === null) {
            return null;
        }

        $fromVisit = DB::table('user_visits')
            ->where('user_id', $userId)
            ->orderByDesc('visit_date')
            ->value('app');

        if (! is_string($fromVisit)) {
            return null;
        }

        $fromVisit = trim($fromVisit);

        return $fromVisit !== '' ? $fromVisit : null;
    }
}
