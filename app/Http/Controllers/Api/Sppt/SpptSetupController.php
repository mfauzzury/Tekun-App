<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateHardRulesSetupRequest;
use App\Http\Traits\ApiResponse;
use App\Models\SpptDataset;
use App\Services\HardRuleCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpptSetupController extends Controller
{
    use ApiResponse;

    private const MODULE = 'setup';

    public function __construct(
        protected HardRuleCheckService $hardRuleCheck,
    ) {}

    public function index(): JsonResponse
    {
        $categories = config('sppt-setup.categories', []);
        $datasets = SpptDataset::query()
            ->where('module', self::MODULE)
            ->get()
            ->keyBy('dataset_key');

        $result = [];
        foreach ($categories as $key => $meta) {
            $result[] = $this->formatCategory($key, $meta, $datasets->get($key));
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

        return $this->sendOk($this->formatCategory($key, $meta, $dataset));
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $meta = $this->categoryMeta($key);
        if (! $meta) {
            return $this->sendError(404, 'NOT_FOUND', 'Setup category not found');
        }

        if (($meta['type'] ?? null) === 'hard_rules') {
            return $this->updateHardRules($key, $meta, $request);
        }

        $isKnowledge = ($meta['type'] ?? null) === 'knowledge';
        $labelMax = $isKnowledge ? 2000 : 200;

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.value' => ['required', 'string', 'max:100'],
            'items.*.label' => ['required', 'string', "max:{$labelMax}"],
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

    private function updateHardRules(string $key, array $meta, Request $request): JsonResponse
    {
        $form = UpdateHardRulesSetupRequest::createFrom($request);
        $form->setContainer(app())->setRedirector(app('redirect'));
        $form->validateResolved();

        $validated = $form->validated();
        $codes = array_column($validated['rules'], 'code');
        if (count($codes) !== count(array_unique($codes))) {
            return $this->sendError(422, 'VALIDATION_ERROR', 'Duplicate rule codes are not allowed');
        }

        $payload = $this->hardRuleCheck->normalizeConfig($validated);

        SpptDataset::updateOrCreate(
            ['module' => self::MODULE, 'dataset_key' => $key],
            ['payload' => $payload]
        );

        return $this->sendOk([
            'key' => $key,
            ...$meta,
            'hardRules' => $payload,
        ]);
    }

    private function categoryMeta(string $key): ?array
    {
        return config("sppt-setup.categories.{$key}");
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCategory(string $key, array $meta, ?SpptDataset $dataset): array
    {
        if (($meta['type'] ?? null) === 'hard_rules') {
            $hardRules = $dataset && is_array($dataset->payload) && isset($dataset->payload['rules'])
                ? $this->hardRuleCheck->normalizeConfig($dataset->payload)
                : $this->hardRuleCheck->normalizeConfig(
                    config("sppt-setup.hard_rules_defaults.{$key}", ['active' => true, 'rules' => []])
                );

            return [
                'key' => $key,
                ...$meta,
                'hardRules' => $hardRules,
                'items' => [],
            ];
        }

        return [
            'key' => $key,
            ...$meta,
            'items' => $this->resolveItems($key, $dataset),
        ];
    }

    private function resolveItems(string $key, ?SpptDataset $dataset): array
    {
        if ($dataset) {
            return $dataset->payload;
        }

        return config("sppt-setup.defaults.{$key}", []);
    }
}
