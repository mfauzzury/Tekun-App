<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Jaminan;
use App\Services\SpptViewTransform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JaminanController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $status = $request->input('status');

        $query = Jaminan::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('rujukan', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%")
                    ->orWhere('no_pinjaman', 'like', "%{$q}%");
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
            ->map(fn (Jaminan $row) => SpptViewTransform::jaminan($row));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }

    public function summary(): JsonResponse
    {
        $aktif = Jaminan::where('status', 'Aktif');
        $totalNilai = (float) (clone $aktif)->sum('nilai');

        return $this->sendOk([
            'jumlahAktif' => (clone $aktif)->count(),
            'nilaiJaminan' => $totalNilai,
            'jaminanSah' => Jaminan::whereIn('status', ['Aktif', 'Dilepaskan'])->count(),
            'perluSemakan' => Jaminan::where('status', 'Perlu Semakan')->count(),
        ]);
    }
}
