<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Информация об устройстве, с которого создано обращение (модель,
     * версия ОС, оболочка/прошивка, версия приложения и т.п.) — собирается
     * клиентом при создании треда, произвольный текст для чтения админом.
     */
    public function up(): void
    {
        Schema::table('feedback_threads', function (Blueprint $table) {
            $table->text('device_info')->nullable()->after('visit_ip');
        });
    }

    public function down(): void
    {
        Schema::table('feedback_threads', function (Blueprint $table) {
            $table->dropColumn('device_info');
        });
    }
};
