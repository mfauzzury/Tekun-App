<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Kutipan;
use App\Services\SpptViewTransform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KutipanController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 10);
        $q = $request->input('q');
        $status = $request->input('status');

        $query = Kutipan::query();

        if ($q) {
            $query->where(function ($builder) use ($q) {
                $builder->where('rujukan', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%")
                    ->orWhere('no_akaun', 'like', "%{$q}%");
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
            ->map(fn (Kutipan $row) => SpptViewTransform::kutipan($row));

        return $this->sendOk($rows, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil(max($total, 1) / $limit),
        ]);
    }
}
