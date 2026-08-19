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
        if (! Schema::hasTable('training_diary_exercises')) {
            Schema::create('training_diary_exercises', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->uuid('user_uuid');
                $table->string('title');
                $table->dateTime('logged_at');
                $table->unsignedInteger('client_id')->nullable();
                $table->timestamps();

                $table->index('user_uuid');
                $table->index('logged_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_diary_exercises');
    }
};
