<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('user_visits')) {
            // Удаляем уникальный индекс по точному имени
            // В PostgreSQL уникальный индекс называется user_visits_visit_date_visit_ip_key
            DB::statement('DROP INDEX IF EXISTS user_visits_visit_date_visit_ip_key');
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
