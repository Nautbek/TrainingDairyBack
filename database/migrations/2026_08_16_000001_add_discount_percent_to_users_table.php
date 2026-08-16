<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Персональная скидка на донат/подписку (0-100%), выставляется вручную из
 * админки. 100% -> оплата сводится к символическим 1 ₽ (условно-бесплатно),
 * см. App\Services\DonationPricingService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('ad_free_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }
};
