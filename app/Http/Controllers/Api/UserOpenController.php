<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserOpenRequest;
use App\Models\UserVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserOpenController extends Controller
{
    /**
     * Увеличить счетчик посещений
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
            
            UserVisit::incrementVisitCount($visitIp, $app);

            return response()->json([
                'status' => 'Ok'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error increment visit count: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Internal Server Error'
            ], 500);
        }
    }
}
