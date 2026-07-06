<?php

namespace Database\Seeders;

use App\Models\AkaunPembiayaan;
use App\Models\Jaminan;
use App\Models\Kutipan;
use App\Models\Permohonan;
use App\Models\PengeluaranDana;
use App\Models\SpptDataset;
use App\Models\Usahawan;
use App\Services\SpptViewTransform;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SpptSeeder extends Seeder
{
    private string $dataPath;

    public function run(): void
    {
        $this->dataPath = database_path('seeders/data');

        $this->seedUsahawan();
        $this->seedPermohonan();
        $this->seedAkaun();
        $this->seedPengeluaranDana();
        $this->seedJaminan();
        $this->seedKutipan();
        $this->seedDatasets();
        $this->seedSetup();
    }

    private function loadJson(string $file): array
    {
        $path = $this->dataPath.'/'.$file;

        return json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function seedUsahawan(): void
    {
        $pemantauan = $this->loadJson('pemantauan-usahawan.json');

        foreach ($pemantauan['usahawanList'] as $item) {
            Usahawan::updateOrCreate(
                ['no_usahawan' => $item['id']],
                [
                    'nama' => $item['nama'],
                    'no_ic' => $item['ic'],
                    'negeri' => $item['negeri'],
                    'no_telefon' => null,
                    'email' => null,
                    'jenis_perniagaan' => $item['perniagaan'] ?? null,
                    'status' => 'Aktif',
                ]
            );
        }

        Usahawan::updateOrCreate(
            ['no_usahawan' => 'U-004'],
            [
                'nama' => 'Fatimah binti Ibrahim',
                'no_ic' => '950410-10-3456',
                'negeri' => 'Perak',
                'status' => 'Tidak Aktif',
            ]
        );
    }

    private function seedPermohonan(): void
    {
        $pengeluaran = $this->loadJson('pengeluaran-dana.json');
        $detailsData = $this->loadJson('permohonan-details.json');
        $detailsByRef = collect($detailsData['applications'])->keyBy('noRujukan');

        $statusMap = [
            'Menunggu' => 'Menunggu',
            'Dalam Proses' => 'Dalam Penilaian',
            'Berjaya' => 'Diluluskan',
            'Gagal' => 'Ditolak',
            'Ditolak' => 'Ditolak',
        ];

        foreach ($pengeluaran['items'] as $item) {
            $ref = $item['idPembiayaan'];
            $meta = $detailsByRef->get($ref, []);
            $usahawan = Usahawan::where('nama', $item['nama'])->first();

            Permohonan::updateOrCreate(
                ['no_rujukan' => $ref],
                $this->permohonanSeedAttributes($item['nama'], $usahawan?->id, [
                    'kategori_pembiayaan' => $meta['kategoriPembiayaan'] ?? 'TEKUN Niaga',
                    'status' => $meta['status'] ?? ($statusMap[$item['status']] ?? 'Menunggu'),
                    'jumlah_permohonan' => $item['jumlahNumeric'],
                    'tarikh_permohonan' => $item['tarikhIso'],
                    'details' => $this->snakeCaseKeys($meta['details'] ?? []),
                ])
            );
        }

        foreach ($detailsData['applications'] as $app) {
            if ($detailsByRef->has($app['noRujukan']) && collect($pengeluaran['items'])->contains('idPembiayaan', $app['noRujukan'])) {
                continue;
            }

            $usahawan = Usahawan::where('nama', $app['nama'])->first();

            Permohonan::updateOrCreate(
                ['no_rujukan' => $app['noRujukan']],
                $this->permohonanSeedAttributes($app['nama'], $usahawan?->id, [
                    'kategori_pembiayaan' => $app['kategoriPembiayaan'] ?? 'TEKUN Niaga',
                    'status' => $app['status'],
                    'jumlah_permohonan' => $app['jumlahPermohonan'],
                    'tarikh_permohonan' => $app['tarikhPermohonan'],
                    'details' => $this->snakeCaseKeys($app['details'] ?? []),
                ])
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function permohonanSeedAttributes(string $nama, ?int $usahawanId, array $attributes): array
    {
        return array_merge([
            'nama' => $nama,
            'usahawan_id' => $usahawanId,
        ], $attributes);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function snakeCaseKeys(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $snakeKey = is_string($key) ? Str::snake($key) : $key;
            $result[$snakeKey] = is_array($value) ? $this->snakeCaseKeys($value) : $value;
        }

        return $result;
    }

    private function seedAkaun(): void
    {
        $data = $this->loadJson('pengurusan-akaun.json');

        foreach ($data['items'] as $item) {
            $permohonan = Permohonan::where('nama', $item['nama'])->first();
            $usahawan = Usahawan::where('nama', $item['nama'])->first();

            AkaunPembiayaan::updateOrCreate(
                ['no_akaun' => $item['id']],
                [
                    'permohonan_id' => $permohonan?->id,
                    'usahawan_id' => $usahawan?->id,
                    'ic' => $item['ic'],
                    'nama' => $item['nama'],
                    'nama_syarikat' => $item['namaSyarikat'] ?? null,
                    'ssm' => $item['ssm'] ?? null,
                    'pukonsa' => $item['pukonsa'] ?? null,
                    'cawangan' => $item['cawangan'],
                    'negeri' => $item['negeri'],
                    'produk' => $item['produk'],
                    'tarikh_mula' => $item['tarikhMula'],
                    'tarikh_tamat' => $item['tarikhTamat'],
                    'jumlah_pembiayaan' => $item['jumlahPembiayaan'],
                    'baki_pokok' => $item['bakiPokok'],
                    'baki_keuntungan' => $item['bakiKeuntungan'],
                    'baki_simpanan' => $item['bakiSimpanan'],
                    'penalti' => $item['penalti'],
                    'tunggakan' => $item['tunggakan'],
                    'baki_akhir' => $item['bakiAkhir'],
                    'bayaran_bulanan' => $item['bayaranBulanan'],
                    'status' => $item['status'],
                    'risiko' => $item['risiko'],
                    'no_bsas' => $item['noBsas'] ?? null,
                    'snc' => $item['snc'] ?? false,
                ]
            );
        }
    }

    private function seedPengeluaranDana(): void
    {
        $data = $this->loadJson('pengeluaran-dana.json');

        foreach ($data['items'] as $item) {
            $akaun = AkaunPembiayaan::where('nama', $item['nama'])->first();

            PengeluaranDana::updateOrCreate(
                ['rujukan' => $item['id']],
                [
                    'akaun_id' => $akaun?->id,
                    'id_pembiayaan' => $item['idPembiayaan'],
                    'nama' => $item['nama'],
                    'jumlah' => $item['jumlahNumeric'],
                    'jenis' => $item['jenisPengeluaran'],
                    'fasa' => $item['fasa'] ?? null,
                    'peratus_fasa' => $item['peratusFasa'] ?? null,
                    'bank' => $item['bank'] ?? null,
                    'no_akaun_bank' => $item['noAkaunBank'] ?? null,
                    'status' => $item['status'],
                    'no_rujukan_bank' => $item['noRujukanBank'] ?? null,
                    'fraud_risk' => $item['fraudRisk'] ?? null,
                    'fraud_alert' => $item['fraudAlert'] ?? null,
                    'bsas_verified' => $item['bsasVerified'] ?? false,
                    'legal_docs_complete' => $item['legalDocsComplete'] ?? false,
                    'tarikh_pengeluaran' => $item['tarikhIso'],
                ]
            );
        }
    }

    private function seedJaminan(): void
    {
        $data = $this->loadJson('pengurusan-jaminan.json');

        foreach ($data['items'] as $item) {
            Jaminan::updateOrCreate(
                ['rujukan' => $item['id']],
                [
                    'nama' => $item['nama'],
                    'jenis' => $item['jenis'],
                    'nilai' => $item['nilai'],
                    'status' => $item['status'],
                    'risiko' => $item['risiko'],
                    'no_pinjaman' => $item['noPinjaman'],
                    'tarikh_mula' => $item['tarikhMula'],
                    'tarikh_tamat' => $item['tarikhTamat'],
                    'deskripsi' => $item['deskripsi'] ?? null,
                    'dokumen' => $item['dokumen'] ?? null,
                ]
            );
        }
    }

    private function seedKutipan(): void
    {
        $data = $this->loadJson('kutipan.json');

        foreach ($data['KUTIPAN_ITEMS'] as $item) {
            $akaun = AkaunPembiayaan::where('no_akaun', $item['noPembiayaan'])
                ->orWhere('nama', $item['nama'])
                ->first();

            Kutipan::updateOrCreate(
                ['rujukan' => $item['id']],
                [
                    'akaun_id' => $akaun?->id,
                    'usahawan_id' => $akaun?->usahawan_id,
                    'nama' => $item['nama'],
                    'no_akaun' => $item['noPembiayaan'],
                    'cawangan' => $item['cawangan'],
                    'zon' => $item['zon'],
                    'pegawai' => $item['pegawaiSeliaan'],
                    'tunggakan' => SpptViewTransform::parseRm($item['jumlahTunggakan']) ?? 0,
                    'hasil_kutipan' => SpptViewTransform::parseRm($item['jumlahDikutip']),
                    'tarikh_akhir_bayaran' => $this->guessIsoDate($item['tarikhAkhirBayaran']),
                    'hari_lewat' => $item['hariLewat'],
                    'maklumat_psat' => $item['maklumatPsat'] ?? null,
                    'status' => $item['status'],
                    'tarikh_lawatan' => $this->guessIsoDate($item['tarikhLawatan']),
                ]
            );
        }
    }

    private function guessIsoDate(string $display): ?string
    {
        if ($display === '—' || $display === '') {
            return null;
        }

        $months = [
            'Jan' => '01', 'Feb' => '02', 'Mac' => '03', 'Apr' => '04',
            'Mei' => '05', 'Jun' => '06', 'Jul' => '07', 'Ogos' => '08',
            'Sep' => '09', 'Okt' => '10', 'Nov' => '11', 'Dis' => '12',
        ];

        if (preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', $display, $m)) {
            $month = $months[$m[2]] ?? '01';

            return sprintf('%s-%s-%02d', $m[3], $month, (int) $m[1]);
        }

        try {
            return Carbon::parse($display)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function seedDatasets(): void
    {
        $pengeluaran = $this->loadJson('pengeluaran-dana.json');
        $this->upsertDataset('pengeluaran', 'audit_trail', $pengeluaran['auditTrail']);
        $this->upsertDataset('pengeluaran', 'batches', $pengeluaran['batches']);
        $this->upsertDataset('pengeluaran', 'legal_docs', $pengeluaran['legalDocsTemplate']);
        $this->upsertDataset('pengeluaran', 'exceptions', $pengeluaran['exceptions']);
        $this->upsertDataset('pengeluaran', 'integration_statuses', $pengeluaran['integrationStatuses']);

        $jaminan = $this->loadJson('pengurusan-jaminan.json');
        $this->upsertDataset('jaminan', 'audit_logs', $jaminan['auditLogs']);
        $this->upsertDataset('jaminan', 'notifikasi', $jaminan['notifikasi']);

        $kutipan = $this->loadJson('kutipan.json');
        $this->upsertDataset('kutipan', 'skh_items', $kutipan['SKH_ITEMS']);
        $this->upsertDataset('kutipan', 'call_center_items', $kutipan['CALL_CENTER_ITEMS']);
        $this->upsertDataset('kutipan', 'psat_items', $kutipan['PSAT_ITEMS']);
        $this->upsertDataset('kutipan', 'skm_mingguan', $kutipan['SKM_MINGGUAN']);
        $this->upsertDataset('kutipan', 'audit_log_items', $kutipan['AUDIT_LOG_ITEMS']);
        $this->upsertDataset('kutipan', 'kpi_pegawai', $kutipan['KPI_PEGAWAI']);

        $pembayaran = $this->loadJson('bayaran-pembiayaan.json');
        foreach ([
            'payment_channels' => 'PAYMENT_CHANNELS',
            'bayaran_items' => 'BAYARAN_ITEMS',
            'pemadanan_resit' => 'PEMADANAN_RESIT',
            'rekon_bank' => 'REKON_BANK',
            'lebihan_kekurangan' => 'LEBIHAN_KEKURANGAN',
            'penyata_bayaran' => 'PENYATA_BAYARAN',
            'early_settlement' => 'EARLY_SETTLEMENT_DUMMY',
            'akaun_selesai_bayar' => 'AKAUN_SELESAI_BAYAR',
            'ai_ocr_results' => 'AI_OCR_RESULTS',
            'ai_analytics' => 'AI_ANALYTICS',
            'chatbot_sample_qa' => 'CHATBOT_SAMPLE_QA',
        ] as $key => $source) {
            $this->upsertDataset('pembayaran', $key, $pembayaran[$source]);
        }

        $litigasi = $this->loadJson('litigasi.json');
        foreach ([
            'panel_peguam' => 'PANEL_PEGUAM',
            'akaun_npf' => 'AKAUN_NPF',
            'nod_items' => 'NOD_ITEMS',
            'kes_litigasi' => 'KES_LITIGASI',
            'execution_items' => 'EXECUTION_ITEMS',
            'wss_items' => 'WSS_ITEMS',
            'garnishee_items' => 'GARNISHEE_ITEMS',
            'jds_items' => 'JDS_ITEMS',
            'kebankrapan_items' => 'KEBANKRAPAN_ITEMS',
            'winding_up_items' => 'WINDING_UP_ITEMS',
            'litigasi_audit' => 'LITIGASI_AUDIT',
            'laporan_kes_aktif' => 'LAPORAN_KES_AKTIF',
            'laporan_keputusan_bulanan' => 'LAPORAN_KEPUTUSAN_BULANAN',
        ] as $key => $source) {
            $this->upsertDataset('litigasi', $key, $litigasi[$source]);
        }

        $laporan = $this->loadJson('laporan.json');
        foreach ($laporan as $key => $payload) {
            $this->upsertDataset('laporan', $this->snakeKey($key), $payload);
        }

        $audit = $this->loadJson('audit.json');
        foreach ($audit as $key => $payload) {
            $this->upsertDataset('audit', $this->snakeKey($key), $payload);
        }

        $pemantauan = $this->loadJson('pemantauan-usahawan.json');
        foreach ([
            'usahawan_list' => 'usahawanList',
            'dokumen_by_usahawan' => 'dokumenByUsahawan',
            'lawatan_list' => 'lawatanList',
            'program_latihan' => 'programLatihanList',
            'kehadiran_latihan' => 'kehadiranLatihanList',
            'ai_forecast' => 'aiForecastData',
        ] as $key => $source) {
            $this->upsertDataset('pemantauan', $key, $pemantauan[$source]);
        }
    }

    private function upsertDataset(string $module, string $key, mixed $payload): void
    {
        SpptDataset::updateOrCreate(
            ['module' => $module, 'dataset_key' => $key],
            ['payload' => $payload]
        );
    }

    private function snakeKey(string $key): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $key));
    }

    private function seedSetup(): void
    {
        $defaults = config('sppt-setup.defaults', []);

        foreach ($defaults as $key => $items) {
            SpptDataset::updateOrCreate(
                ['module' => 'setup', 'dataset_key' => $key],
                ['payload' => $items]
            );
        }
    }
}
