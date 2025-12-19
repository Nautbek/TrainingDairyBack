<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * Регистрация нового пользователя
     * Генерирует уникальный UUID и сохраняет его в базе
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        try {
            // Генерируем уникальный UUID
            do {
                $uuid = (string) Str::uuid();
                $exists = DB::table('users')->where('uuid', $uuid)->exists();
            } while ($exists);

            // Сохраняем только UUID в таблицу users
            // Используем минимальные значения для обязательных полей
            DB::table('users')->insert([
                'uuid' => $uuid,
                'name' => 'user_' . substr($uuid, 0, 8), // Генерируем имя из UUID
                'email' => $uuid . '@temp.local', // Временный email на основе UUID
                'password' => Hash::make(Str::random(32)), // Случайный пароль
            ]);

            return response()->json([
                'uuid' => $uuid
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error'
            ], 500);
        }
    }
}
