<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nutrition_dishes')) {
            Schema::create('nutrition_dishes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->decimal('water_grams', 8, 2)->default(0);
                // Server-computed on save: sum(ingredients.grams) + water_grams.
                $table->decimal('total_grams', 10, 2)->default(0);
                // Server-computed per 100 g of the finished dish — never trusted from the client.
                $table->decimal('proteins', 8, 2)->default(0);
                $table->decimal('fats', 8, 2)->default(0);
                $table->decimal('carbs', 8, 2)->default(0);
                $table->decimal('calories', 8, 2)->default(0);
                $table->uuid('author_uuid');
                $table->unsignedTinyInteger('status')->default(0);
                $table->timestamps();

                $table->index('author_uuid');
                $table->index('name');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nutrition_dishes')) {
            Schema::dropIfExists('nutrition_dishes');
        }
    }
};
