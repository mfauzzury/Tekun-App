<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpptCawanganRequest;
use App\Http\Requests\UpdateSpptCawanganRequest;
use App\Http\Traits\ApiResponse;
use App\Models\SpptCawangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CawanganController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $q = $request->input('q');
        $negeri = $request->input('negeri');
        $branchType = $request->input('branch_type');
        $activeOnly = $request->boolean('active_only', false);
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');

        $allowedSort = ['sort_order', 'name', 'negeri', 'branch_type', 'created_at'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'sort_order';
        }

        $query = SpptCawangan::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('locality', 'like', "%{$q}%")
                    ->orWhere('contact_person', 'like', "%{$q}%");
            });
        }

        if ($negeri) {
            $query->where('negeri', $negeri);
        }

        if ($branchType) {
            $query->where('branch_type', $branchType);
        }

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $total = (clone $query)->count();
        $rows = $query->orderBy($sortBy, $sortDir)
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function negeriOptions(): JsonResponse
    {
        $rows = SpptCawangan::query()
            ->whereNotNull('negeri')
            ->where('negeri', '!=', '')
            ->distinct()
            ->orderBy('negeri')
            ->pluck('negeri');

        return $this->sendOk($rows);
    }

    public function store(StoreSpptCawanganRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['branch_type'] = $data['branch_type'] ?? 'cawangan';
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? ((int) SpptCawangan::max('sort_order')) + 1;

        $cawangan = SpptCawangan::create($data);

        return $this->sendCreated($cawangan);
    }

    public function show(int $id): JsonResponse
    {
        $cawangan = SpptCawangan::find($id);

        if (! $cawangan) {
            return $this->sendError(404, 'NOT_FOUND', 'Cawangan not found');
        }

        return $this->sendOk($cawangan);
    }

    public function update(UpdateSpptCawanganRequest $request, int $id): JsonResponse
    {
        $cawangan = SpptCawangan::find($id);

        if (! $cawangan) {
            return $this->sendError(404, 'NOT_FOUND', 'Cawangan not found');
        }

        $cawangan->update($request->validated());

        return $this->sendOk($cawangan->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = SpptCawangan::where('id', $id)->delete();

        if (! $deleted) {
            return $this->sendError(404, 'NOT_FOUND', 'Cawangan not found');
        }

        return $this->sendOk(['success' => true]);
    }
}
