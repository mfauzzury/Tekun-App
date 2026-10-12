<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\BatchBayaran;
use App\Models\JadualAnsuran;
use App\Models\Jaminan;
use App\Models\Kutipan;
use App\Models\PengeluaranDana;
use App\Models\NotisKutipan;
use App\Models\PermohonanPenjadualan;
use App\Models\TransaksiBayaran;
use Carbon\Carbon;

class SpptViewTransform
{
    public static function akaun(AkaunPembiayaan $row): array
    {
        return [
            'id' => $row->no_akaun,
            'ic' => $row->ic ?? '',
            'nama' => $row->nama,
            'namaSyarikat' => $row->nama_syarikat,
            'ssm' => $row->ssm,
            'pukonsa' => $row->pukonsa,
            'cawangan' => $row->cawangan ?? '',
            'negeri' => $row->negeri ?? '',
            'produk' => $row->produk ?? '',
            'tarikhMula' => $row->tarikh_mula?->format('Y-m-d') ?? '',
            'tarikhTamat' => $row->tarikh_tamat?->format('Y-m-d') ?? '',
            'jumlahPembiayaan' => (float) $row->jumlah_pembiayaan,
            'bakiPokok' => (float) $row->baki_pokok,
            'bakiKeuntungan' => (float) $row->baki_keuntungan,
            'bakiSimpanan' => (float) $row->baki_simpanan,
            'penalti' => (float) $row->penalti,
            'tawidh' => (float) ($row->tawidh ?? 0),
            'hariLewat' => (int) ($row->hari_lewat ?? 0),
            'tunggakan' => (float) $row->tunggakan,
            'bakiAkhir' => (float) $row->baki_akhir,
            'bayaranBulanan' => (float) $row->bayaran_bulanan,
            'kaedahPengiraan' => $row->kaedah_pengiraan,
            'kadarKeuntunganTahunan' => $row->kadar_keuntungan_tahunan !== null ? (float) $row->kadar_keuntungan_tahunan : null,
            'tempohBulan' => $row->tempoh_bulan ? (int) $row->tempoh_bulan : null,
            'moratoriumTamat' => $row->moratorium_tamat?->format('Y-m-d'),
            'status' => $row->status,
            'klasifikasiNpl' => $row->klasifikasi_npl ?? 'lancar',
            'klasifikasiNplLabel' => config('sppt-collection.npl.labels.'.$row->klasifikasi_npl, $row->klasifikasi_npl ?? 'Lancar'),
            'klasifikasiDikemaskiniPada' => $row->klasifikasi_dikemaskini_pada?->toIso8601String(),
            'risiko' => $row->risiko,
            'noBsas' => $row->no_bsas,
            'snc' => (bool) $row->snc,
            '_dbId' => $row->id,
        ];
    }

    public static function pengeluaran(PengeluaranDana $row): array
    {
        $tarikhIso = $row->tarikh_pengeluaran?->format('Y-m-d') ?? $row->created_at->format('Y-m-d');

        return [
            'id' => $row->rujukan,
            'idPembiayaan' => $row->id_pembiayaan,
            'nama' => $row->nama,
            'jumlah' => 'RM '.number_format((float) $row->jumlah, 0),
            'jumlahNumeric' => (float) $row->jumlah,
            'tarikh' => self::formatTarikhMalay($tarikhIso),
            'tarikhIso' => $tarikhIso,
            'status' => $row->status,
            'bank' => $row->bank,
            'noAkaunBank' => $row->no_akaun_bank,
            'jenisPengeluaran' => $row->jenis,
            'fasa' => $row->fasa,
            'peratusFasa' => $row->peratus_fasa !== null ? (float) $row->peratus_fasa : null,
            'noRujukanBank' => $row->no_rujukan_bank,
            'fraudRisk' => $row->fraud_risk,
            'fraudAlert' => $row->fraud_alert,
            'bsasVerified' => (bool) $row->bsas_verified,
            'legalDocsComplete' => (bool) $row->legal_docs_complete,
            '_dbId' => $row->id,
        ];
    }

    public static function jaminan(Jaminan $row): array
    {
        $dokumen = $row->dokumen;
        if (is_string($dokumen)) {
            $dokumen = json_decode($dokumen, true) ?? [];
        }

        return [
            'id' => $row->rujukan,
            'nama' => $row->nama,
            'jenis' => $row->jenis,
            'nilai' => (float) $row->nilai,
            'status' => $row->status,
            'risiko' => $row->risiko,
            'noPinjaman' => $row->no_pinjaman ?? '',
            'tarikhMula' => $row->tarikh_mula?->format('Y-m-d') ?? '',
            'tarikhTamat' => $row->tarikh_tamat?->format('Y-m-d') ?? '',
            'deskripsi' => $row->deskripsi,
            'dokumen' => $dokumen,
            '_dbId' => $row->id,
        ];
    }

    public static function kutipan(Kutipan $row): array
    {
        $tunggakan = (float) $row->tunggakan;
        $dikutip = $row->hasil_kutipan;

        return [
            'id' => $row->rujukan,
            'noPembiayaan' => $row->no_akaun ?? '',
            'nama' => $row->nama,
            'jumlahTunggakan' => 'RM '.number_format($tunggakan, 0),
            'jumlahDikutip' => $dikutip !== null ? 'RM '.number_format((float) $dikutip, 0) : '—',
            'tarikhAkhirBayaran' => $row->tarikh_akhir_bayaran
                ? self::formatTarikhMalay($row->tarikh_akhir_bayaran->format('Y-m-d'))
                : '—',
            'tarikhLawatan' => $row->tarikh_lawatan
                ? self::formatTarikhMalay($row->tarikh_lawatan->format('Y-m-d'))
                : '—',
            'cawangan' => $row->cawangan ?? '',
            'pegawaiSeliaan' => $row->pegawai ?? '',
            'zon' => $row->zon ?? '',
            'status' => $row->status,
            'hariLewat' => (int) $row->hari_lewat,
            'maklumatPsat' => $row->maklumat_psat,
            '_dbId' => $row->id,
        ];
    }

    public static function permohonanPenilaian(array $row): array
    {
        return [
            'id' => $row['noRujukan'],
            'permohonan_id' => $row['id'],
            'nama' => $row['nama'],
            'negeri' => $row['negeri'] ?? null,
            'cawangan' => $row['cawangan'] ?? null,
            'jumlah' => 'RM '.number_format((float) $row['jumlahPermohonan'], 0),
            'tarikh' => $row['tarikhPermohonan']
                ? self::formatTarikhMalay($row['tarikhPermohonan'])
                : '—',
            'status' => $row['status'],
            '_dbId' => $row['id'],
        ];
    }

    public static function usahawanRekod(array $row): array
    {
        return [
            'id' => $row['noUsahawan'],
            'nama' => $row['nama'],
            'noIc' => $row['noIc'] ?? '',
            'negeri' => $row['negeri'] ?? '',
            'status' => $row['status'],
            '_dbId' => $row['id'],
        ];
    }

    public static function formatTarikhMalay(string $isoDate): string
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mac', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ogos', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dis',
        ];

        try {
            $date = Carbon::parse($isoDate);

            return $date->day.' '.$months[$date->month].' '.$date->year;
        } catch (\Throwable) {
            return $isoDate;
        }
    }

    public static function parseRm(?string $value): ?float
    {
        if ($value === null || $value === '' || $value === '—') {
            return null;
        }

        $clean = preg_replace('/[^0-9.]/', '', $value);

        return $clean !== '' ? (float) $clean : null;
    }

    public static function jadualAnsuran(JadualAnsuran $row): array
    {
        return [
            'no' => (int) $row->no_ansuran,
            'tarikh' => self::formatTarikhMalay($row->tarikh_bayaran->format('Y-m-d')),
            'tarikhIso' => $row->tarikh_bayaran->format('Y-m-d'),
            'ansuran' => (float) $row->jumlah_ansuran,
            'pokok' => (float) $row->pokok,
            'keuntungan' => (float) $row->keuntungan,
            'baki' => (float) $row->baki,
            'status' => $row->status,
            '_dbId' => $row->id,
        ];
    }

    public static function penjadualan(PermohonanPenjadualan $row): array
    {
        return [
            'id' => $row->rujukan,
            'rujukan' => $row->rujukan,
            'akaunId' => $row->akaun_id,
            'noAkaun' => $row->akaun?->no_akaun,
            'nama' => $row->akaun?->nama,
            'jenis' => $row->jenis,
            'status' => $row->status,
            'tempohBulan' => $row->tempoh_bulan ? (int) $row->tempoh_bulan : null,
            'tarikhMula' => $row->tarikh_mula?->format('Y-m-d'),
            'tarikhKuatKuasa' => $row->tarikh_kuat_kuasa?->format('Y-m-d'),
            'bayaranBaharu' => $row->bayaran_baharu !== null ? (float) $row->bayaran_baharu : null,
            'sebab' => $row->sebab,
            'catatanPegawai' => $row->catatan_pegawai,
            'tarikhKelulusan' => $row->tarikh_kelulusan?->toIso8601String(),
            '_dbId' => $row->id,
        ];
    }

    public static function transaksiBayaran(TransaksiBayaran $row): array
    {
        return [
            'id' => $row->rujukan,
            'rujukan' => $row->rujukan,
            'akaunId' => $row->akaun_id,
            'noAkaun' => $row->akaun?->no_akaun,
            'nama' => $row->akaun?->nama,
            'jumlah' => (float) $row->jumlah,
            'kepadaTawidh' => (float) $row->kepada_tawidh,
            'kepadaTunggakan' => (float) $row->kepada_tunggakan,
            'kepadaAnsuran' => (float) $row->kepada_ansuran,
            'saluran' => $row->saluran,
            'mod' => $row->mod,
            'status' => $row->status,
            'noRujukanLuar' => $row->no_rujukan_luar,
            'tarikhBayaran' => $row->tarikh_bayaran->toIso8601String(),
            '_dbId' => $row->id,
        ];
    }

    public static function notisKutipan(NotisKutipan $row): array
    {
        $meta = $row->meta ?? [];

        return [
            'id' => $row->rujukan,
            'rujukan' => $row->rujukan,
            'akaunId' => $row->akaun_id,
            'noAkaun' => $row->akaun?->no_akaun ?? ($meta['noAkaun'] ?? ''),
            'nama' => $row->akaun?->nama ?? ($meta['nama'] ?? ''),
            'tahap' => $row->tahap,
            'tahapLabel' => $meta['label'] ?? $row->tahap,
            'jenis' => $row->jenis,
            'status' => $row->status,
            'hariLewat' => (int) $row->hari_lewat,
            'jumlahTunggakan' => (float) $row->jumlah_tunggakan,
            'mesej' => $row->mesej,
            'tarikhDijadual' => $row->tarikh_dijadual->toIso8601String(),
            'tarikhDihantar' => $row->tarikh_dihantar?->toIso8601String(),
            'meta' => $meta,
            '_dbId' => $row->id,
        ];
    }

    public static function batchBayaran(BatchBayaran $row): array
    {
        return [
            'id' => $row->rujukan,
            'rujukan' => $row->rujukan,
            'saluran' => $row->saluran,
            'status' => $row->status,
            'jumlahRekod' => (int) $row->jumlah_rekod,
            'jumlahBerjaya' => (int) $row->jumlah_berjaya,
            'jumlahGagal' => (int) $row->jumlah_gagal,
            'jumlahKeseluruhan' => (float) $row->jumlah_keseluruhan,
            'catatan' => $row->catatan,
            '_dbId' => $row->id,
        ];
    }
}
