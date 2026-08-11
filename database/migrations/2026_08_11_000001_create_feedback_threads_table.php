<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Треды обращений пользователей (фидбек-чат).
     *
     * В отличие от старой user_feedback (которая вперемешку хранит и реальный
     * фидбек, и служебные activity-пинги в Telegram, и не имеет id) — это
     * отдельная таблица только для настоящих обращений с поддержкой чата.
     *
     * Ответы админа пишутся напрямую в feedback_messages через SQL (интерфейса
     * пока нет): INSERT INTO feedback_messages (thread_id, sender, body, created_at)
     * VALUES (:thread_id, 'admin', :body, now()). Закрытие треда:
     * UPDATE feedback_threads SET status = 'closed', updated_at = now() WHERE id = :id.
     */
    public function up(): void
    {
        Schema::create('feedback_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('app', 40);
            $table->string('status', 20)->default('open');
            $table->string('visit_ip', 40)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_threads');
    }
};
