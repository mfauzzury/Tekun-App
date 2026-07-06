<?php

namespace App\Services;

use App\Models\SpptDataset;

class SpptSetupService
{
    private const MODULE = 'setup';

    /**
     * @return list<array{value: string, label: string, color?: string, active?: bool, sort?: int}>
     */
    public function resolveItems(string $key): array
    {
        $dataset = SpptDataset::query()
            ->where('module', self::MODULE)
            ->where('dataset_key', $key)
            ->first();

        if ($dataset) {
            return $dataset->payload;
        }

        return config("sppt-setup.defaults.{$key}", []);
    }

    /**
     * Format setup knowledge items as plain text for AI system prompts.
     */
    public function formatKnowledgeForPrompt(string $key): string
    {
        $meta = config("sppt-setup.categories.{$key}", []);
        $items = collect($this->resolveItems($key))
            ->filter(fn (array $item) => $item['active'] ?? true)
            ->sortBy(fn (array $item) => $item['sort'] ?? 0)
            ->values();

        $title = is_string($meta['description'] ?? null)
            ? $meta['description']
            : (is_string($meta['label'] ?? null) ? $meta['label'] : $key);

        $lines = [$title.':'];

        foreach ($items as $item) {
            $value = trim((string) ($item['value'] ?? ''));
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $lines[] = $value !== '' ? "{$value}. {$label}" : "- {$label}";
        }

        return implode("\n", $lines);
    }
}
