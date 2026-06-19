<?php

namespace App\Http\Controllers\Api\TripSplit;

use App\Http\Controllers\Controller;
use App\Http\Requests\TripSplit\UserUuidRequest;
use App\Models\User;
use App\Services\TripSplit\TripSplitSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TripSplitSettlementPdfController extends Controller
{
    public function __invoke(
        string $settlementUuid,
        UserUuidRequest $request,
        TripSplitSettlementService $settlementService,
    ): JsonResponse|StreamedResponse {
        $user = User::query()->where('uuid', $request->validated('uuid'))->first();

        if ($user === null) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $settlement = $settlementService->findForUser($user, $settlementUuid);

        if ($settlement === null || $settlement->pdf_path === null) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        if (! Storage::disk('local')->exists($settlement->pdf_path)) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        $filename = 'tripsplit-'.$settlement->trip_name.'.pdf';

        return Storage::disk('local')->download($settlement->pdf_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
