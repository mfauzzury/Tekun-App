<?php

namespace Tests\Unit;

use App\Services\AiCreditScoringService;
use App\Services\AiRiskScoringService;
use App\Services\ApkCreditEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCreditScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_scoring_includes_apk_and_decision_engine(): void
    {
        config(['services.anthropic.key' => '']);

        $service = new AiCreditScoringService(
            new AiRiskScoringService,
            new ApkCreditEnrichmentService,
        );

        $result = $service->score([
            'umur' => 40,
            'no_kp' => '850101145678',
            'pendapatan_bulanan' => 6000,
            'jumlah_komitmen_sedia_ada' => 500,
            'tempoh_perniagaan_tahun' => 4,
            'jumlah_permohonan' => 35000,
        ]);

        $this->assertArrayHasKey('credit_score', $result);
        $this->assertArrayHasKey('recommended_action', $result);
        $this->assertArrayHasKey('decision_label', $result);
        $this->assertArrayHasKey('apk', $result);
        $this->assertArrayHasKey('ccris', $result['apk']);
        $this->assertArrayHasKey('ctos', $result['apk']);
        $this->assertContains($result['recommended_action'], ['auto_approve', 'officer_review', 'reject_escalate']);
        $this->assertContains($result['risk_band_color'], ['green', 'amber', 'red']);
    }

    public function test_critical_apk_flags_route_to_reject_escalate(): void
    {
        config(['services.anthropic.key' => '']);

        $service = new AiCreditScoringService(
            new AiRiskScoringService,
            new ApkCreditEnrichmentService,
        );

        $result = $service->score([
            'umur' => 40,
            'no_kp' => '800101010001',
            'pendapatan_bulanan' => 6000,
            'muflis' => true,
        ]);

        $this->assertSame('reject_escalate', $result['recommended_action']);
        $this->assertSame('red', $result['risk_band_color']);
    }
}
