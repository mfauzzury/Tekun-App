<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiCreditScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_credit_scoring_requires_auth(): void
    {
        $this->postJson('/api/sppt/permohonan/credit-scoring/score', [
            'umur' => 30,
        ])->assertStatus(401);
    }

    public function test_admin_can_score_permohonan_credit_and_persist_result(): void
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
            'no_rujukan' => 'PM-2026-0002',
            'nama' => 'Siti Test',
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'status' => 'Dalam Proses',
            'jumlah_permohonan' => 40000,
            'details' => [
                'umur' => 38,
                'no_ic_baru' => '850101145678',
                'pendapatan' => 60000,
                'pendapatan_bulan' => 12,
                'no_ssm' => '201901234567',
            ],
        ]);

        $response = $this->postJson("/api/sppt/permohonan/{$permohonan->id}/credit-scoring");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'creditScore',
                    'creditCategory',
                    'recommendedLimit',
                    'riskBand',
                    'riskBandColor',
                    'recommendedAction',
                    'decisionLabel',
                    'decisionDescription',
                    'factors',
                    'adverseActionReasons',
                    'apk',
                ],
            ]);

        $permohonan->refresh();
        $this->assertIsArray($permohonan->details['ai_credit_scoring'] ?? null);
        $this->assertArrayHasKey('credit_score', $permohonan->details['ai_credit_scoring']);
    }
}
