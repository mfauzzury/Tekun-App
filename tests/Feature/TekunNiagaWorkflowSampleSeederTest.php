<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\SpptCawangan;
use App\Models\User;
use Database\Seeders\CawanganSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TekunNiagaWorkflowSampleSeeder;
use Database\Seeders\TekunNiagaWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TekunNiagaWorkflowSampleSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CawanganSeeder::class);
        $this->seed(TekunNiagaWorkflowSeeder::class);
        $this->seed(TekunNiagaWorkflowSampleSeeder::class);
    }

    public function test_seeder_creates_nine_workflow_sample_permohonan(): void
    {
        $this->assertSame(3, Permohonan::where('status', 'Dalam Proses')->where('cawangan', 'TEKUN Nasional Cawangan Hulu Langat')->count());
        $this->assertSame(3, Permohonan::where('status', 'Disemak')->where('cawangan', 'TEKUN Nasional Cawangan Hulu Langat')->count());
        $this->assertSame(3, Permohonan::where('status', 'Disokong')->count());
    }

    public function test_semakan_queue_lists_hulu_langat_samples(): void
    {
        $cawangan = SpptCawangan::where('code', 'hulu-langat')->firstOrFail();
        $role = Role::where('name', 'penyemak')->firstOrFail();

        Sanctum::actingAs(User::factory()->create([
            'role' => 'penyemak',
            'role_id' => $role->id,
            'sppt_cawangan_id' => $cawangan->id,
        ]));

        $response = $this->getJson('/api/sppt/permohonan?workflow_stage=semakan&limit=100');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.noRujukan', 'PM-2026-WF-SEM-001');
    }

    public function test_kelulusan_queue_lists_disokong_from_multiple_cawangan(): void
    {
        $role = Role::where('name', 'pelulus')->firstOrFail();

        Sanctum::actingAs(User::factory()->create([
            'role' => 'pelulus',
            'role_id' => $role->id,
        ]));

        $response = $this->getJson('/api/sppt/permohonan?workflow_stage=kelulusan&limit=100');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $cawanganNames = collect($response->json('data'))->pluck('cawangan')->unique()->sort()->values()->all();

        $this->assertContains('TEKUN Nasional Cawangan Hulu Langat', $cawanganNames);
        $this->assertContains('TEKUN Nasional Cawangan Gombak', $cawanganNames);
        $this->assertContains('TEKUN Nasional Cawangan Shah Alam', $cawanganNames);
    }
}
