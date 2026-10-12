<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\PermohonanPenjadualan;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PenjadualanService
{
    public function __construct(
        protected JadualAnsuranService $jadualService,
        protected TawidhCalculationService $tawidhService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PermohonanPenjadualan
    {
        $akaun = AkaunPembiayaan::findOrFail($data['akaun_id']);

        return PermohonanPenjadualan::create([
            'rujukan' => 'PS-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),
            'akaun_id' => $akaun->id,
            'jenis' => $data['jenis'],
            'status' => 'menunggu',
            'tempoh_bulan' => $data['tempoh_bulan'] ?? null,
            'tarikh_mula' => $data['tarikh_mula'] ?? null,
            'tarikh_kuat_kuasa' => $data['tarikh_kuat_kuasa'] ?? now()->toDateString(),
            'bayaran_baharu' => $data['bayaran_baharu'] ?? null,
            'sebab' => $data['sebab'] ?? null,
        ]);
    }

    public function approve(PermohonanPenjadualan $permohonan, int $userId, ?string $catatan = null): PermohonanPenjadualan
    {
        $akaun = $permohonan->akaun;
        $kuatKuasa = Carbon::parse($permohonan->tarikh_kuat_kuasa ?? now());

        match ($permohonan->jenis) {
            'moratorium' => $this->jadualService->applyMoratorium(
                $akaun,
                (int) $permohonan->tempoh_bulan,
                $kuatKuasa,
            ),
            'penstrukturan' => $this->jadualService->applyRestructure(
                $akaun,
                (int) $permohonan->tempoh_bulan,
            ),
            'penjadualan_semula' => $this->jadualService->applyReschedule(
                $akaun,
                $kuatKuasa,
                $permohonan->tempoh_bulan ? (int) $permohonan->tempoh_bulan : null,
            ),
            default => throw new \InvalidArgumentException('Jenis penjadualan tidak sah.'),
        };

        $permohonan->update([
            'status' => 'diluluskan',
            'diluluskan_oleh' => $userId,
            'tarikh_kelulusan' => now(),
            'catatan_pegawai' => $catatan,
        ]);

        $this->tawidhService->recalculate($akaun->fresh());

        return $permohonan->fresh(['akaun']);
    }

    public function reject(PermohonanPenjadualan $permohonan, int $userId, ?string $catatan = null): PermohonanPenjadualan
    {
        $permohonan->update([
            'status' => 'ditolak',
            'diluluskan_oleh' => $userId,
            'tarikh_kelulusan' => now(),
            'catatan_pegawai' => $catatan,
        ]);

        return $permohonan->fresh();
    }
}
