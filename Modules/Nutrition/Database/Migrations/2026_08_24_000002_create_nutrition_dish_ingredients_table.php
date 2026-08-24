<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nutrition_dish_ingredients')) {
            Schema::create('nutrition_dish_ingredients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dish_id')->constrained('nutrition_dishes')->cascadeOnDelete();
                // Provenance only, never joined at read time — a dish must remain servable even
                // if the source product later changes, is deleted, or belongs to another user
                // who never had it cached. No FK constraint on purpose.
                $table->uuid('product_uuid')->nullable();
                // Snapshot of the ingredient at the moment the dish was saved.
                $table->string('name');
                $table->decimal('proteins', 8, 2);
                $table->decimal('fats', 8, 2);
                $table->decimal('carbs', 8, 2);
                $table->decimal('grams', 8, 2);
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();

                $table->index('dish_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nutrition_dish_ingredients')) {
            Schema::dropIfExists('nutrition_dish_ingredients');
        }
    }
};
