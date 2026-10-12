<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use Carbon\Carbon;

class TawidhCalculationService
{
    public function __construct(
        protected NplClassificationService $nplService,
    ) {}

    public function recalculate(AkaunPembiayaan $akaun): AkaunPembiayaan
    {
        $config = config('sppt-repayment.tawidh');
        $hariTangguh = (int) ($config['hari_tangguh'] ?? 7);
        $kadarHarian = (float) ($config['kadar_harian_pct'] ?? 0.0333);
        $maxPctTahunan = (float) ($config['max_pct_tahunan'] ?? 12.0);

        $tunggakan = (float) $akaun->tunggakan;
        if ($tunggakan <= 0) {
            $akaun->update([
                'tawidh' => 0,
                'penalti' => 0,
                'hari_lewat' => 0,
            ]);

            return $this->nplService->classify($akaun->fresh());
        }

        $hariLewat = $this->computeHariLewat($akaun, $hariTangguh);
        if ($hariLewat <= 0) {
            $akaun->update([
                'tawidh' => 0,
                'penalti' => 0,
                'hari_lewat' => 0,
            ]);

            return $this->nplService->classify($akaun->fresh());
        }

        $tawidh = round($tunggakan * ($kadarHarian / 100) * $hariLewat, 2);
        $maxTawidh = round($tunggakan * ($maxPctTahunan / 100), 2);
        $tawidh = min($tawidh, $maxTawidh);

        $akaun->update([
            'tawidh' => $tawidh,
            'penalti' => $tawidh,
            'hari_lewat' => $hariLewat,
        ]);

        if ($akaun->status === 'Aktif' && $tunggakan > 0) {
            $akaun->update(['status' => 'Tunggakan']);
        }

        return $this->nplService->classify($akaun->fresh());
    }

    public function recalculateAll(): int
    {
        $count = 0;
        AkaunPembiayaan::query()
            ->whereIn('status', ['Aktif', 'Tunggakan', 'Moratorium', 'Reschedule', 'NPF'])
            ->where('tunggakan', '>', 0)
            ->each(function (AkaunPembiayaan $akaun) use (&$count) {
                $this->recalculate($akaun);
                $count++;
            });

        return $count;
    }

    private function computeHariLewat(AkaunPembiayaan $akaun, int $hariTangguh): int
    {
        $overdueRow = $akaun->jadualAnsuran()
            ->where('status', 'tertunggak')
            ->orderBy('no_ansuran')
            ->first();

        if (! $overdueRow) {
            $overdueRow = $akaun->jadualAnsuran()
                ->whereIn('status', ['menunggu', 'tertunggak'])
                ->whereDate('tarikh_bayaran', '<', now()->toDateString())
                ->orderBy('no_ansuran')
                ->first();
        }

        if ($overdueRow) {
            $due = Carbon::parse($overdueRow->tarikh_bayaran)->addDays($hariTangguh);
            if (now()->lte($due)) {
                return 0;
            }

            return (int) $due->diffInDays(now());
        }

        if ((float) $akaun->tunggakan > 0) {
            return max(0, 30 - $hariTangguh);
        }

        return 0;
    }
}
