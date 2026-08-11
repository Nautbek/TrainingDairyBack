<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Сообщения внутри треда обращения. sender = 'user' | 'admin'.
     * Только created_at — сообщения неизменяемы, редактирование не нужно.
     */
    public function up(): void
    {
        Schema::create('feedback_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('feedback_threads')->onDelete('cascade');
            $table->string('sender', 10);
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();

            $table->index('thread_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_messages');
    }
};
