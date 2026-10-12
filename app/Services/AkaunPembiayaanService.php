<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\Permohonan;
use Carbon\Carbon;

class AkaunPembiayaanService
{
    public function __construct(
        protected JadualAnsuranService $jadualService,
    ) {}

    public function openFromPermohonan(Permohonan $permohonan): AkaunPembiayaan
    {
        $existing = AkaunPembiayaan::query()
            ->where('permohonan_id', $permohonan->id)
            ->first();

        if ($existing) {
            return $this->syncFromFormula($existing, $permohonan);
        }

        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $formula = is_array($details['formula_pembiayaan'] ?? null) ? $details['formula_pembiayaan'] : [];
        $penilaian = is_array($details['penilaian'] ?? null) ? $details['penilaian'] : [];

        $jumlahPembiayaan = (float) ($formula['jumlah_pokok']
            ?? $penilaian['jumlah_diluluskan']
            ?? $permohonan->jumlah_permohonan
            ?? 0);

        $jumlahKeuntungan = (float) ($formula['jumlah_keuntungan'] ?? 0);
        $jumlahBayaranBalik = (float) ($formula['jumlah_bayaran_balik'] ?? ($jumlahPembiayaan + $jumlahKeuntungan));
        $bayaranBulanan = (float) ($formula['ansuran_bulanan'] ?? $penilaian['ansuran_bulanan'] ?? 0);
        $tempohBulan = (int) ($formula['tempoh_bulan'] ?? $penilaian['tempoh_pembiayaan'] ?? 36);
        $kaedah = (string) ($formula['kaedah_pengiraan'] ?? $penilaian['kaedah_pengiraan'] ?? 'reducing_balance');
        $kadar = (float) ($formula['kadar_keuntungan_tahunan'] ?? $penilaian['kadar_keuntungan'] ?? 4);

        $tarikhMula = Carbon::now()->startOfMonth()->addMonth();
        $tarikhTamat = $tarikhMula->copy()->addMonths($tempohBulan);

        $ic = (string) (($details['no_ic_baru'] ?? $details['no_ic'] ?? '') ?: '');

        $akaun = AkaunPembiayaan::create([
            'no_akaun' => $this->generateNoAkaun($permohonan),
            'permohonan_id' => $permohonan->id,
            'usahawan_id' => $permohonan->usahawan_id,
            'ic' => $ic !== '' ? $ic : null,
            'nama' => (string) $permohonan->nama,
            'nama_syarikat' => $details['nama_syarikat'] ?? null,
            'ssm' => $details['no_ssm'] ?? $details['no_pendaftaran_syarikat'] ?? null,
            'cawangan' => $permohonan->cawangan,
            'negeri' => $permohonan->negeri ?? ($details['negeri'] ?? null),
            'produk' => (string) ($penilaian['cadangan_jenis_pembiayaan'] ?? $permohonan->kategori_pembiayaan ?? 'TEKUN Niaga'),
            'tarikh_mula' => $tarikhMula,
            'tarikh_tamat' => $tarikhTamat,
            'jumlah_pembiayaan' => $jumlahPembiayaan,
            'baki_pokok' => $jumlahPembiayaan,
            'baki_keuntungan' => $jumlahKeuntungan,
            'baki_simpanan' => 0,
            'penalti' => 0,
            'tawidh' => 0,
            'tunggakan' => 0,
            'baki_akhir' => $jumlahBayaranBalik,
            'bayaran_bulanan' => $bayaranBulanan,
            'kaedah_pengiraan' => $kaedah,
            'kadar_keuntungan_tahunan' => $kadar,
            'tempoh_bulan' => $tempohBulan,
            'status' => 'Aktif',
            'risiko' => 'Normal',
        ]);

        $this->jadualService->generateFromFormula($akaun, $formula, $tarikhMula);

        return $akaun->fresh();
    }

    public function syncFromFormula(AkaunPembiayaan $akaun, Permohonan $permohonan): AkaunPembiayaan
    {
        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $formula = is_array($details['formula_pembiayaan'] ?? null) ? $details['formula_pembiayaan'] : [];

        if ($formula === []) {
            return $akaun;
        }

        $jumlahPembiayaan = (float) ($formula['jumlah_pokok'] ?? $akaun->jumlah_pembiayaan);
        $jumlahKeuntungan = (float) ($formula['jumlah_keuntungan'] ?? $akaun->baki_keuntungan);
        $jumlahBayaranBalik = (float) ($formula['jumlah_bayaran_balik'] ?? ($jumlahPembiayaan + $jumlahKeuntungan));
        $tempohBulan = (int) ($formula['tempoh_bulan'] ?? $akaun->tempoh_bulan ?? 36);
        $tarikhMula = $akaun->tarikh_mula ? Carbon::parse($akaun->tarikh_mula) : Carbon::now()->startOfMonth()->addMonth();

        $akaun->update([
            'jumlah_pembiayaan' => $jumlahPembiayaan,
            'baki_pokok' => $jumlahPembiayaan,
            'baki_keuntungan' => $jumlahKeuntungan,
            'baki_akhir' => $jumlahBayaranBalik,
            'bayaran_bulanan' => (float) ($formula['ansuran_bulanan'] ?? $akaun->bayaran_bulanan),
            'kaedah_pengiraan' => (string) ($formula['kaedah_pengiraan'] ?? $akaun->kaedah_pengiraan),
            'kadar_keuntungan_tahunan' => (float) ($formula['kadar_keuntungan_tahunan'] ?? $akaun->kadar_keuntungan_tahunan),
            'tempoh_bulan' => $tempohBulan,
            'tarikh_tamat' => $tarikhMula->copy()->addMonths($tempohBulan),
        ]);

        $this->jadualService->generateFromFormula($akaun->fresh(), $formula, $tarikhMula);

        return $akaun->fresh();
    }

    private function generateNoAkaun(Permohonan $permohonan): string
    {
        $ref = preg_replace('/[^A-Za-z0-9]/', '', (string) ($permohonan->no_rujukan ?? ''));

        if ($ref !== '') {
            return 'A-'.$ref;
        }

        return 'A-'.str_pad((string) $permohonan->id, 6, '0', STR_PAD_LEFT);
    }
}
