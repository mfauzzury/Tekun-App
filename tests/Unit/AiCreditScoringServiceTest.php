<?php

namespace Tests\Unit;

use App\Services\AiCreditScoringService;
use App\Services\AiRiskScoringService;
use App\Services\ApkCreditEnrichmentService;
use App\Services\EdgeCaseMitigationService;
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
            new EdgeCaseMitigationService,
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
            new EdgeCaseMitigationService,
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

    public function test_borderline_gray_zone_includes_mitigation_suggestions(): void
    {
        config(['services.anthropic.key' => '']);

        $service = new AiCreditScoringService(
            new AiRiskScoringService,
            new ApkCreditEnrichmentService,
            new EdgeCaseMitigationService,
        );

        $result = $service->score([
            'umur' => 32,
            'no_kp' => '750101145678',
            'pendapatan_bulanan' => 2800,
            'jumlah_komitmen_sedia_ada' => 1600,
            'tempoh_perniagaan_tahun' => 2,
            'jumlah_permohonan' => 70000,
        ]);

        $this->assertSame('officer_review', $result['recommended_action']);
        $this->assertTrue($result['edge_case']['is_borderline']);
        $this->assertSame('gray', $result['edge_case']['zone']);
        $this->assertTrue($result['edge_case']['auto_approve_blocked']);
        $this->assertNotEmpty($result['edge_case']['mitigations']);

        $codes = array_column($result['edge_case']['mitigations'], 'code');
        $this->assertContains('reduce_amount', $codes);
        $this->assertContains('additional_guarantor', $codes);
    }
}
