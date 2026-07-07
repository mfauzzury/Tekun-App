<?php

namespace Tests\Unit;

use App\Models\AkaunPembiayaan;
use App\Models\SpptDataset;
use App\Services\HardRuleCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HardRuleCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluate_rejects_age_outside_limits(): void
    {
        $service = new HardRuleCheckService;

        $result = $service->evaluate(['umur' => 70, 'no_kp' => '850101145678']);

        $this->assertFalse($result['eligible']);
        $this->assertTrue($result['autoReject']);
        $this->assertContains('age_limit', $result['failedRules']);
    }

    public function test_evaluate_rejects_blacklisted_ic(): void
    {
        $service = new HardRuleCheckService;

        $result = $service->evaluate(['umur' => 30, 'no_kp' => '800101-01-0001']);

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('senarai hitam', $result['reasons'][0]);
    }

    public function test_evaluate_rejects_high_commitment_ratio(): void
    {
        $service = new HardRuleCheckService;

        $result = $service->evaluate([
            'umur' => 30,
            'no_kp' => '850101145678',
            'pendapatan_bulanan' => 1000,
            'jumlah_komitmen_sedia_ada' => 800,
        ]);

        $this->assertFalse($result['eligible']);
        $this->assertContains('commitment_ratio', $result['failedRules']);
    }

    public function test_evaluate_passes_valid_profile(): void
    {
        $service = new HardRuleCheckService;

        $result = $service->evaluate([
            'umur' => 35,
            'no_kp' => '850101145678',
            'pendapatan_bulanan' => 5000,
            'jumlah_komitmen_sedia_ada' => 1000,
        ]);

        $this->assertTrue($result['eligible']);
        $this->assertFalse($result['autoReject']);
        $this->assertSame([], $result['reasons']);
    }

    public function test_evaluate_uses_database_rules_when_configured(): void
    {
        SpptDataset::create([
            'module' => 'setup',
            'dataset_key' => 'saringan_auto_kelayakan',
            'payload' => [
                'active' => true,
                'rules' => [
                    [
                        'code' => 'age_limit',
                        'label' => 'Had umur',
                        'active' => true,
                        'sort' => 1,
                        'config' => ['min_age' => 21, 'max_age' => 55],
                    ],
                ],
            ],
        ]);

        $service = new HardRuleCheckService;
        $result = $service->evaluate(['umur' => 20, 'no_kp' => '850101145678']);

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('21', $result['reasons'][0]);
        $this->assertStringContainsString('55', $result['reasons'][0]);
    }

    public function test_evaluate_checks_active_financing_from_database_by_ic(): void
    {
        SpptDataset::create([
            'module' => 'setup',
            'dataset_key' => 'saringan_auto_kelayakan',
            'payload' => [
                'active' => true,
                'rules' => [
                    [
                        'code' => 'active_financing_limit',
                        'label' => 'Had pembiayaan aktif',
                        'active' => true,
                        'sort' => 1,
                        'config' => ['max_active_count' => 0, 'max_total_amount' => 100000],
                    ],
                ],
            ],
        ]);

        AkaunPembiayaan::create([
            'no_akaun' => 'AK-001',
            'ic' => '850101145678',
            'nama' => 'Test Usahawan',
            'jumlah_pembiayaan' => 50000,
            'status' => 'Aktif',
        ]);

        $service = new HardRuleCheckService;
        $result = $service->evaluate(['no_kp' => '850101-14-5678']);

        $this->assertFalse($result['eligible']);
        $this->assertContains('active_financing_limit', $result['failedRules']);
    }
}
