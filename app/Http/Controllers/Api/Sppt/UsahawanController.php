<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsahawanRequest;
use App\Http\Requests\UpdateUsahawanRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Usahawan;
use App\Services\SpptViewTransform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsahawanController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $status = $request->input('status');

        $query = Usahawan::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('nama', 'like', "%{$q}%")
                    ->orWhere('no_ic', 'like', "%{$q}%")
                    ->orWhere('no_usahawan', 'like', "%{$q}%");
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

        if ($request->boolean('rekod')) {
            $rows = $rows->map(fn (Usahawan $row) => SpptViewTransform::usahawanRekod([
                'id' => $row->id,
                'noUsahawan' => $row->no_usahawan,
                'nama' => $row->nama,
                'noIc' => $row->no_ic,
                'negeri' => $row->negeri,
                'status' => $row->status,
            ]));
        }

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
            'jumlah' => Usahawan::count(),
            'aktif' => Usahawan::where('status', 'Aktif')->count(),
            'pembiayaanBerjalan' => Usahawan::where('status', 'Aktif')->count(),
            'daftarBulanIni' => Usahawan::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ]);
    }

    public function store(StoreUsahawanRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['no_usahawan'])) {
            $data['no_usahawan'] = 'USW-'.str_pad((string) (Usahawan::count() + 1), 5, '0', STR_PAD_LEFT);
        }

        $usahawan = Usahawan::create($data);

        return $this->sendCreated($usahawan);
    }

    public function show(int $id): JsonResponse
    {
        $usahawan = Usahawan::find($id);
        if (! $usahawan) {
            return $this->sendError(404, 'NOT_FOUND', 'Usahawan not found');
        }

        return $this->sendOk($usahawan);
    }

    public function update(UpdateUsahawanRequest $request, int $id): JsonResponse
    {
        $usahawan = Usahawan::find($id);
        if (! $usahawan) {
            return $this->sendError(404, 'NOT_FOUND', 'Usahawan not found');
        }

        $usahawan->update($request->validated());

        return $this->sendOk($usahawan);
    }

    public function destroy(int $id): JsonResponse
    {
        Usahawan::where('id', $id)->delete();

        return $this->sendOk(['success' => true]);
    }
}
