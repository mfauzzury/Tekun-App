<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\SpptCawangan;
use App\Models\User;
use Database\Seeders\TekunNiagaWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermohonanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TekunNiagaWorkflowSeeder::class);
    }

    private function createCawangan(string $code, string $name): SpptCawangan
    {
        return SpptCawangan::create([
            'code' => $code,
            'name' => $name,
            'branch_type' => 'cawangan',
            'negeri' => 'Selangor',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function actingAsRole(string $roleName, array $permissions, ?SpptCawangan $cawangan = null): User
    {
        $role = Role::create([
            'name' => $roleName,
            'description' => ucfirst($roleName),
            'permissions' => $permissions,
        ]);

        $user = User::factory()->create([
            'role' => $roleName,
            'role_id' => $role->id,
            'sppt_cawangan_id' => $cawangan?->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_semakan_queue_is_scoped_to_officer_cawangan(): void
    {
        $huluLangat = $this->createCawangan('hulu-langat', 'TEKUN Nasional Cawangan Hulu Langat');
        $gombak = $this->createCawangan('gombak', 'TEKUN Nasional Cawangan Gombak');

        Permohonan::create([
            'no_rujukan' => 'PM-2026-0001',
            'nama' => 'Ali A',
            'status' => 'Dalam Proses',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'cawangan' => $huluLangat->name,
            'negeri' => 'Selangor',
            'wf_workflow_code' => 'TEKUN_NIAGA',
            'wf_current_process_id' => app(\App\Services\PermohonanWorkflowService::class)
                ->processForStage('TEKUN_NIAGA', 'semakan')?->wfp_process_id,
        ]);
        Permohonan::create([
            'no_rujukan' => 'PM-2026-0002',
            'nama' => 'Ali B',
            'status' => 'Dalam Proses',
            'cawangan' => $gombak->name,
            'negeri' => 'Selangor',
        ]);

        $this->actingAsRole('penyemak', ['sppt.view', 'sppt.edit', 'sppt.semakan'], $huluLangat);

        $response = $this->getJson('/api/sppt/permohonan?workflow_stage=semakan&limit=100');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Ali A');
    }

    public function test_sokongan_queue_shows_disemak_applications_in_cawangan(): void
    {
        $cawangan = $this->createCawangan('hulu-langat', 'TEKUN Nasional Cawangan Hulu Langat');

        Permohonan::create([
            'no_rujukan' => 'PM-2026-0003',
            'nama' => 'Siti A',
            'status' => 'Disemak',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'cawangan' => $cawangan->name,
            'negeri' => 'Selangor',
            'wf_workflow_code' => 'TEKUN_NIAGA',
            'wf_current_process_id' => app(\App\Services\PermohonanWorkflowService::class)
                ->processForStage('TEKUN_NIAGA', 'sokongan')?->wfp_process_id,
        ]);

        $this->actingAsRole('penyokong', ['sppt.view', 'sppt.edit', 'sppt.sokongan'], $cawangan);

        $response = $this->getJson('/api/sppt/permohonan?workflow_stage=sokongan&limit=100');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Siti A');
    }

    public function test_kelulusan_queue_shows_disokong_from_all_cawangan(): void
    {
        Permohonan::create([
            'no_rujukan' => 'PM-2026-0004',
            'nama' => 'Abu A',
            'status' => 'Disokong',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'cawangan' => 'TEKUN Nasional Cawangan Hulu Langat',
            'negeri' => 'Selangor',
            'wf_workflow_code' => 'TEKUN_NIAGA',
            'wf_current_process_id' => app(\App\Services\PermohonanWorkflowService::class)
                ->processForStage('TEKUN_NIAGA', 'kelulusan')?->wfp_process_id,
        ]);
        Permohonan::create([
            'no_rujukan' => 'PM-2026-0005',
            'nama' => 'Abu B',
            'status' => 'Disokong',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'cawangan' => 'TEKUN Nasional Cawangan Gombak',
            'negeri' => 'Selangor',
            'wf_workflow_code' => 'TEKUN_NIAGA',
            'wf_current_process_id' => app(\App\Services\PermohonanWorkflowService::class)
                ->processForStage('TEKUN_NIAGA', 'kelulusan')?->wfp_process_id,
        ]);

        $this->actingAsRole('pelulus', ['sppt.view', 'sppt.edit', 'sppt.kelulusan']);

        $response = $this->getJson('/api/sppt/permohonan?workflow_stage=kelulusan&limit=100');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_semakan_process_moves_status_to_disemak(): void
    {
        $cawangan = $this->createCawangan('hulu-langat', 'TEKUN Nasional Cawangan Hulu Langat');

        $permohonan = Permohonan::create([
            'no_rujukan' => 'PM-2026-0006',
            'nama' => 'Zainal',
            'status' => 'Dalam Proses',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'cawangan' => $cawangan->name,
            'negeri' => 'Selangor',
            'wf_workflow_code' => 'TEKUN_NIAGA',
            'wf_current_process_id' => app(\App\Services\PermohonanWorkflowService::class)
                ->processForStage('TEKUN_NIAGA', 'semakan')?->wfp_process_id,
        ]);

        $this->actingAsRole('penyemak', ['sppt.view', 'sppt.edit', 'sppt.semakan'], $cawangan);

        $response = $this->postJson("/api/sppt/permohonan/{$permohonan->id}/workflow", [
            'stage' => 'semakan',
            'keputusan' => 'lulus',
            'catatan' => 'Dokumen lengkap',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Disemak');

        $sokonganProcessId = app(\App\Services\PermohonanWorkflowService::class)
            ->processForStage('TEKUN_NIAGA', 'sokongan')?->wfp_process_id;

        $this->assertDatabaseHas('permohonan', [
            'id' => $permohonan->id,
            'status' => 'Disemak',
            'wf_workflow_code' => 'TEKUN_NIAGA',
            'wf_current_process_id' => $sokonganProcessId,
        ]);
    }

    public function test_submit_assigns_tekun_niaga_workflow(): void
    {
        $cawangan = $this->createCawangan('hulu-langat', 'TEKUN Nasional Cawangan Hulu Langat');

        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => [
                'sppt.view', 'sppt.create', 'sppt.edit', 'sppt.delete',
            ],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
            'sppt_cawangan_id' => $cawangan->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/sppt/permohonan', [
            'nama' => 'Pemohon TEKUN Niaga',
            'kategoriPembiayaan' => 'TEKUN Niaga',
            'status' => 'Dalam Proses',
        ]);

        $semakanProcessId = app(\App\Services\PermohonanWorkflowService::class)
            ->processForStage('TEKUN_NIAGA', 'semakan')?->wfp_process_id;

        $response->assertStatus(201)
            ->assertJsonPath('data.wfWorkflowCode', 'TEKUN_NIAGA')
            ->assertJsonPath('data.wfCurrentProcessId', $semakanProcessId);
    }

    public function test_workflow_queue_requires_permission(): void
    {
        $this->actingAsRole('viewer', ['sppt.view']);

        $this->getJson('/api/sppt/permohonan?workflow_stage=semakan')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }
}
