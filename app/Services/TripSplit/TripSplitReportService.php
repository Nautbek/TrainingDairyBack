<?php

namespace App\Services\TripSplit;

use App\Models\TripSplit\Settlement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TripSplitReportService
{
    /**
     * @param  array<string, mixed>  $trip
     * @param  array<string, mixed>  $summary
     */
    public function generate(Settlement $settlement, array $trip, array $summary): string
    {
        $pdf = Pdf::loadView('tripsplit.settlement-report', [
            'settlement' => $settlement,
            'trip' => $trip,
            'summary' => $summary,
        ])->setPaper('a4');

        $relativePath = sprintf(
            'tripsplit/reports/%s/%s.pdf',
            $settlement->user_id,
            $settlement->uuid,
        );

        Storage::disk('local')->put($relativePath, $pdf->output());

        return $relativePath;
    }
}
