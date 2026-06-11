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
        if (! Schema::hasTable('nutrition_products')) {
            Schema::create('nutrition_products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('proteins', 8, 2)->default(0);
                $table->decimal('fats', 8, 2)->default(0);
                $table->decimal('carbs', 8, 2)->default(0);
                $table->decimal('calories', 8, 2)->default(0);
                $table->uuid('author_uuid');
                $table->unsignedTinyInteger('status')->default(0);
                $table->timestamps();

                $table->index('author_uuid');
                $table->index('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nutrition_products')) {
            Schema::dropIfExists('nutrition_products');
        }
    }
};
