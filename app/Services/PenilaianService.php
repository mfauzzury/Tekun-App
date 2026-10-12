<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\Permohonan;
use Carbon\Carbon;

class PenilaianService
{
    public function __construct(
        protected PembiayaanFormulaService $formulaService,
        protected AkaunPembiayaanService $akaunService,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array{permohonan: Permohonan, akaun: AkaunPembiayaan|null}
     */
    public function save(Permohonan $permohonan, array $input): array
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $comparison = $this->buildFormulaComparison($permohonan, $input);
        $kaedah = $this->resolveKaedah($input, $comparison);
        $selected = $this->formulaService->methodResult($comparison, $kaedah);

        $jumlahDiluluskan = $this->parseAmount(
            $input['cadangan_pembiayaan'] ?? null,
            (float) ($permohonan->jumlah_permohonan ?? 0),
        );

        $penilaian = [
            'keputusan' => (string) $input['keputusan'],
            'skor_kredit' => (string) ($input['skor_kredit'] ?? ''),
            'cadangan_pembiayaan' => (string) ($input['cadangan_pembiayaan'] ?? ''),
            'jumlah_diluluskan' => $jumlahDiluluskan,
            'cadangan_jenis_pembiayaan' => (string) ($input['cadangan_jenis_pembiayaan'] ?? ''),
            'catatan' => (string) ($input['catatan'] ?? ''),
            'kadar_keuntungan' => (float) ($comparison['input']['kadar_keuntungan_tahunan'] ?? 4),
            'tempoh_pembiayaan' => (int) ($comparison['input']['tempoh_bulan'] ?? 36),
            'kaedah_pengiraan' => $kaedah,
            'kaedah_label' => (string) ($selected['method_label'] ?? ''),
            'ansuran_bulanan' => (float) ($selected['ansuran_bulanan'] ?? 0),
            'disimpan_pada' => now()->toIso8601String(),
        ];

        $details['penilaian'] = $penilaian;
        $details['formula_pembiayaan'] = [
            'kaedah_pengiraan' => $kaedah,
            'kaedah_label' => (string) ($selected['method_label'] ?? ''),
            'kadar_keuntungan_tahunan' => (float) ($comparison['input']['kadar_keuntungan_tahunan'] ?? 0),
            'tempoh_bulan' => (int) ($comparison['input']['tempoh_bulan'] ?? 0),
            'jumlah_pokok' => (float) ($comparison['input']['jumlah_pokok'] ?? 0),
            'ansuran_bulanan' => (float) ($selected['ansuran_bulanan'] ?? 0),
            'jumlah_keuntungan' => (float) ($selected['jumlah_keuntungan'] ?? 0),
            'jumlah_bayaran_balik' => (float) ($selected['jumlah_bayaran_balik'] ?? 0),
            'perbandingan' => $comparison['perbandingan'] ?? [],
            'jadual' => $selected['jadual'] ?? [],
            'calculated_at' => $comparison['calculated_at'] ?? now()->toIso8601String(),
        ];

        $status = match ((string) $input['keputusan']) {
            'Lulus' => 'Diluluskan',
            'Tolak' => 'Ditolak',
            default => $permohonan->status,
        };

        $permohonan->update([
            'status' => $status,
            'details' => $details,
        ]);

        $akaun = null;
        if ((string) $input['keputusan'] === 'Lulus' && (bool) ($input['buka_akaun'] ?? true)) {
            $akaun = $this->akaunService->openFromPermohonan($permohonan->fresh());
        }

        return [
            'permohonan' => $permohonan->fresh(),
            'akaun' => $akaun,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildFormulaComparison(Permohonan $permohonan, array $input): array
    {
        $defaults = config('sppt-repayment.formula', []);
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $existingPenilaian = is_array($details['penilaian'] ?? null) ? $details['penilaian'] : [];

        $jumlahPokok = $this->parseAmount(
            $input['cadangan_pembiayaan'] ?? null,
            $this->parseAmount(
                $existingPenilaian['cadangan_pembiayaan'] ?? null,
                (float) ($permohonan->jumlah_permohonan ?? 0),
            ),
        );

        $kadar = (float) ($input['kadar_keuntungan_tahunan']
            ?? $existingPenilaian['kadar_keuntungan']
            ?? $defaults['default_kadar_keuntungan_tahunan']
            ?? 4);

        $tempoh = (int) ($input['tempoh_pembiayaan']
            ?? $existingPenilaian['tempoh_pembiayaan']
            ?? $details['tempoh_pembiayaan']
            ?? $defaults['default_tempoh_bulan']
            ?? 36);

        return $this->formulaService->compare($jumlahPokok, $kadar, max(1, $tempoh));
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $comparison
     */
    private function resolveKaedah(array $input, array $comparison): string
    {
        $kaedah = (string) ($input['kaedah_pengiraan'] ?? 'reducing_balance');

        return in_array($kaedah, ['flat_rate', 'reducing_balance'], true)
            ? $kaedah
            : 'reducing_balance';
    }

    private function parseAmount(mixed $value, float $fallback = 0.0): float
    {
        if ($value === null || $value === '') {
            return max(0, $fallback);
        }

        if (is_numeric($value)) {
            return max(0, (float) $value);
        }

        $clean = preg_replace('/[^0-9.]/', '', (string) $value);

        return $clean !== '' ? max(0, (float) $clean) : max(0, $fallback);
    }
}
