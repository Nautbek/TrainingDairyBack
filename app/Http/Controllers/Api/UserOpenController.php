<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserOpenRequest;
use App\Models\UserVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserOpenController extends Controller
{
    /**
     * Сохранить посещение
     * Аналог /api/user_open из Go проекта
     *
     * @param UserOpenRequest $request
     * @return JsonResponse
     */
    public function __invoke(UserOpenRequest $request): JsonResponse
    {
        try {
            $visitIp = $request->ip();
            $app = $request->validated()['app'];
            
            // Получаем user_id по UUID, если передан
            $userId = null;
            $uuid = $request->header('X-User-UUID') ?? $request->input('uuid');
            if ($uuid) {
                $user = DB::table('users')->where('uuid', $uuid)->first();
                $userId = $user?->id;
            }

            UserVisit::incrementVisitCount($visitIp, $app, $userId);

            return response()->json([
                'status' => 'Ok'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error saving visit: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Internal Server Error'
            ], 500);
        }
    }
}
