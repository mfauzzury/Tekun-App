<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AkaunPembiayaan;
use App\Models\Jaminan;
use App\Models\Kutipan;
use App\Models\Permohonan;
use App\Models\PengeluaranDana;
use App\Models\Usahawan;
use Illuminate\Http\JsonResponse;

class SpptDashboardController extends Controller
{
    use ApiResponse;

    public function summary(): JsonResponse
    {
        $permohonanDalamProses = Permohonan::whereIn('status', ['Dalam Proses', 'Menunggu Dokumen'])->count();
        $akaunAktif = AkaunPembiayaan::where('status', 'Aktif')->count();
        $kutipanBulanIni = Kutipan::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $tunggakan = AkaunPembiayaan::whereIn('status', ['Tunggakan', 'NPF'])->count();

        return $this->sendOk([
            'permohonanDalamProses' => $permohonanDalamProses,
            'akaunAktif' => $akaunAktif,
            'kutipanBulanIni' => $kutipanBulanIni,
            'tunggakan' => $tunggakan,
            'kesLitigasiAktif' => 0,
            'jaminanAktif' => Jaminan::where('status', 'Aktif')->count(),
            'pengeluaranMenunggu' => PengeluaranDana::where('status', 'Menunggu')->count(),
            'jumlahUsahawan' => Usahawan::count(),
            'permohonanSelesaiBulanIni' => Permohonan::where('status', 'Lengkap')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ]);
    }
}
