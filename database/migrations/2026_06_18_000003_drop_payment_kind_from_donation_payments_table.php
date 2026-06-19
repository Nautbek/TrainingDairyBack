<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donation_payments', function (Blueprint $table) {
            if (Schema::hasColumn('donation_payments', 'payment_kind')) {
                $table->dropIndex(['payment_kind']);
                $table->dropColumn('payment_kind');
            }
        });
    }

    public function down(): void
    {
        Schema::table('donation_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('donation_payments', 'payment_kind')) {
                $table->string('payment_kind', 20)->default('donation')->after('app');
                $table->index('payment_kind');
            }
        });
    }
};
