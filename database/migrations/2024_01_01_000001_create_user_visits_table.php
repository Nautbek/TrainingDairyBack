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
        if (! Schema::hasTable('user_visits')) {
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

