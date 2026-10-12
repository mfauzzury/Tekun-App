<?php

namespace Database\Seeders;

use App\Models\Permohonan;
use App\Models\SpptCawangan;
use App\Models\WfProcess;
use App\Services\PermohonanWorkflowService;
use Illuminate\Database\Seeder;

class TekunNiagaWorkflowSampleSeeder extends Seeder
{
    public function run(): void
    {
        $workflowService = app(PermohonanWorkflowService::class);

        $semakanProcess = $workflowService->processForStage('TEKUN_NIAGA', PermohonanWorkflowService::STAGE_SEMAKAN);
        $sokonganProcess = $workflowService->processForStage('TEKUN_NIAGA', PermohonanWorkflowService::STAGE_SOKONGAN);
        $kelulusanProcess = $workflowService->processForStage('TEKUN_NIAGA', PermohonanWorkflowService::STAGE_KELULUSAN);

        if (! $semakanProcess || ! $sokonganProcess || ! $kelulusanProcess) {
            $this->command?->error('TEKUN Niaga workflow processes not found. Run TekunNiagaWorkflowSeeder first.');

            return;
        }

        $huluLangat = SpptCawangan::where('code', 'hulu-langat')->first();
        $gombak = SpptCawangan::where('code', 'gombak')->first();
        $shahAlam = SpptCawangan::where('code', 'shah-alam')->first();

        if (! $huluLangat) {
            $this->command?->error('Cawangan hulu-langat not found. Run CawanganSeeder first.');

            return;
        }

        $semakanSamples = [
            ['no_rujukan' => 'PM-2026-WF-SEM-001', 'nama' => 'Ahmad Faiz Bin Hassan', 'jumlah' => 25000, 'ic' => '850101-10-1234'],
            ['no_rujukan' => 'PM-2026-WF-SEM-002', 'nama' => 'Siti Nurhaliza Binti Omar', 'jumlah' => 18000, 'ic' => '900215-08-5678'],
            ['no_rujukan' => 'PM-2026-WF-SEM-003', 'nama' => 'Raj Kumar A/L Muthu', 'jumlah' => 32000, 'ic' => '880712-05-9012'],
            [
                'no_rujukan' => 'PM-2026-EDGE-001',
                'nama' => 'Hafiz Bin Roslan',
                'jumlah' => 70000,
                'ic' => '750101-14-5678',
                'edge_case_demo' => true,
            ],
        ];

        foreach ($semakanSamples as $index => $sample) {
            $this->upsertSample($sample, $huluLangat, [
                'status' => 'Dalam Proses',
                'wf_workflow_code' => 'TEKUN_NIAGA',
                'wf_current_process_id' => $semakanProcess->wfp_process_id,
                'tarikh_permohonan' => now()->subDays(5 - $index)->toDateString(),
            ]);
        }

        $sokonganSamples = [
            ['no_rujukan' => 'PM-2026-WF-SOK-001', 'nama' => 'Mohd Rizal Bin Ismail', 'jumlah' => 22000, 'ic' => '870303-10-3456'],
            ['no_rujukan' => 'PM-2026-WF-SOK-002', 'nama' => 'Nur Aisyah Binti Kamal', 'jumlah' => 15000, 'ic' => '910808-08-7890'],
            ['no_rujukan' => 'PM-2026-WF-SOK-003', 'nama' => 'Lee Wei Ming', 'jumlah' => 28000, 'ic' => '890425-14-2345'],
        ];

        foreach ($sokonganSamples as $index => $sample) {
            $this->upsertSample($sample, $huluLangat, [
                'status' => 'Disemak',
                'wf_workflow_code' => 'TEKUN_NIAGA',
                'wf_current_process_id' => $sokonganProcess->wfp_process_id,
                'tarikh_permohonan' => now()->subDays(12 - $index)->toDateString(),
                'workflow_history' => [
                    'semakan' => [
                        'keputusan' => 'lulus',
                        'catatan' => 'Dokumen lengkap — contoh data seeder.',
                        'by_user_name' => 'Penyemak — Hulu Langat',
                        'status' => 'Disemak',
                    ],
                ],
            ]);
        }

        $kelulusanSamples = [
            [
                'sample' => ['no_rujukan' => 'PM-2026-WF-KEL-001', 'nama' => 'Fatimah Binti Yusof', 'jumlah' => 35000, 'ic' => '820601-08-1122'],
                'cawangan' => $huluLangat,
            ],
            [
                'sample' => ['no_rujukan' => 'PM-2026-WF-KEL-002', 'nama' => 'Azman Bin Sulaiman', 'jumlah' => 20000, 'ic' => '860915-10-3344'],
                'cawangan' => $gombak,
            ],
            [
                'sample' => ['no_rujukan' => 'PM-2026-WF-KEL-003', 'nama' => 'Priya A/P Subramaniam', 'jumlah' => 27000, 'ic' => '920110-05-5566'],
                'cawangan' => $shahAlam,
            ],
        ];

        foreach ($kelulusanSamples as $index => $entry) {
            $cawangan = $entry['cawangan'] ?? $huluLangat;
            if (! $cawangan) {
                continue;
            }

            $this->upsertSample($entry['sample'], $cawangan, [
                'status' => 'Disokong',
                'wf_workflow_code' => 'TEKUN_NIAGA',
                'wf_current_process_id' => $kelulusanProcess->wfp_process_id,
                'tarikh_permohonan' => now()->subDays(20 - $index)->toDateString(),
                'workflow_history' => [
                    'semakan' => [
                        'keputusan' => 'lulus',
                        'catatan' => 'Semakan selesai.',
                        'by_user_name' => 'Penyemak Cawangan',
                        'status' => 'Disemak',
                    ],
                    'sokongan' => [
                        'keputusan' => 'lulus',
                        'catatan' => 'Disokong untuk kelulusan HQ.',
                        'by_user_name' => 'Penyokong Cawangan',
                        'status' => 'Disokong',
                    ],
                ],
            ]);
        }

        $this->command?->info('Created/updated 10 TEKUN Niaga workflow sample permohonan.');
        $this->command?->line('  Semakan (Hulu Langat): PM-2026-WF-SEM-001 .. SEM-003, PM-2026-EDGE-001 (kes sempadan)');
        $this->command?->line('  Sokongan (Hulu Langat): PM-2026-WF-SOK-001 .. SOK-003');
        $this->command?->line('  Kelulusan (pelbagai cawangan): PM-2026-WF-KEL-001 .. KEL-003');
    }

    /**
     * @param  array{no_rujukan: string, nama: string, jumlah: float|int, ic: string}  $sample
     * @param  array<string, mixed>  $workflow
     */
    private function upsertSample(array $sample, SpptCawangan $cawangan, array $workflow): void
    {
        $details = [
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'status_perniagaan' => 'sedang_berniaga',
            'sektor_perniagaan' => 'Peruncitan',
            'nama' => $sample['nama'],
            'no_ic_baru' => $sample['ic'],
            'no_telefon_bimbit' => '012-'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'email' => strtolower(str_replace([' ', "'"], ['.', ''], $sample['nama'])).'@contoh.com',
            'negeri' => $cawangan->negeri,
            'sample_data' => true,
        ];

        if (! empty($workflow['workflow_history'])) {
            $details['workflow'] = $workflow['workflow_history'];
        }

        if (! empty($sample['edge_case_demo'])) {
            $details['umur'] = 32;
            $details['pendapatan'] = 33600;
            $details['pendapatan_bulan'] = 12;
            $details['jumlah_komitmen_sedia_ada'] = 1600;
            $details['tempoh_perniagaan_tahun'] = 2;
            $details['edge_case_demo'] = true;
        }

        Permohonan::updateOrCreate(
            ['no_rujukan' => $sample['no_rujukan']],
            [
                'nama' => $sample['nama'],
                'kategori_pembiayaan' => 'TEKUN Niaga',
                'status' => $workflow['status'],
                'negeri' => $cawangan->negeri,
                'cawangan' => $cawangan->name,
                'wf_workflow_code' => $workflow['wf_workflow_code'],
                'wf_current_process_id' => $workflow['wf_current_process_id'],
                'jumlah_permohonan' => $sample['jumlah'],
                'tarikh_permohonan' => $workflow['tarikh_permohonan'],
                'details' => $details,
            ],
        );
    }
}
