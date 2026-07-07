<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\WfAuthorizedRole;
use App\Models\WfProcess;
use App\Models\WfProcessDetail;
use App\Models\WfWorkflowName;
use Illuminate\Database\Seeder;

class TekunNiagaWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        WfWorkflowName::updateOrCreate(
            ['wfa_workflow_code' => 'TEKUN_NIAGA'],
            [
                'wfa_workflow_title' => 'SKIM PEMBIAYAAN TEKUN NIAGA',
                'wfa_prevent_self_process' => 0,
                'wfa_involve_posting' => 1,
            ],
        );

        $semakan = $this->upsertProcess('TEKUN_NIAGA', 'SEMAKAN', 1, [
            'wfp_process_desc_bm' => 'Semakan permohonan di peringkat cawangan',
            'wfp_is_by_ptj' => 1,
            'wfp_extended_field' => [
                'permohonan_stage' => 'semakan',
                'permohonan_status' => 'Dalam Proses',
                'permohonan_permission' => 'sppt.semakan',
                'permohonan_route' => '/admin/permohonan/semakan',
            ],
        ]);

        $sokongan = $this->upsertProcess('TEKUN_NIAGA', 'SOKONGAN', 2, [
            'wfp_process_desc_bm' => 'Sokongan permohonan di peringkat cawangan',
            'wfp_is_by_ptj' => 1,
            'wfp_extended_field' => [
                'permohonan_stage' => 'sokongan',
                'permohonan_status' => 'Disemak',
                'permohonan_permission' => 'sppt.sokongan',
                'permohonan_route' => '/admin/permohonan/sokongan',
            ],
        ]);

        $kelulusan = $this->upsertProcess('TEKUN_NIAGA', 'KELULUSAN', 3, [
            'wfp_process_desc_bm' => 'Kelulusan permohonan (semua cawangan)',
            'wfp_is_by_ptj' => 0,
            'wfp_extended_field' => [
                'permohonan_stage' => 'kelulusan',
                'permohonan_status' => 'Disokong',
                'permohonan_permission' => 'sppt.kelulusan',
                'permohonan_route' => '/admin/permohonan/kelulusan',
            ],
        ]);

        $this->upsertDetail($semakan, 'APPROVE', 1, $sokongan, 'Disemak', 'lulus');
        $this->upsertDetail($semakan, 'REJECT', 2, null, 'Ditolak', 'tolak');

        $this->upsertDetail($sokongan, 'APPROVE', 1, $kelulusan, 'Disokong', 'lulus');
        $this->upsertDetail($sokongan, 'REJECT', 2, null, 'Ditolak', 'tolak');

        $this->upsertDetail($kelulusan, 'APPROVE', 1, null, 'Diluluskan', 'lulus');
        $this->upsertDetail($kelulusan, 'REJECT', 2, null, 'Ditolak', 'tolak');

        $this->upsertAuthorizedRole($semakan, 'penyemak');
        $this->upsertAuthorizedRole($sokongan, 'penyokong');
        $this->upsertAuthorizedRole($kelulusan, 'pelulus');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertProcess(string $workflowCode, string $name, int $sequence, array $attributes): WfProcess
    {
        return WfProcess::updateOrCreate(
            [
                'wfp_workflow_code' => $workflowCode,
                'wfp_process_name' => $name,
            ],
            array_merge([
                'wfp_sequence' => $sequence,
                'wfp_status' => '1',
                'wfp_is_email_notification' => 1,
                'wfp_is_todo_notification' => 1,
            ], $attributes),
        );
    }

    private function upsertDetail(
        WfProcess $process,
        string $statusCode,
        int $order,
        ?WfProcess $reroute,
        string $permohonanStatus,
        string $keputusan,
    ): void {
        WfProcessDetail::updateOrCreate(
            [
                'wpd_process_id' => $process->wfp_process_id,
                'wpd_status_code' => $statusCode,
            ],
            [
                'wpd_reroute_process' => $reroute?->wfp_process_id,
                'wpd_order' => $order,
                'wpd_extended_field' => [
                    'wpd_status_desc' => $statusCode === 'APPROVE' ? 'Lulus' : 'Tolak',
                    'permohonan_status' => $permohonanStatus,
                    'keputusan' => $keputusan,
                ],
            ],
        );
    }

    private function upsertAuthorizedRole(WfProcess $process, string $roleName): void
    {
        if (! Role::where('name', $roleName)->exists()) {
            return;
        }

        WfAuthorizedRole::updateOrCreate(
            [
                'war_process_id' => $process->wfp_process_id,
                'war_group_code' => $roleName,
            ],
            [
                'war_limit_min' => null,
                'war_limit_max' => null,
            ],
        );
    }
}
