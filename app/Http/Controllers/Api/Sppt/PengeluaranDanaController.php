<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\PengeluaranDana;
use App\Services\SpptViewTransform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengeluaranDanaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $status = $request->input('status');

        $query = PengeluaranDana::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('rujukan', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%")
                    ->orWhere('id_pembiayaan', 'like', "%{$q}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $rows = $query->orderByDesc('created_at')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn (PengeluaranDana $row) => SpptViewTransform::pengeluaran($row));

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
            'menunggu' => PengeluaranDana::where('status', 'Menunggu')->count(),
            'dalamProses' => PengeluaranDana::where('status', 'Dalam Proses')->count(),
            'berjayaBulanIni' => PengeluaranDana::where('status', 'Berjaya')
                ->whereMonth('tarikh_pengeluaran', now()->month)
                ->whereYear('tarikh_pengeluaran', now()->year)
                ->count(),
            'jumlahDanaKeluar' => (float) PengeluaranDana::where('status', 'Berjaya')->sum('jumlah'),
        ]);
    }
}
