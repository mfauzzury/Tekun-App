<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermohonanRequest;
use App\Http\Requests\UpdatePermohonanRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Permohonan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermohonanController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $status = $request->input('status');

        $query = Permohonan::query()->with('usahawan');

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('no_rujukan', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%");
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
            'jumlah' => Permohonan::count(),
            'dalamProses' => Permohonan::where('status', 'Dalam Proses')->count(),
            'menungguDokumen' => Permohonan::where('status', 'Menunggu Dokumen')->count(),
            'selesaiBulanIni' => Permohonan::where('status', 'Lengkap')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ]);
    }

    public function store(StorePermohonanRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['no_rujukan'])) {
            $data['no_rujukan'] = 'PM-'.now()->format('Y').'-'.str_pad((string) (Permohonan::count() + 1), 4, '0', STR_PAD_LEFT);
        }

        $permohonan = Permohonan::create($data);

        return $this->sendCreated($permohonan);
    }

    public function show(int $id): JsonResponse
    {
        $permohonan = Permohonan::with('usahawan')->find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        return $this->sendOk($permohonan);
    }

    public function update(UpdatePermohonanRequest $request, int $id): JsonResponse
    {
        $permohonan = Permohonan::find($id);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $permohonan->update($request->validated());

        return $this->sendOk($permohonan);
    }

    public function destroy(int $id): JsonResponse
    {
        Permohonan::where('id', $id)->delete();

        return $this->sendOk(['success' => true]);
    }
}
