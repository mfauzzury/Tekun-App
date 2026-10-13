<?php

namespace Database\Seeders;

use App\Models\Permohonan;
use Illuminate\Database\Seeder;

/**
 * POC C4 / panel case K2 — synthetic syndicate scenario for the Disbursement Alert demo.
 *
 * All names, MyKad numbers, phones and accounts are fictitious. Not registered in DatabaseSeeder;
 * run explicitly: php artisan db:seed --class=PocSindiketDemoSeeder
 */
class PocSindiketDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sharedAddress = [
            'No 12, Jln Mawar 3, Tmn Sri Mawar',
            'NO. 12 JALAN MAWAR 3 TAMAN SRI MAWAR',
            'No.12, Jalan Mawar 3, Taman Sri Mawar',
            'no 12 jln mawar 3 tmn sri mawar',
        ];
        $sharedPhone = ['012-345 6789', '60123456789', '+6012-3456789', '0123456789'];

        $rows = [
            [
                'no_rujukan' => 'PM-POC-K2-001',
                'nama' => 'Mohd Faizal Bin Rahmat',
                'ic' => '880312105523',
                'jantina' => 'Lelaki',
                'alamat' => $sharedAddress[0],
                'poskod' => '43000',
                'telefon' => $sharedPhone[0],
                'email' => 'faizal.rahmat88@example.com',
                'bank' => ['Maybank', '162001234501'],
                'perniagaan' => ['Faizal Burger Station', 3],
                'jumlah' => 15000,
                'hari' => 12,
            ],
            [
                'no_rujukan' => 'PM-POC-K2-002',
                'nama' => 'Nurul Ain Binti Kassim',
                'ic' => '910728106214',
                'jantina' => 'Perempuan',
                'alamat' => $sharedAddress[1],
                'poskod' => '43000',
                'telefon' => $sharedPhone[1],
                'email' => 'nurulain.kassim@example.com',
                'bank' => ['CIMB Bank', '7600234502'],
                'perniagaan' => ['Ain Kek & Pastri', 2],
                'jumlah' => 20000,
                'hari' => 10,
            ],
            [
                'no_rujukan' => 'PM-POC-K2-003',
                'nama' => 'Hairul Nizam Bin Othman',
                'ic' => '850115107731',
                'jantina' => 'Lelaki',
                'alamat' => $sharedAddress[2],
                'poskod' => '43000',
                'telefon' => $sharedPhone[2],
                'email' => 'hairul.othman85@example.com',
                'bank' => ['Bank Islam', '12029010234503'],
                'perniagaan' => ['HN Cuci Kereta', 4],
                'jumlah' => 18000,
                'hari' => 7,
            ],
            [
                'no_rujukan' => 'PM-POC-K2-004',
                'nama' => 'Siti Hajar Binti Mansor',
                'ic' => '930204108842',
                'jantina' => 'Perempuan',
                'alamat' => $sharedAddress[3],
                'poskod' => '43000',
                'telefon' => $sharedPhone[3],
                'email' => 'sitihajar.mansor@example.com',
                'bank' => ['BSN', '0410029345504'],
                'perniagaan' => ['Hajar Tudung Online', 2],
                'jumlah' => 15000,
                'hari' => 4,
            ],
            [
                // Reuses PM-POC-K2-001's MyKad under a different name.
                'no_rujukan' => 'PM-POC-K2-005',
                'nama' => 'Zulkifli Bin Hamzah',
                'ic' => '880312105523',
                'jantina' => 'Lelaki',
                'alamat' => 'Lot 88, Kg Sungai Serai',
                'poskod' => '43100',
                'telefon' => '019-882 7710',
                'email' => 'zul.hamzah@example.com',
                'bank' => ['RHB Bank', '25801200345505'],
                'perniagaan' => ['Zul Kedai Runcit', 3],
                'jumlah' => 25000,
                'hari' => 2,
            ],
            [
                'no_rujukan' => 'PM-POC-K1-001',
                'nama' => 'Rosnah Binti Abdul Latif',
                'ic' => '790605106628',
                'jantina' => 'Perempuan',
                'alamat' => 'No 5, Jalan Cempaka 7, Taman Cempaka Indah',
                'poskod' => '43200',
                'telefon' => '017-650 2291',
                'email' => 'rosnah.latif@example.com',
                'bank' => ['Bank Rakyat', '220145678906'],
                'perniagaan' => ['Rosnah Nasi Lemak (Pasar Malam Batu 9)', 8],
                'jumlah' => 10000,
                'hari' => 9,
            ],
        ];

        foreach ($rows as $row) {
            $createdAt = now()->subDays($row['hari'])->setTime(10, 15);

            $details = [
                'nama' => $row['nama'],
                'no_ic_baru' => $row['ic'],
                'jantina' => $row['jantina'],
                'bangsa' => 'Melayu',
                'alamat' => $row['alamat'],
                'poskod' => $row['poskod'],
                'negeri' => 'Selangor',
                'no_telefon_bimbit' => $row['telefon'],
                'email' => $row['email'],
                'nama_bank' => $row['bank'][0],
                'no_akaun_bank' => $row['bank'][1],
                'nama_perniagaan' => $row['perniagaan'][0],
                'tempoh_berniaga' => $row['perniagaan'][1],
                'pendapatan' => 2500,
                'pendapatan_bulan' => 1,
                'jumlah_permohonan' => $row['jumlah'],
                'tempoh_pembiayaan' => 24,
                'attachments' => [],
            ];

            $permohonan = Permohonan::updateOrCreate(
                ['no_rujukan' => $row['no_rujukan']],
                [
                    'nama' => $row['nama'],
                    'kategori_pembiayaan' => 'TEKUN Niaga',
                    'status' => 'Dalam Proses',
                    'negeri' => 'Selangor',
                    'cawangan' => 'TEKUN Nasional Cawangan Hulu Langat',
                    'wf_workflow_code' => null,
                    'jumlah_permohonan' => $row['jumlah'],
                    'tarikh_permohonan' => $createdAt->toDateString(),
                    'details' => $details,
                    'pemohon_email' => $row['email'],
                    'pemohon_telefon' => $row['telefon'],
                ],
            );

            $permohonan->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
        }
    }
}
