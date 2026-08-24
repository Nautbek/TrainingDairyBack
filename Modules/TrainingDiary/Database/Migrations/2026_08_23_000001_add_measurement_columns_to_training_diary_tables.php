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
        if (Schema::hasTable('training_diary_exercises') && ! Schema::hasColumn('training_diary_exercises', 'measurement_type')) {
            Schema::table('training_diary_exercises', function (Blueprint $table) {
                $table->string('measurement_type')->default('reps')->after('title');
            });
        }

        if (Schema::hasTable('training_diary_approaches') && ! Schema::hasColumn('training_diary_approaches', 'duration_seconds')) {
            Schema::table('training_diary_approaches', function (Blueprint $table) {
                $table->unsignedInteger('duration_seconds')->nullable()->after('repeat_count');
                $table->decimal('distance_meters', 10, 2)->nullable()->after('duration_seconds');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('training_diary_exercises') && Schema::hasColumn('training_diary_exercises', 'measurement_type')) {
            Schema::table('training_diary_exercises', function (Blueprint $table) {
                $table->dropColumn('measurement_type');
            });
        }

        if (Schema::hasTable('training_diary_approaches')) {
            Schema::table('training_diary_approaches', function (Blueprint $table) {
                if (Schema::hasColumn('training_diary_approaches', 'duration_seconds')) {
                    $table->dropColumn('duration_seconds');
                }
                if (Schema::hasColumn('training_diary_approaches', 'distance_meters')) {
                    $table->dropColumn('distance_meters');
                }
            });
        }
    }
};
