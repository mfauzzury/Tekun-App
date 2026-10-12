<?php

namespace App\Services;

use App\Models\Permohonan;

class PermohonanReferenceService
{
    /**
     * Generate the next PM-YYYY-NNNN reference, skipping non-numeric demo/workflow refs
     * and any gaps or collisions already present in permohonan.
     */
    public function generateNextNoRujukan(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = 'PM-'.$year.'-';

        $maxSeq = Permohonan::query()
            ->where('no_rujukan', 'like', $prefix.'%')
            ->pluck('no_rujukan')
            ->map(fn (string $ref) => $this->numericSequence($ref, $prefix))
            ->filter(fn (?int $seq) => $seq !== null)
            ->max();

        $seq = ($maxSeq ?? 0) + 1;

        do {
            $candidate = $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $seq++;
        } while (Permohonan::query()->where('no_rujukan', $candidate)->exists());

        return $candidate;
    }

    private function numericSequence(string $reference, string $prefix): ?int
    {
        if (! str_starts_with($reference, $prefix)) {
            return null;
        }

        $suffix = substr($reference, strlen($prefix));
        if ($suffix === '' || ! ctype_digit($suffix)) {
            return null;
        }

        return (int) $suffix;
    }
}
