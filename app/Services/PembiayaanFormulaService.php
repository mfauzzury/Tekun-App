<?php

namespace App\Services;

/**
 * Spec Scenario 2 — flat rate vs reducing balance profit calculation with amortization schedule.
 */
class PembiayaanFormulaService
{
    /**
     * @return array{
     *     input: array<string, float|int>,
     *     flat_rate: array<string, mixed>,
     *     reducing_balance: array<string, mixed>,
     *     perbandingan: array<string, mixed>,
     *     calculated_at: string
     * }
     */
    public function compare(float $jumlahPokok, float $kadarKeuntunganTahunan, int $tempohBulan): array
    {
        $flat = $this->flatRate($jumlahPokok, $kadarKeuntunganTahunan, $tempohBulan);
        $reducing = $this->reducingBalance($jumlahPokok, $kadarKeuntunganTahunan, $tempohBulan);

        return [
            'input' => [
                'jumlah_pokok' => round($jumlahPokok, 2),
                'kadar_keuntungan_tahunan' => round($kadarKeuntunganTahunan, 4),
                'tempoh_bulan' => $tempohBulan,
            ],
            'flat_rate' => $flat,
            'reducing_balance' => $reducing,
            'perbandingan' => [
                'beza_ansuran_bulanan' => round((float) $flat['ansuran_bulanan'] - (float) $reducing['ansuran_bulanan'], 2),
                'beza_jumlah_keuntungan' => round((float) $flat['jumlah_keuntungan'] - (float) $reducing['jumlah_keuntungan'], 2),
                'jumlah_bayaran_balik_flat' => $flat['jumlah_bayaran_balik'],
                'jumlah_bayaran_balik_reducing' => $reducing['jumlah_bayaran_balik'],
                'ringkasan' => $this->comparisonSummary($flat, $reducing),
            ],
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $comparison
     * @return array<string, mixed>
     */
    public function methodResult(array $comparison, string $method): array
    {
        if ($method === 'flat_rate') {
            return is_array($comparison['flat_rate'] ?? null) ? $comparison['flat_rate'] : [];
        }

        return is_array($comparison['reducing_balance'] ?? null) ? $comparison['reducing_balance'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function flatRate(float $jumlahPokok, float $kadarKeuntunganTahunan, int $tempohBulan): array
    {
        $tempohTahun = $tempohBulan / 12;
        $jumlahKeuntungan = round($jumlahPokok * ($kadarKeuntunganTahunan / 100) * $tempohTahun, 2);
        $jumlahBayaranBalik = round($jumlahPokok + $jumlahKeuntungan, 2);
        $ansuranBulanan = $tempohBulan > 0 ? round($jumlahBayaranBalik / $tempohBulan, 2) : 0.0;

        $pokokPerBulan = $tempohBulan > 0 ? round($jumlahPokok / $tempohBulan, 2) : 0.0;
        $keuntunganPerBulan = $tempohBulan > 0 ? round($jumlahKeuntungan / $tempohBulan, 2) : 0.0;

        $rows = [];
        $bakiPokok = $jumlahPokok;
        $bakiBayaranBalik = $jumlahBayaranBalik;

        for ($n = 1; $n <= $tempohBulan; $n++) {
            $pokok = $n === $tempohBulan ? round($bakiPokok, 2) : $pokokPerBulan;
            $keuntungan = $n === $tempohBulan
                ? round($bakiBayaranBalik - $pokok, 2)
                : $keuntunganPerBulan;
            $ansuran = round($pokok + $keuntungan, 2);

            $bakiPokok = round(max(0, $bakiPokok - $pokok), 2);
            $bakiBayaranBalik = round(max(0, $bakiBayaranBalik - $ansuran), 2);

            $rows[] = [
                'no' => $n,
                'pokok' => $pokok,
                'keuntungan' => $keuntungan,
                'ansuran' => $ansuran,
                'baki_pokok' => $bakiPokok,
                'baki_bayaran_balik' => $bakiBayaranBalik,
            ];
        }

        return $this->buildResult(
            'flat_rate',
            'Kadar Rata (Flat Rate)',
            $jumlahPokok,
            $kadarKeuntunganTahunan,
            $tempohBulan,
            $jumlahKeuntungan,
            $jumlahBayaranBalik,
            $ansuranBulanan,
            $rows,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function reducingBalance(float $jumlahPokok, float $kadarKeuntunganTahunan, int $tempohBulan): array
    {
        $kadarBulanan = $kadarKeuntunganTahunan / 100 / 12;
        $ansuranBulanan = $this->monthlyInstallment($jumlahPokok, $kadarBulanan, $tempohBulan);

        $rows = [];
        $bakiPokok = $jumlahPokok;
        $jumlahKeuntungan = 0.0;
        $jumlahBayaranBalik = 0.0;

        for ($n = 1; $n <= $tempohBulan; $n++) {
            $keuntungan = round($bakiPokok * $kadarBulanan, 2);
            $pokok = round($ansuranBulanan - $keuntungan, 2);

            if ($n === $tempohBulan) {
                $pokok = round($bakiPokok, 2);
                $keuntungan = round($ansuranBulanan - $pokok, 2);
            }

            $ansuran = round($pokok + $keuntungan, 2);
            $bakiPokok = round(max(0, $bakiPokok - $pokok), 2);

            $jumlahKeuntungan += $keuntungan;
            $jumlahBayaranBalik += $ansuran;

            $rows[] = [
                'no' => $n,
                'pokok' => $pokok,
                'keuntungan' => $keuntungan,
                'ansuran' => $ansuran,
                'baki_pokok' => $bakiPokok,
                'baki_bayaran_balik' => 0.0,
            ];
        }

        $this->applyRemainingBalances($rows);

        return $this->buildResult(
            'reducing_balance',
            'Baki Menurun (Reducing Balance)',
            $jumlahPokok,
            $kadarKeuntunganTahunan,
            $tempohBulan,
            round($jumlahKeuntungan, 2),
            round($jumlahBayaranBalik, 2),
            $ansuranBulanan,
            $rows,
        );
    }

    private function monthlyInstallment(float $principal, float $monthlyRate, int $months): float
    {
        if ($months < 1) {
            return 0.0;
        }

        if ($monthlyRate <= 0) {
            return round($principal / $months, 2);
        }

        $factor = (1 + $monthlyRate) ** $months;

        return round($principal * $monthlyRate * $factor / ($factor - 1), 2);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function applyRemainingBalances(array &$rows): void
    {
        for ($i = 0; $i < count($rows); $i++) {
            $remaining = 0.0;
            for ($j = $i + 1; $j < count($rows); $j++) {
                $remaining += (float) $rows[$j]['ansuran'];
            }
            $rows[$i]['baki_bayaran_balik'] = round($remaining, 2);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $jadual
     * @return array<string, mixed>
     */
    private function buildResult(
        string $method,
        string $methodLabel,
        float $jumlahPokok,
        float $kadarKeuntunganTahunan,
        int $tempohBulan,
        float $jumlahKeuntungan,
        float $jumlahBayaranBalik,
        float $ansuranBulanan,
        array $jadual,
    ): array {
        $tempohTahun = $tempohBulan > 0 ? $tempohBulan / 12 : 0;
        $keuntunganEfektif = $jumlahPokok > 0 && $tempohTahun > 0
            ? round(($jumlahKeuntungan / $jumlahPokok / $tempohTahun) * 100, 4)
            : 0.0;

        return [
            'method' => $method,
            'method_label' => $methodLabel,
            'jumlah_pokok' => round($jumlahPokok, 2),
            'kadar_keuntungan_tahunan' => round($kadarKeuntunganTahunan, 4),
            'tempoh_bulan' => $tempohBulan,
            'jumlah_keuntungan' => round($jumlahKeuntungan, 2),
            'jumlah_bayaran_balik' => round($jumlahBayaranBalik, 2),
            'ansuran_bulanan' => round($ansuranBulanan, 2),
            'keuntungan_efektif_pct' => $keuntunganEfektif,
            'jadual' => $jadual,
        ];
    }

    /**
     * @param  array<string, mixed>  $flat
     * @param  array<string, mixed>  $reducing
     */
    private function comparisonSummary(array $flat, array $reducing): string
    {
        $bezaAnsuran = (float) $flat['ansuran_bulanan'] - (float) $reducing['ansuran_bulanan'];
        $bezaKeuntungan = (float) $flat['jumlah_keuntungan'] - (float) $reducing['jumlah_keuntungan'];

        if (abs($bezaAnsuran) < 0.01 && abs($bezaKeuntungan) < 0.01) {
            return 'Kedua-dua kaedah menghasilkan ansuran dan jumlah keuntungan yang hampir sama.';
        }

        if ($bezaAnsuran > 0) {
            return sprintf(
                'Kadar rata lebih tinggi RM %.2f sebulan (jumlah keuntungan lebih RM %.2f). Baki menurun lebih menguntungkan pemohon.',
                $bezaAnsuran,
                max(0, $bezaKeuntungan),
            );
        }

        return sprintf(
            'Baki menurun lebih tinggi RM %.2f sebulan. Kadar rata lebih rendah untuk pemohon (jumlah keuntungan kurang RM %.2f).',
            abs($bezaAnsuran),
            abs(min(0, $bezaKeuntungan)),
        );
    }
}
