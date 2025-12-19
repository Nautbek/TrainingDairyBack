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
            // Удаляем constraint, который автоматически удалит связанный индекс
            // В PostgreSQL уникальный constraint называется user_visits_visit_date_visit_ip_key
            DB::statement('ALTER TABLE user_visits DROP CONSTRAINT IF EXISTS user_visits_visit_date_visit_ip_key');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_visits')) {
            Schema::table('user_visits', function (Blueprint $table) {
                // Восстанавливаем уникальный constraint (который создаст индекс)
                $table->unique(['visit_date', 'visit_ip']);
            });
        }
    }
};
