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
        if (! Schema::hasTable('training_diary_approaches')) {
            Schema::create('training_diary_approaches', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('exercise_id')
                    ->constrained('training_diary_exercises')
                    ->cascadeOnDelete();
                $table->decimal('weight', 8, 2);
                $table->unsignedInteger('repeat_count');
                $table->text('comment')->nullable();
                $table->unsignedInteger('client_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_diary_approaches');
    }
};
