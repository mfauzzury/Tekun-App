<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SpptDataset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpptSetupController extends Controller
{
    use ApiResponse;

    private const MODULE = 'setup';

    public function index(): JsonResponse
    {
        $categories = config('sppt-setup.categories', []);
        $datasets = SpptDataset::query()
            ->where('module', self::MODULE)
            ->get()
            ->keyBy('dataset_key');

        $result = [];
        foreach ($categories as $key => $meta) {
            $result[] = [
                'key' => $key,
                ...$meta,
                'items' => $this->resolveItems($key, $datasets->get($key)),
            ];
        }

        return $this->sendOk($result);
    }

    public function show(string $key): JsonResponse
    {
        $meta = $this->categoryMeta($key);
        if (! $meta) {
            return $this->sendError(404, 'NOT_FOUND', 'Setup category not found');
        }

        $dataset = SpptDataset::query()
            ->where('module', self::MODULE)
            ->where('dataset_key', $key)
            ->first();

        return $this->sendOk([
            'key' => $key,
            ...$meta,
            'items' => $this->resolveItems($key, $dataset),
        ]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $meta = $this->categoryMeta($key);
        if (! $meta) {
            return $this->sendError(404, 'NOT_FOUND', 'Setup category not found');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.value' => ['required', 'string', 'max:100'],
            'items.*.label' => ['required', 'string', 'max:200'],
            'items.*.color' => ['nullable', 'string', 'max:30'],
            'items.*.active' => ['nullable', 'boolean'],
            'items.*.sort' => ['nullable', 'integer', 'min:0'],
        ]);

        $values = array_column($validated['items'], 'value');
        if (count($values) !== count(array_unique($values))) {
            return $this->sendError(422, 'VALIDATION_ERROR', 'Duplicate status codes are not allowed');
        }

        $items = collect($validated['items'])
            ->map(fn (array $item, int $index) => [
                'value' => $item['value'],
                'label' => $item['label'],
                'color' => $item['color'] ?? 'slate',
                'active' => $item['active'] ?? true,
                'sort' => $item['sort'] ?? ($index + 1),
            ])
            ->sortBy('sort')
            ->values()
            ->all();

        SpptDataset::updateOrCreate(
            ['module' => self::MODULE, 'dataset_key' => $key],
            ['payload' => $items]
        );

        return $this->sendOk([
            'key' => $key,
            ...$meta,
            'items' => $items,
        ]);
    }

    private function categoryMeta(string $key): ?array
    {
        return config("sppt-setup.categories.{$key}");
    }

    private function resolveItems(string $key, ?SpptDataset $dataset): array
    {
        if ($dataset) {
            return $dataset->payload;
        }

        return config("sppt-setup.defaults.{$key}", []);
    }
}
