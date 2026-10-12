<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AkaunPembiayaan;
use App\Models\NotisKutipan;
use App\Services\DunningService;
use App\Services\KoleksiDemoProfileService;
use App\Services\NplClassificationService;
use App\Services\SpptViewTransform;
use App\Services\TawidhCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class KoleksiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NplClassificationService $nplService,
        protected DunningService $dunningService,
        protected TawidhCalculationService $tawidhService,
        protected KoleksiDemoProfileService $demoProfiles,
    ) {}

    public function config(): JsonResponse
    {
        return $this->sendOk([
            'npl' => config('sppt-collection.npl'),
            'dunning' => config('sppt-collection.dunning'),
        ]);
    }

    public function klasifikasiIndex(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $q = $request->input('q');
        $klasifikasi = $request->input('klasifikasi');

        $query = AkaunPembiayaan::query()
            ->whereNotIn('status', ['Selesai', 'Batal']);

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('no_akaun', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%");
            });
        }

        if ($klasifikasi) {
            $query->where('klasifikasi_npl', $klasifikasi);
        }

        $total = $query->count();
        $rows = $query->orderByDesc('hari_lewat')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn (AkaunPembiayaan $row) => SpptViewTransform::akaun($row));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }

    public function klasifikasiSummary(): JsonResponse
    {
        return $this->sendOk($this->nplService->agingSummary());
    }

    public function recalculateKlasifikasi(): JsonResponse
    {
        $this->demoProfiles->syncOverdueJadualDates();

        $count = 0;
        AkaunPembiayaan::query()
            ->whereNotIn('status', ['Selesai', 'Batal'])
            ->each(function (AkaunPembiayaan $akaun) use (&$count) {
                if ((float) $akaun->tunggakan > 0) {
                    $this->tawidhService->recalculate($akaun);
                } else {
                    $this->nplService->classify($akaun);
                }
                $count++;
            });

        $this->demoProfiles->applyProfiles();

        return $this->sendOk(['akaunDikemaskini' => $count]);
    }

    public function notisIndex(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $q = $request->input('q');
        $status = $request->input('status');
        $tahap = $request->input('tahap');

        $query = NotisKutipan::query()->with('akaun');

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('rujukan', 'like', "%{$q}%")
                    ->orWhereHas('akaun', function ($akaunQuery) use ($q) {
                        $akaunQuery->where('no_akaun', 'like', "%{$q}%")
                            ->orWhere('nama', 'like', "%{$q}%");
                    });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($tahap) {
            $query->where('tahap', $tahap);
        }

        $total = $query->count();
        $rows = $query->orderByDesc('tarikh_dijadual')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn (NotisKutipan $row) => SpptViewTransform::notisKutipan($row));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }

    public function processDunning(): JsonResponse
    {
        $stats = $this->dunningService->processAll();

        return $this->sendOk($stats);
    }

    public function notisPdf(int $id): Response|JsonResponse
    {
        $notis = NotisKutipan::with('akaun')->find($id);
        if (! $notis) {
            return $this->sendError(404, 'NOT_FOUND', 'Notis not found');
        }

        if ($notis->jenis !== 'surat') {
            return $this->sendError(400, 'BAD_REQUEST', 'PDF hanya tersedia untuk notis surat');
        }

        $html = View::make('pdf.notis-peringatan', $this->dunningService->buildLetterData($notis))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$notis->rujukan.'.html"',
        ]);
    }
}
