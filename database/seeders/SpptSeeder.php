<?php

namespace Database\Seeders;

use App\Models\AkaunPembiayaan;
use App\Models\Jaminan;
use App\Models\Kutipan;
use App\Models\Permohonan;
use App\Models\PengeluaranDana;
use App\Models\SpptDataset;
use App\Models\Usahawan;
use Illuminate\Database\Seeder;

class SpptSeeder extends Seeder
{
    public function run(): void
    {
        $usahawan = [
            Usahawan::updateOrCreate(['no_usahawan' => 'USW-00001'], [
                'nama' => 'Ahmad bin Abdullah',
                'no_ic' => '850315-10-5432',
                'alamat' => 'No 12, Jalan Merdeka',
                'poskod' => '50000',
                'negeri' => 'Wilayah Persekutuan Kuala Lumpur',
                'no_telefon' => '012-3456789',
                'email' => 'ahmad@example.com',
                'jenis_perniagaan' => 'Peruncitan',
                'status' => 'Aktif',
            ]),
            Usahawan::updateOrCreate(['no_usahawan' => 'USW-00002'], [
                'nama' => 'Siti Nurhaliza binti Omar',
                'no_ic' => '920618-14-6789',
                'alamat' => 'Lot 5, Kampung Baru',
                'poskod' => '15000',
                'negeri' => 'Kelantan',
                'no_telefon' => '019-8765432',
                'email' => 'siti@example.com',
                'jenis_perniagaan' => 'Makanan & Minuman',
                'status' => 'Aktif',
            ]),
            Usahawan::updateOrCreate(['no_usahawan' => 'USW-00003'], [
                'nama' => 'Mohd Rizal bin Hassan',
                'no_ic' => '780512-08-1234',
                'alamat' => 'No 8, Taman Industri',
                'poskod' => '80000',
                'negeri' => 'Johor',
                'no_telefon' => '013-1122334',
                'email' => 'rizal@example.com',
                'jenis_perniagaan' => 'Pembuatan',
                'status' => 'Aktif',
            ]),
        ];

        $permohonan = [
            Permohonan::updateOrCreate(['no_rujukan' => 'PM-2024-0001'], [
                'usahawan_id' => $usahawan[0]->id,
                'nama' => 'Ahmad bin Abdullah',
                'kategori_pembiayaan' => 'TEKUN Niaga',
                'status' => 'Dalam Proses',
                'jumlah_permohonan' => 15000,
                'tarikh_permohonan' => '2024-03-01',
            ]),
            Permohonan::updateOrCreate(['no_rujukan' => 'PM-2024-0002'], [
                'usahawan_id' => $usahawan[1]->id,
                'nama' => 'Siti Nurhaliza binti Omar',
                'kategori_pembiayaan' => 'TEMAN TEKUN',
                'status' => 'Menunggu Dokumen',
                'jumlah_permohonan' => 20000,
                'tarikh_permohonan' => '2024-03-05',
            ]),
            Permohonan::updateOrCreate(['no_rujukan' => 'PM-2024-0003'], [
                'usahawan_id' => $usahawan[2]->id,
                'nama' => 'Mohd Rizal bin Hassan',
                'kategori_pembiayaan' => 'Kontrak',
                'status' => 'Lengkap',
                'jumlah_permohonan' => 50000,
                'tarikh_permohonan' => '2024-02-15',
            ]),
        ];

        $akaun = [
            AkaunPembiayaan::updateOrCreate(['no_akaun' => 'PF-2023-041'], [
                'permohonan_id' => $permohonan[2]->id,
                'usahawan_id' => $usahawan[2]->id,
                'ic' => '780512-08-1234',
                'nama' => 'Mohd Rizal bin Hassan',
                'cawangan' => 'Kota Bharu',
                'negeri' => 'Kelantan',
                'produk' => 'Pembiayaan Mikro',
                'tarikh_mula' => '2023-06-01',
                'tarikh_tamat' => '2026-06-01',
                'jumlah_pembiayaan' => 50000,
                'baki_pokok' => 32000,
                'baki_keuntungan' => 4500,
                'baki_simpanan' => 500,
                'penalti' => 0,
                'tunggakan' => 1200,
                'baki_akhir' => 37700,
                'bayaran_bulanan' => 1500,
                'status' => 'Tunggakan',
                'risiko' => 'Amaran',
            ]),
            AkaunPembiayaan::updateOrCreate(['no_akaun' => 'PF-2023-008'], [
                'permohonan_id' => $permohonan[0]->id,
                'usahawan_id' => $usahawan[0]->id,
                'ic' => '850315-10-5432',
                'nama' => 'Ahmad bin Abdullah',
                'cawangan' => 'Kuala Lumpur',
                'negeri' => 'Wilayah Persekutuan Kuala Lumpur',
                'produk' => 'Pembiayaan Mikro',
                'tarikh_mula' => '2023-04-01',
                'tarikh_tamat' => '2026-04-01',
                'jumlah_pembiayaan' => 15000,
                'baki_pokok' => 8000,
                'baki_keuntungan' => 1200,
                'baki_simpanan' => 200,
                'penalti' => 0,
                'tunggakan' => 0,
                'baki_akhir' => 9400,
                'bayaran_bulanan' => 500,
                'status' => 'Aktif',
                'risiko' => 'Normal',
            ]),
        ];

        PengeluaranDana::updateOrCreate(['rujukan' => 'PD-2024-001'], [
            'akaun_id' => $akaun[1]->id,
            'id_pembiayaan' => 'PF-2023-008',
            'nama' => 'Ahmad bin Abdullah',
            'jumlah' => 15000,
            'jenis' => 'Penuh',
            'bank' => 'Maybank',
            'no_akaun_bank' => '1234567890',
            'status' => 'Berjaya',
            'fraud_risk' => 'Rendah',
            'bsas_verified' => true,
            'legal_docs_complete' => true,
            'tarikh_pengeluaran' => '2023-04-05',
        ]);

        PengeluaranDana::updateOrCreate(['rujukan' => 'PD-2024-002'], [
            'akaun_id' => $akaun[0]->id,
            'id_pembiayaan' => 'PF-2023-041',
            'nama' => 'Mohd Rizal bin Hassan',
            'jumlah' => 25000,
            'jenis' => 'Berperingkat',
            'fasa' => 1,
            'peratus_fasa' => 50,
            'bank' => 'CIMB',
            'no_akaun_bank' => '9876543210',
            'status' => 'Menunggu',
            'fraud_risk' => 'Sederhana',
            'bsas_verified' => false,
            'legal_docs_complete' => true,
        ]);

        Jaminan::updateOrCreate(['rujukan' => 'JMN-001'], [
            'nama' => 'Ahmad bin Abdullah',
            'jenis' => 'Inden',
            'nilai' => 15000,
            'status' => 'Aktif',
            'risiko' => 'Rendah',
            'no_pinjaman' => 'PF-2023-008',
            'tarikh_mula' => '2023-04-01',
            'tarikh_tamat' => '2026-04-01',
        ]);

        Kutipan::updateOrCreate(['rujukan' => 'KUT-2024-001'], [
            'akaun_id' => $akaun[0]->id,
            'usahawan_id' => $usahawan[2]->id,
            'nama' => 'Mohd Rizal bin Hassan',
            'no_akaun' => 'PF-2023-041',
            'cawangan' => 'Kota Bharu',
            'zon' => 'Zon Utara',
            'pegawai' => 'Ahmad bin Ali',
            'tunggakan' => 1200,
            'status' => 'Janji Bayar',
            'janji_bayar' => now()->addDays(7)->toDateString(),
        ]);

        $this->seedDatasets();
        $this->seedSetup();
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

    private function seedDatasets(): void
    {
        SpptDataset::updateOrCreate(
            ['module' => 'litigasi', 'dataset_key' => 'panel_peguam'],
            ['payload' => [
                ['id' => 'PP-001', 'namaFirma' => 'Messrs Ahmad & Associates', 'negeri' => 'Kuala Lumpur', 'status' => 'aktif', 'kuotaNod' => 1000, 'kesAktif' => 45],
                ['id' => 'PP-002', 'namaFirma' => 'Messrs Siti & Partners', 'negeri' => 'Kuala Lumpur', 'status' => 'aktif', 'kuotaNod' => 1000, 'kesAktif' => 32],
            ]]
        );

        SpptDataset::updateOrCreate(
            ['module' => 'litigasi', 'dataset_key' => 'kes_litigasi'],
            ['payload' => [
                ['id' => 'KES-001', 'noKes' => 'KTR-2024-001', 'noAkaun' => 'PF-2023-041', 'nama' => 'Mohd Rizal bin Hassan', 'statusKes' => 'nod-dihantar', 'peringkat' => 'NOD'],
                ['id' => 'KES-002', 'noKes' => 'KTR-2024-002', 'noAkaun' => 'PF-2022-089', 'nama' => 'Syarikat XYZ Sdn Bhd', 'statusKes' => 'saman-dihantar', 'peringkat' => 'Saman'],
            ]]
        );

        SpptDataset::updateOrCreate(
            ['module' => 'pembayaran', 'dataset_key' => 'bayaran_items'],
            ['payload' => [
                ['id' => 'BYR-001', 'noAkaun' => 'PF-2023-008', 'nama' => 'Ahmad bin Abdullah', 'jumlah' => 'RM 500.00', 'tarikh' => '2 Jul 2024', 'kaedah' => 'FPX'],
                ['id' => 'BYR-002', 'noAkaun' => 'PF-2023-041', 'nama' => 'Mohd Rizal bin Hassan', 'jumlah' => 'RM 1,500.00', 'tarikh' => '1 Jul 2024', 'kaedah' => 'Kaunter'],
            ]]
        );

        SpptDataset::updateOrCreate(
            ['module' => 'laporan', 'dataset_key' => 'kpi_summary'],
            ['payload' => [
                'permohonanBulanIni' => 128,
                'kelulusanRate' => 72.5,
                'kutipanBulanIni' => 2450000,
                'npfRate' => 3.2,
            ]]
        );

        SpptDataset::updateOrCreate(
            ['module' => 'audit', 'dataset_key' => 'audit_trail'],
            ['payload' => [
                ['id' => 'AUD-001', 'tarikh' => '2 Jul 2024', 'pengguna' => 'admin', 'modul' => 'Permohonan', 'tindakan' => 'Kemaskini status', 'jenisTindakan' => 'update'],
                ['id' => 'AUD-002', 'tarikh' => '1 Jul 2024', 'pengguna' => 'pegawai1', 'modul' => 'Pengeluaran Dana', 'tindakan' => 'Lulus batch', 'jenisTindakan' => 'approve'],
            ]]
        );
    }
}
