<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('user_visits')) {
            Schema::table('user_visits', function (Blueprint $table) {
                // Удаляем уникальный индекс на (visit_date, visit_ip)
                $table->dropUnique(['visit_date', 'visit_ip']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_visits')) {
            Schema::table('user_visits', function (Blueprint $table) {
                // Восстанавливаем уникальный индекс
                $table->unique(['visit_date', 'visit_ip']);
            });
        }
    }
};
