<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\JadualAnsuran;
use Carbon\Carbon;

class JadualAnsuranService
{
    public function ensureSchedule(AkaunPembiayaan $akaun): void
    {
        if ($akaun->jadualAnsuran()->exists()) {
            return;
        }

        $this->generateSchedule($akaun);
    }

    public function generateSchedule(AkaunPembiayaan $akaun, ?int $tempohBulan = null): void
    {
        $tempoh = $tempohBulan ?? $this->inferTempohBulan($akaun);
        if ($tempoh < 1 || (float) $akaun->bayaran_bulanan <= 0) {
            return;
        }

        $akaun->update(['tempoh_bulan' => $tempoh]);

        $akaun->jadualAnsuran()->delete();

        $totalRepay = (float) $akaun->bayaran_bulanan * $tempoh;
        $pokokPer = round((float) $akaun->jumlah_pembiayaan / $tempoh, 2);
        $keuntunganPer = round((float) $akaun->bayaran_bulanan - $pokokPer, 2);
        $start = $akaun->tarikh_mula ?? now()->startOfMonth();

        $rows = [];
        for ($n = 1; $n <= $tempoh; $n++) {
            $baki = max(0, round($totalRepay - ($n * (float) $akaun->bayaran_bulanan), 2));
            $tarikh = Carbon::parse($start)->addMonths($n);
            $status = 'menunggu';
            if ($tarikh->isPast() && (float) $akaun->tunggakan > 0 && $n === $this->firstUnpaidInstallmentNo($akaun, $tempoh)) {
                $status = 'tertunggak';
            }

            $rows[] = [
                'akaun_id' => $akaun->id,
                'no_ansuran' => $n,
                'tarikh_bayaran' => $tarikh->toDateString(),
                'jumlah_ansuran' => $akaun->bayaran_bulanan,
                'pokok' => $pokokPer,
                'keuntungan' => $keuntunganPer,
                'baki' => $baki,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        JadualAnsuran::insert($rows);
    }

    /**
     * @param  array<string, mixed>  $formula
     */
    public function generateFromFormula(AkaunPembiayaan $akaun, array $formula, ?Carbon $start = null): void
    {
        $jadual = is_array($formula['jadual'] ?? null) ? $formula['jadual'] : [];
        if ($jadual === []) {
            $this->generateSchedule($akaun);

            return;
        }

        $akaun->jadualAnsuran()->delete();

        $startDate = $start ?? ($akaun->tarikh_mula ? Carbon::parse($akaun->tarikh_mula) : now()->startOfMonth());
        $rows = [];

        foreach ($jadual as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $no = (int) ($item['no'] ?? ($index + 1));
            $tarikh = $startDate->copy()->addMonths($no);
            $status = 'menunggu';
            if ($tarikh->isPast() && (float) $akaun->tunggakan > 0 && $no === 1) {
                $status = 'tertunggak';
            }

            $rows[] = [
                'akaun_id' => $akaun->id,
                'no_ansuran' => $no,
                'tarikh_bayaran' => $tarikh->toDateString(),
                'jumlah_ansuran' => (float) ($item['ansuran'] ?? $akaun->bayaran_bulanan),
                'pokok' => (float) ($item['pokok'] ?? 0),
                'keuntungan' => (float) ($item['keuntungan'] ?? 0),
                'baki' => (float) ($item['baki_bayaran_balik'] ?? 0),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            JadualAnsuran::insert($rows);
        }
    }

    public function applyMoratorium(AkaunPembiayaan $akaun, int $bulan, Carbon $kuatKuasa): void
    {
        $this->ensureSchedule($akaun);

        $moratoriumTamat = $kuatKuasa->copy()->addMonths($bulan);

        $akaun->jadualAnsuran()
            ->where('status', 'menunggu')
            ->whereDate('tarikh_bayaran', '>=', $kuatKuasa->toDateString())
            ->orderBy('no_ansuran')
            ->limit($bulan)
            ->update(['status' => 'moratorium']);

        $toShift = $akaun->jadualAnsuran()
            ->where('status', 'menunggu')
            ->whereDate('tarikh_bayaran', '>=', $kuatKuasa->toDateString())
            ->orderBy('no_ansuran')
            ->get();

        foreach ($toShift as $row) {
            $row->update([
                'tarikh_bayaran' => Carbon::parse($row->tarikh_bayaran)->addMonths($bulan)->toDateString(),
            ]);
        }

        $akaun->update([
            'moratorium_tamat' => $moratoriumTamat,
            'tarikh_tamat' => $akaun->tarikh_tamat
                ? Carbon::parse($akaun->tarikh_tamat)->addMonths($bulan)
                : $moratoriumTamat,
            'status' => 'Moratorium',
        ]);
    }

    public function applyRestructure(AkaunPembiayaan $akaun, int $tempohBulan): void
    {
        $baki = (float) $akaun->baki_akhir;
        $bayaranBaharu = $tempohBulan > 0 ? ceil($baki / $tempohBulan) : (float) $akaun->bayaran_bulanan;

        $akaun->update([
            'bayaran_bulanan' => $bayaranBaharu,
            'tempoh_bulan' => $tempohBulan,
            'tarikh_tamat' => now()->addMonths($tempohBulan),
            'status' => 'Reschedule',
        ]);

        $this->generateSchedule($akaun->fresh(), $tempohBulan);
    }

    public function applyReschedule(AkaunPembiayaan $akaun, Carbon $tarikhKuatKuasa, ?int $tempohBulan = null): void
    {
        $this->ensureSchedule($akaun);

        $unpaid = $akaun->jadualAnsuran()
            ->whereIn('status', ['menunggu', 'tertunggak'])
            ->orderBy('no_ansuran')
            ->get();

        $cursor = $tarikhKuatKuasa->copy();
        foreach ($unpaid as $row) {
            $row->update([
                'tarikh_bayaran' => $cursor->toDateString(),
                'status' => 'menunggu',
            ]);
            $cursor->addMonth();
        }

        $tempoh = $tempohBulan ?? (int) $akaun->tempoh_bulan ?? $unpaid->count();
        $akaun->update([
            'tarikh_mula' => $tarikhKuatKuasa,
            'tarikh_tamat' => $cursor,
            'tempoh_bulan' => $tempoh,
            'status' => 'Reschedule',
        ]);
    }

    public function markInstallmentPaid(AkaunPembiayaan $akaun, float $amount): float
    {
        $remaining = $amount;
        $rows = $akaun->jadualAnsuran()
            ->whereIn('status', ['tertunggak', 'menunggu'])
            ->orderBy('no_ansuran')
            ->get();

        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }
            $due = (float) $row->jumlah_ansuran;
            if ($remaining >= $due) {
                $row->update(['status' => 'dibayar']);
                $remaining -= $due;
            }
        }

        return $remaining;
    }

    private function inferTempohBulan(AkaunPembiayaan $akaun): int
    {
        if ($akaun->tempoh_bulan) {
            return (int) $akaun->tempoh_bulan;
        }

        if ($akaun->tarikh_mula && $akaun->tarikh_tamat) {
            return max(1, (int) Carbon::parse($akaun->tarikh_mula)->diffInMonths($akaun->tarikh_tamat));
        }

        if ((float) $akaun->bayaran_bulanan > 0) {
            return max(1, (int) ceil((float) $akaun->baki_akhir / (float) $akaun->bayaran_bulanan));
        }

        return 36;
    }

    private function firstUnpaidInstallmentNo(AkaunPembiayaan $akaun, int $tempoh): int
    {
        $paid = $akaun->jadualAnsuran()->where('status', 'dibayar')->count();

        return min($tempoh, max(1, $paid + 1));
    }
}
