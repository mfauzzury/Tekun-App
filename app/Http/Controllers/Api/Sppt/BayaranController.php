<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBatchBayaranRequest;
use App\Http\Requests\StoreBayaranRequest;
use App\Http\Traits\ApiResponse;
use App\Models\AkaunPembiayaan;
use App\Models\BatchBayaran;
use App\Models\TransaksiBayaran;
use App\Services\BayaranPostingService;
use App\Services\SpptViewTransform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BayaranController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BayaranPostingService $bayaranService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $saluran = $request->input('saluran');
        $mod = $request->input('mod');

        $query = TransaksiBayaran::query()->with('akaun');

        if ($saluran) {
            $query->where('saluran', $saluran);
        }
        if ($mod) {
            $query->where('mod', $mod);
        }

        $total = $query->count();
        $rows = $query->orderByDesc('tarikh_bayaran')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn (TransaksiBayaran $row) => SpptViewTransform::transaksiBayaran($row));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }

    public function store(StoreBayaranRequest $request): JsonResponse
    {
        $txn = $this->bayaranService->postPayment($request->validated());

        return $this->sendCreated(SpptViewTransform::transaksiBayaran($txn->load('akaun')));
    }

    public function batch(StoreBatchBayaranRequest $request): JsonResponse
    {
        $data = $request->validated();
        $batch = $this->bayaranService->processBatch($data['saluran'], $data['rows']);

        return $this->sendCreated(SpptViewTransform::batchBayaran($batch));
    }

    public function batches(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);

        $query = BatchBayaran::query();
        $total = $query->count();
        $rows = $query->orderByDesc('created_at')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn (BatchBayaran $row) => SpptViewTransform::batchBayaran($row));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }

    public function saluran(): JsonResponse
    {
        return $this->sendOk(config('sppt-repayment.saluran_bayaran'));
    }
}
