<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\BatchBayaran;
use App\Models\TransaksiBayaran;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BayaranPostingService
{
    public function __construct(
        protected JadualAnsuranService $jadualService,
        protected TawidhCalculationService $tawidhService,
    ) {}

    /**
     * @param  array{akaun_id:int,jumlah:float,saluran:string,mod?:string,no_rujukan_luar?:string,tarikh_bayaran?:string}  $input
     */
    public function postPayment(array $input, ?BatchBayaran $batch = null): TransaksiBayaran
    {
        $akaun = AkaunPembiayaan::findOrFail($input['akaun_id']);
        $this->jadualService->ensureSchedule($akaun);
        $this->tawidhService->recalculate($akaun);
        $akaun->refresh();

        $jumlah = round((float) $input['jumlah'], 2);
        $remaining = $jumlah;

        $kepadaTawidh = min($remaining, (float) $akaun->tawidh);
        $remaining -= $kepadaTawidh;

        $kepadaTunggakan = min($remaining, (float) $akaun->tunggakan);
        $remaining -= $kepadaTunggakan;

        $beforeInstallments = $remaining;
        $remaining = $this->jadualService->markInstallmentPaid($akaun, $remaining);
        $kepadaAnsuran = round($beforeInstallments - $remaining, 2);

        $tawidhBaru = max(0, round((float) $akaun->tawidh - $kepadaTawidh, 2));
        $tunggakanBaru = max(0, round((float) $akaun->tunggakan - $kepadaTunggakan, 2));
        $bakiAkhirBaru = max(0, round((float) $akaun->baki_akhir - $kepadaAnsuran, 2));

        $status = 'Aktif';
        if ($bakiAkhirBaru <= 0) {
            $status = 'Selesai';
        } elseif ($tunggakanBaru > 0) {
            $status = 'Tunggakan';
        }

        $akaun->update([
            'tawidh' => $tawidhBaru,
            'penalti' => $tawidhBaru,
            'tunggakan' => $tunggakanBaru,
            'baki_akhir' => $bakiAkhirBaru,
            'baki_pokok' => max(0, round((float) $akaun->baki_pokok - ($kepadaAnsuran * 0.85), 2)),
            'baki_keuntungan' => max(0, round((float) $akaun->baki_keuntungan - ($kepadaAnsuran * 0.15), 2)),
            'status' => $status,
        ]);

        $this->tawidhService->recalculate($akaun->fresh());

        return TransaksiBayaran::create([
            'rujukan' => $this->generateRujukan($input['saluran']),
            'akaun_id' => $akaun->id,
            'batch_id' => $batch?->id,
            'jumlah' => $jumlah,
            'kepada_tawidh' => $kepadaTawidh,
            'kepada_tunggakan' => $kepadaTunggakan,
            'kepada_ansuran' => $kepadaAnsuran,
            'saluran' => $input['saluran'],
            'mod' => $input['mod'] ?? ($batch ? 'batch' : 'real_time'),
            'status' => 'berjaya',
            'no_rujukan_luar' => $input['no_rujukan_luar'] ?? null,
            'tarikh_bayaran' => isset($input['tarikh_bayaran'])
                ? Carbon::parse($input['tarikh_bayaran'])
                : now(),
        ]);
    }

    /**
     * @param  array<int, array{no_akaun:string,jumlah:float,no_rujukan_luar?:string}>  $rows
     */
    public function processBatch(string $saluran, array $rows): BatchBayaran
    {
        $batch = BatchBayaran::create([
            'rujukan' => 'BATCH-'.now()->format('YmdHis'),
            'saluran' => $saluran,
            'status' => 'memproses',
            'jumlah_rekod' => count($rows),
        ]);

        $berjaya = 0;
        $gagal = 0;
        $jumlahKeseluruhan = 0.0;

        foreach ($rows as $row) {
            try {
                $akaun = AkaunPembiayaan::where('no_akaun', $row['no_akaun'])->firstOrFail();
                $txn = $this->postPayment([
                    'akaun_id' => $akaun->id,
                    'jumlah' => (float) $row['jumlah'],
                    'saluran' => $saluran,
                    'mod' => 'batch',
                    'no_rujukan_luar' => $row['no_rujukan_luar'] ?? null,
                ], $batch);
                $berjaya++;
                $jumlahKeseluruhan += (float) $txn->jumlah;
            } catch (\Throwable) {
                $gagal++;
            }
        }

        $batch->update([
            'status' => $gagal > 0 && $berjaya === 0 ? 'gagal' : 'selesai',
            'jumlah_berjaya' => $berjaya,
            'jumlah_gagal' => $gagal,
            'jumlah_keseluruhan' => round($jumlahKeseluruhan, 2),
        ]);

        return $batch->fresh();
    }

    private function generateRujukan(string $saluran): string
    {
        $prefix = strtoupper(Str::substr(preg_replace('/[^a-z]/', '', $saluran) ?: 'pay', 0, 3));

        return $prefix.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }
}
