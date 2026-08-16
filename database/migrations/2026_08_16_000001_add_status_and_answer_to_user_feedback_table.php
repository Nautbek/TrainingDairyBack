<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица user_feedback изначально не имела первичного ключа (см.
 * app/Models/UserFeedback.php — legacy-таблица без id, унаследованная из
 * Go-бэкенда). Чтобы админки могли адресно менять статус конкретного
 * отзыва и хранить ответ на него, добавляем id + поля статуса/ответа.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_feedback', function (Blueprint $table) {
            if (! Schema::hasColumn('user_feedback', 'id')) {
                $table->id();
            }

            if (! Schema::hasColumn('user_feedback', 'status')) {
                $table->string('status', 20)->default('new')->index();
            }

            if (! Schema::hasColumn('user_feedback', 'admin_answer')) {
                $table->text('admin_answer')->nullable();
            }

            if (! Schema::hasColumn('user_feedback', 'answered_at')) {
                $table->timestamp('answered_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_feedback', function (Blueprint $table) {
            if (Schema::hasColumn('user_feedback', 'answered_at')) {
                $table->dropColumn('answered_at');
            }

            if (Schema::hasColumn('user_feedback', 'admin_answer')) {
                $table->dropColumn('admin_answer');
            }

            if (Schema::hasColumn('user_feedback', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('user_feedback', 'id')) {
                $table->dropColumn('id');
            }
        });
    }
};
