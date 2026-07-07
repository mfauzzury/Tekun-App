<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiRiskScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_risk_scoring_returns_score(): void
    {
        config(['services.anthropic.key' => '']);

        $response = $this->postJson('/api/public/sppt/risk-scoring/score', [
            'umur' => 35,
            'noKp' => '850101145678',
            'pendapatanBulanan' => 5000,
            'jumlahKomitmenSediaAda' => 800,
            'jumlahPermohonan' => 35000,
            'tempohPerniagaanTahun' => 4,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'riskScore',
                    'riskCategory',
                    'recommendedLimit',
                    'factors',
                    'confidence',
                    'source',
                    'scoredAt',
                    'message',
                ],
            ])
            ->assertJsonPath('data.source', 'heuristic');
    }

    public function test_admin_risk_scoring_requires_auth(): void
    {
        $response = $this->postJson('/api/sppt/permohonan/risk-scoring/score', [
            'umur' => 30,
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_score_permohonan_and_persist_result(): void
    {
        config(['services.anthropic.key' => '']);

        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => ['sppt.view', 'sppt.edit'],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        $permohonan = Permohonan::create([
            'no_rujukan' => 'PM-2026-0001',
            'nama' => 'Ahmad Test',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'status' => 'Dalam Proses',
            'jumlah_permohonan' => 40000,
            'details' => [
                'umur' => 38,
                'no_ic_baru' => '850101145678',
                'pendapatan' => 60000,
                'pendapatan_bulan' => 12,
                'sektor_perniagaan' => 'Peruncitan',
                'tempoh_perniagaan_tahun' => 3,
                'negeri' => 'Selangor',
            ],
        ]);

        $response = $this->postJson("/api/sppt/permohonan/{$permohonan->id}/risk-scoring");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'riskScore',
                    'riskCategory',
                    'recommendedLimit',
                ],
            ]);

        $score = $response->json('data.riskScore');
        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);

        $permohonan->refresh();
        $this->assertIsArray($permohonan->details['ai_risk_scoring'] ?? null);
        $this->assertArrayHasKey('risk_score', $permohonan->details['ai_risk_scoring']);
    }

    public function test_risk_scoring_validation_error_for_invalid_permohonan_id(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => ['sppt.view'],
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/sppt/permohonan/risk-scoring/score', [
            'permohonanId' => 99999,
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}
