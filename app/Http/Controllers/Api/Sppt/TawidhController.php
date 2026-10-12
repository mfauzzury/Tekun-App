<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AkaunPembiayaan;
use App\Services\KoleksiDemoProfileService;
use App\Services\SpptViewTransform;
use App\Services\TawidhCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TawidhController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TawidhCalculationService $tawidhService,
        protected KoleksiDemoProfileService $demoProfiles,
    ) {}

    public function config(): JsonResponse
    {
        return $this->sendOk(config('sppt-repayment.tawidh'));
    }

    public function recalculateAll(): JsonResponse
    {
        $this->demoProfiles->syncOverdueJadualDates();
        $count = $this->tawidhService->recalculateAll();
        $this->demoProfiles->applyProfiles();

        return $this->sendOk(['akaunDikira' => $count]);
    }

    public function recalculateAccount(int $akaunId): JsonResponse
    {
        $akaun = AkaunPembiayaan::find($akaunId);
        if (! $akaun) {
            return $this->sendError(404, 'NOT_FOUND', 'Akaun not found');
        }

        $updated = $this->tawidhService->recalculate($akaun);

        return $this->sendOk(SpptViewTransform::akaun($updated));
    }

    public function tunggakanSummary(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $query = AkaunPembiayaan::query()
            ->where(function ($builder) {
                $builder->where('tunggakan', '>', 0)
                    ->orWhere('tawidh', '>', 0);
            });

        $total = $query->count();
        $rows = $query->orderByDesc('tunggakan')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function (AkaunPembiayaan $row) {
                $this->tawidhService->recalculate($row);

                return SpptViewTransform::akaun($row->fresh());
            });

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }
}
