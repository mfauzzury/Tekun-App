<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovePenjadualanRequest;
use App\Http\Requests\StorePenjadualanRequest;
use App\Http\Traits\ApiResponse;
use App\Models\PermohonanPenjadualan;
use App\Services\PenjadualanService;
use App\Services\SpptViewTransform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenjadualanController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PenjadualanService $penjadualanService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $jenis = $request->input('jenis');
        $status = $request->input('status');

        $query = PermohonanPenjadualan::query()->with('akaun');

        if ($jenis) {
            $query->where('jenis', $jenis);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $rows = $query->orderByDesc('created_at')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn (PermohonanPenjadualan $row) => SpptViewTransform::penjadualan($row));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }

    public function store(StorePenjadualanRequest $request): JsonResponse
    {
        $permohonan = $this->penjadualanService->create($request->validated());

        return $this->sendCreated(SpptViewTransform::penjadualan($permohonan->load('akaun')));
    }

    public function show(int $id): JsonResponse
    {
        $permohonan = PermohonanPenjadualan::with('akaun')->find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan penjadualan not found');
        }

        return $this->sendOk(SpptViewTransform::penjadualan($permohonan));
    }

    public function approve(ApprovePenjadualanRequest $request, int $id): JsonResponse
    {
        $permohonan = PermohonanPenjadualan::find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan penjadualan not found');
        }
        if ($permohonan->status !== 'menunggu') {
            return $this->sendError(409, 'BAD_REQUEST', 'Permohonan telah diproses.');
        }

        $userId = (int) $request->user()->id;
        $tindakan = $request->validated('tindakan');
        $catatan = $request->validated('catatan_pegawai');

        $result = $tindakan === 'lulus'
            ? $this->penjadualanService->approve($permohonan, $userId, $catatan)
            : $this->penjadualanService->reject($permohonan, $userId, $catatan);

        return $this->sendOk(SpptViewTransform::penjadualan($result->load('akaun')));
    }
}
