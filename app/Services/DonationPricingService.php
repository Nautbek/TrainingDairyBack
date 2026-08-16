<?php

namespace App\Services;

use App\Models\User;

/**
 * Единая точка расчёта цены доната/подписки с учётом персональной скидки
 * юзера (User::discount_percent, 0-100, выставляется вручную из админки —
 * см. Modules/TrainingDiary/Http/Controllers/Admin/UserDiscountController).
 *
 * При 100% скидке платёж не становится нулевым (ЮKassa не проводит платежи
 * на 0 ₽) — цена сводится к символическому 1 ₽, то есть условно-бесплатно.
 */
class DonationPricingService
{
    private const MIN_AMOUNT = 1;

    /**
     * @return array<int, array{key: int, amount: int, base_amount: int, discount_percent: int, months: int, label: string}>
     */
    public function tiersFor(?User $user): array
    {
        $tiers = [];

        foreach (config('donations.tiers', []) as $key => $tier) {
            $tiers[] = [
                'key' => (int) $key,
                'amount' => $this->amountFor($user, $tier),
                'base_amount' => (int) $tier['amount'],
                'discount_percent' => $this->discountPercentFor($user),
                'months' => (int) $tier['months'],
                'label' => (string) $tier['label'],
            ];
        }

        return $tiers;
    }

    /**
     * @param  array{amount: int, months: int, label: string}  $tier
     */
    public function amountFor(?User $user, array $tier): int
    {
        $discountPercent = $this->discountPercentFor($user);

        if ($discountPercent <= 0) {
            return (int) $tier['amount'];
        }

        $discounted = (int) round($tier['amount'] * (100 - $discountPercent) / 100);

        return max(self::MIN_AMOUNT, $discounted);
    }

    private function discountPercentFor(?User $user): int
    {
        if ($user === null) {
            return 0;
        }

        return max(0, min(100, $user->discount_percent ?? 0));
    }
}
