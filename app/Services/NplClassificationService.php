<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;

class NplClassificationService
{
    public function classify(AkaunPembiayaan $akaun): AkaunPembiayaan
    {
        $config = config('sppt-collection.npl');
        $perhatianMin = (int) ($config['perhatian_khusus_min_hari'] ?? 1);
        $nplMin = (int) ($config['tidak_lancar_min_hari'] ?? 91);

        $hariLewat = (int) ($akaun->hari_lewat ?? 0);
        $tunggakan = (float) $akaun->tunggakan;

        $klasifikasi = 'lancar';
        if ($tunggakan > 0 && $hariLewat >= $nplMin) {
            $klasifikasi = 'tidak_lancar';
        } elseif ($tunggakan > 0 && $hariLewat >= $perhatianMin) {
            $klasifikasi = 'perhatian_khusus';
        }

        $updates = [
            'klasifikasi_npl' => $klasifikasi,
            'klasifikasi_dikemaskini_pada' => now(),
        ];

        if ($klasifikasi === 'tidak_lancar' && ! in_array($akaun->status, ['Selesai', 'Batal', 'Dibekukan'], true)) {
            $updates['status'] = 'NPF';
        } elseif ($klasifikasi === 'lancar' && in_array($akaun->status, ['Tunggakan', 'NPF'], true)) {
            $updates['status'] = 'Aktif';
        } elseif ($klasifikasi === 'perhatian_khusus' && $akaun->status === 'Aktif' && $tunggakan > 0) {
            $updates['status'] = 'Tunggakan';
        }

        $akaun->update($updates);

        return $akaun->fresh();
    }

    public function classifyAll(): int
    {
        $count = 0;
        AkaunPembiayaan::query()
            ->whereNotIn('status', ['Selesai', 'Batal'])
            ->each(function (AkaunPembiayaan $akaun) use (&$count) {
                $this->classify($akaun);
                $count++;
            });

        return $count;
    }

    public function label(string $klasifikasi): string
    {
        return config("sppt-collection.npl.labels.{$klasifikasi}", $klasifikasi);
    }

    /**
     * @return array<int, array{kategori: string, bil: int, amaun: float, peratus: float}>
     */
    public function agingSummary(): array
    {
        $active = AkaunPembiayaan::query()
            ->whereNotIn('status', ['Selesai', 'Batal'])
            ->get();

        $total = $active->count();
        $buckets = [
            'lancar' => ['kategori' => 'Lancar', 'bil' => 0, 'amaun' => 0.0],
            'perhatian_khusus' => ['kategori' => 'Perhatian Khusus (1–90 hari)', 'bil' => 0, 'amaun' => 0.0],
            'tidak_lancar' => ['kategori' => 'Tidak Lancar / NPL (>90 hari)', 'bil' => 0, 'amaun' => 0.0],
        ];

        foreach ($active as $akaun) {
            $key = $akaun->klasifikasi_npl ?? 'lancar';
            if (! isset($buckets[$key])) {
                $key = 'lancar';
            }
            $buckets[$key]['bil']++;
            $buckets[$key]['amaun'] += (float) $akaun->tunggakan;
        }

        return array_values(array_map(function (array $row) use ($total) {
            $row['peratus'] = $total > 0 ? round(($row['bil'] / $total) * 100, 1) : 0.0;

            return $row;
        }, $buckets));
    }
}
