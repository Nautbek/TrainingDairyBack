<?php

namespace App\Services\TripSplit;

use App\Models\TripSplit\Settlement;
use App\Models\User;
use App\Services\TripSplitCreditsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TripSplitSettlementService
{
    public function __construct(
        private readonly TripSplitCreditsService $creditsService,
        private readonly TripSplitSettlementCalculator $calculator,
        private readonly TripSplitReportService $reportService,
    ) {}

    /**
     * @param  array<string, mixed>  $trip
     * @return array<string, mixed>
     */
    public function settle(User $user, array $trip): array
    {
        $clientTripId = (int) ($trip['id'] ?? 0);

        $existing = Settlement::query()
            ->where('user_id', $user->id)
            ->where('client_trip_id', $clientTripId)
            ->first();

        if ($existing !== null) {
            return $this->buildResponse($existing, $user);
        }

        if ($this->creditsService->getCountForUser($user) < 1) {
            throw new InsufficientCreditsException($this->creditsService->getCountForUser($user));
        }

        return DB::transaction(function () use ($user, $trip, $clientTripId): array {
            if (! $this->creditsService->consume($user, 1)) {
                throw new InsufficientCreditsException($this->creditsService->getCountForUser($user));
            }

            $summary = $this->calculator->calculate($trip);

            $settlement = Settlement::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'client_trip_id' => $clientTripId,
                'trip_name' => (string) ($trip['name'] ?? ''),
                'summary' => $summary,
            ]);

            $pdfPath = $this->reportService->generate($settlement, $trip, $summary);
            $settlement->update(['pdf_path' => $pdfPath]);

            return $this->buildResponse($settlement->fresh(), $user);
        });
    }

    public function findForUser(User $user, string $settlementUuid): ?Settlement
    {
        return Settlement::query()
            ->where('uuid', $settlementUuid)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResponse(Settlement $settlement, User $user): array
    {
        $summary = $settlement->summary;

        return [
            'settlement_uuid' => $settlement->uuid,
            'usage_count' => $this->creditsService->getCountForUser($user),
            'trip_name' => $settlement->trip_name,
            'calculation_note' => $summary['calculation_note'] ?? null,
            'participants' => $summary['participants'] ?? [],
            'transactions' => $summary['transactions'] ?? [],
            'transfers' => $summary['transfers'] ?? [],
            'books_balanced' => $summary['books_balanced'] ?? true,
            'unsettled_rub' => $summary['unsettled_rub'] ?? 0.0,
            'pdf_url' => url('/api/tripsplit/settlements/'.$settlement->uuid.'/pdf?uuid='.$user->uuid),
            'created_at' => $settlement->created_at?->toIso8601String(),
        ];
    }
}
