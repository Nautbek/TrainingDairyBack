<?php

namespace App\Http\Controllers\Api\Donation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donation\DonationTiersRequest;
use App\Models\User;
use App\Services\DonationPricingService;
use Illuminate\Http\JsonResponse;

/**
 * Отдаёт актуальный список тарифов доната/подписки вместе с их ценой.
 * Приложение не хранит цены/тарифы у себя — все значения (включая
 * персональную скидку юзера, если она выставлена в админке) приходят
 * отсюда и используются как есть.
 */
class DonationTiersController extends Controller
{
    public function __invoke(DonationTiersRequest $request, DonationPricingService $pricingService): JsonResponse
    {
        $uuid = $request->validated('uuid');
        $user = $uuid !== null ? User::query()->where('uuid', $uuid)->first() : null;

        return response()->json([
            'tiers' => $pricingService->tiersFor($user),
        ]);
    }
}
