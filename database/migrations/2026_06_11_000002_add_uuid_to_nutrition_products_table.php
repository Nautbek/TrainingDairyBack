<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutrition_products', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        DB::table('nutrition_products')
            ->whereNull('uuid')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id): void {
                DB::table('nutrition_products')
                    ->where('id', $id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE nutrition_products ALTER COLUMN uuid SET NOT NULL');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE nutrition_products MODIFY uuid CHAR(36) NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('nutrition_products', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
