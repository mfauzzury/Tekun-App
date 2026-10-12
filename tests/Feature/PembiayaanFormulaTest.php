<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PembiayaanFormulaTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(array $permissions = null): User
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => $permissions ?? ['sppt.view'],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_formula_calculate_requires_auth(): void
    {
        $this->postJson('/api/sppt/pembiayaan-formula/calculate', [
            'jumlah_pokok' => 50000,
        ])->assertStatus(401);
    }

    public function test_formula_calculate_validation_error(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/sppt/pembiayaan-formula/calculate', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_formula_calculate_success(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/sppt/pembiayaan-formula/calculate', [
            'jumlah_pokok' => 100000,
            'kadar_keuntungan_tahunan' => 4,
            'tempoh_bulan' => 36,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'input',
                    'flatRate' => ['method', 'ansuranBulanan', 'jadual'],
                    'reducingBalance' => ['method', 'ansuranBulanan', 'jadual'],
                    'perbandingan' => ['bezaAnsuranBulanan', 'ringkasan'],
                    'calculatedAt',
                ],
            ]);

        $this->assertCount(36, $response->json('data.flatRate.jadual'));
        $this->assertCount(36, $response->json('data.reducingBalance.jadual'));
    }

    public function test_formula_forbidden_without_view_permission(): void
    {
        $this->actingAsAdmin(['posts.view']);

        $this->postJson('/api/sppt/pembiayaan-formula/calculate', [
            'jumlah_pokok' => 50000,
        ])->assertStatus(403);
    }

    public function test_formula_defaults(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/sppt/pembiayaan-formula/defaults')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['defaultKadarKeuntunganTahunan', 'defaultTempohBulan', 'tempohBulanOptions'],
            ]);
    }
}
