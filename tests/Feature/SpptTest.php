<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpptTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
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
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_sppt_dashboard_requires_auth(): void
    {
        $this->getJson('/api/sppt/dashboard/summary')->assertStatus(401);
    }

    public function test_sppt_dashboard_returns_summary(): void
    {
        $this->actingAsAdmin();
        $this->seed(\Database\Seeders\SpptSeeder::class);

        $response = $this->getJson('/api/sppt/dashboard/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'permohonanDalamProses',
                    'akaunAktif',
                    'kutipanBulanIni',
                    'tunggakan',
                ],
            ]);
    }

    public function test_sppt_reference_data_returns_options(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/sppt/reference-data');

        $response->assertStatus(200)
            ->assertJsonPath('data.negeriOptions.0', 'Johor');
    }

    public function test_permohonan_create_validation_error(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/sppt/permohonan', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_permohonan_create_success(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/sppt/permohonan', [
            'nama' => 'Test Pemohon',
            'kategoriPembiayaan' => 'TEKUN Niaga',
            'jumlahPermohonan' => 10000,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nama', 'Test Pemohon');
    }

    public function test_sppt_forbidden_without_permission(): void
    {
        $role = Role::create([
            'name' => 'viewer',
            'description' => 'Viewer',
            'permissions' => [],
        ]);

        $user = User::factory()->create([
            'role' => 'viewer',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/sppt/dashboard/summary')->assertStatus(403);
    }
}
