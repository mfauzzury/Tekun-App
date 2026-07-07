<?php

namespace Tests\Unit;

use App\Services\AiRiskScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiRiskScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_heuristic_scores_low_risk_for_strong_profile(): void
    {
        config(['services.anthropic.key' => '']);

        $service = new AiRiskScoringService;
        $result = $service->score([
            'umur' => 35,
            'no_kp' => '850101145678',
            'pendapatan_bulanan' => 6000,
            'jumlah_komitmen_sedia_ada' => 500,
            'tempoh_perniagaan_tahun' => 5,
            'jumlah_permohonan' => 30000,
            'negeri' => 'Selangor',
        ]);

        $this->assertGreaterThanOrEqual(75, $result['risk_score']);
        $this->assertSame('Risiko Rendah', $result['risk_category']);
        $this->assertSame('heuristic', $result['source']);
        $this->assertNotEmpty($result['factors']);
    }

    public function test_heuristic_scores_high_risk_for_weak_profile(): void
    {
        config(['services.anthropic.key' => '']);

        $service = new AiRiskScoringService;
        $result = $service->score([
            'umur' => 19,
            'pendapatan_bulanan' => 1200,
            'jumlah_komitmen_sedia_ada' => 1000,
            'tempoh_perniagaan_tahun' => 0,
            'jumlah_permohonan' => 50000,
            'muflis' => true,
        ]);

        $this->assertLessThan(60, $result['risk_score']);
        $this->assertSame('Risiko Tinggi', $result['risk_category']);
    }

    public function test_ai_scoring_uses_anthropic_when_configured(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'risk_score' => 82,
                        'risk_category' => 'Risiko Rendah',
                        'recommended_limit' => 45000,
                        'confidence' => 88,
                        'factors' => [
                            ['factor' => 'Pendapatan', 'impact' => 'positif', 'description' => 'Pendapatan stabil.'],
                        ],
                    ], JSON_THROW_ON_ERROR),
                ]],
            ], 200),
        ]);

        config(['services.anthropic.key' => 'test-key']);

        $service = new AiRiskScoringService;
        $result = $service->score([
            'umur' => 40,
            'pendapatan_bulanan' => 5000,
            'jumlah_permohonan' => 40000,
        ]);

        $this->assertSame(82, $result['risk_score']);
        $this->assertSame('Risiko Rendah', $result['risk_category']);
        $this->assertSame('ai', $result['source']);
        $this->assertSame(45000.0, $result['recommended_limit']);
    }
}
