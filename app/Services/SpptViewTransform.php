<?php

namespace App\Services;

use App\Models\AkaunPembiayaan;
use App\Models\Jaminan;
use App\Models\Kutipan;
use App\Models\PengeluaranDana;
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
            'tunggakan' => (float) $row->tunggakan,
            'bakiAkhir' => (float) $row->baki_akhir,
            'bayaranBulanan' => (float) $row->bayaran_bulanan,
            'status' => $row->status,
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
            'nama' => $row['nama'],
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
}
