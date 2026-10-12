<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AkaunPembiayaan;
use App\Services\JadualAnsuranService;
use App\Services\SpptViewTransform;
use App\Services\TawidhCalculationService;
use Illuminate\Http\JsonResponse;

class JadualAnsuranController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected JadualAnsuranService $jadualService,
        protected TawidhCalculationService $tawidhService,
    ) {}

    public function index(int $akaunId): JsonResponse
    {
        $akaun = AkaunPembiayaan::find($akaunId);
        if (! $akaun) {
            return $this->sendError(404, 'NOT_FOUND', 'Akaun not found');
        }

        $this->jadualService->ensureSchedule($akaun);
        $this->tawidhService->recalculate($akaun);

        $rows = $akaun->fresh()->jadualAnsuran
            ->map(fn ($row) => SpptViewTransform::jadualAnsuran($row));

        return $this->sendOk($rows);
    }
}
