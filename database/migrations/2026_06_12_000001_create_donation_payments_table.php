<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ad_free_until')->nullable()->after('uuid');
        });

        if (! Schema::hasTable('donation_payments')) {
            Schema::create('donation_payments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->uuid('user_uuid');
                $table->string('yookassa_payment_id')->nullable()->unique();
                $table->unsignedSmallInteger('amount');
                $table->unsignedTinyInteger('months');
                $table->string('status', 20)->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index('user_uuid');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_payments');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ad_free_until');
        });
    }
};
