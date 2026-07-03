<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AkaunPembiayaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AkaunController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $status = $request->input('status');

        $query = AkaunPembiayaan::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('no_akaun', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%")
                    ->orWhere('ic', 'like', "%{$q}%")
                    ->orWhere('ssm', 'like', "%{$q}%")
                    ->orWhere('pukonsa', 'like', "%{$q}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $rows = $query->orderByDesc('created_at')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }

    public function summary(): JsonResponse
    {
        return $this->sendOk([
            'jumlahAktif' => AkaunPembiayaan::where('status', 'Aktif')->count(),
            'bakiTertunggak' => (float) AkaunPembiayaan::sum('tunggakan'),
            'akaunBaruBulanIni' => AkaunPembiayaan::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $akaun = AkaunPembiayaan::find($id);
        if (! $akaun) {
            return $this->sendError(404, 'NOT_FOUND', 'Akaun not found');
        }

        return $this->sendOk($akaun);
    }
}
