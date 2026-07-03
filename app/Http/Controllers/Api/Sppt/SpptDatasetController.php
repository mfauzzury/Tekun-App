<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SpptDataset;
use Illuminate\Http\JsonResponse;

class SpptDatasetController extends Controller
{
    use ApiResponse;

    public function show(string $module, string $key): JsonResponse
    {
        $dataset = SpptDataset::query()
            ->where('module', $module)
            ->where('dataset_key', $key)
            ->first();

        if (! $dataset) {
            return $this->sendError(404, 'NOT_FOUND', 'Dataset not found');
        }

        return $this->sendOk($dataset->payload);
    }

    public function module(string $module): JsonResponse
    {
        $datasets = SpptDataset::query()
            ->where('module', $module)
            ->get()
            ->mapWithKeys(fn (SpptDataset $row) => [$row->dataset_key => $row->payload]);

        return $this->sendOk($datasets);
    }
}
