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
        // Проверка существования таблицы через прямой SQL запрос
        $tableExists = DB::selectOne(
            "SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'user_visits'
            )"
        );

        if (! $tableExists->exists) {
            Schema::create('user_visits', function (Blueprint $table) {
                $table->string('visit_ip', 40)->nullable();
                $table->date('visit_date');
                $table->integer('visit_count')->default(1);
                $table->string('app', 40)->nullable();
                
                $table->unique(['visit_date', 'visit_ip']);
                $table->index('visit_date');
                $table->index('app');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_visits')) {
            Schema::dropIfExists('user_visits');
        }
    }
};

