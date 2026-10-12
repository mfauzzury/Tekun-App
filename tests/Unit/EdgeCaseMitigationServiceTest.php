<?php

namespace Tests\Unit;

use App\Services\EdgeCaseMitigationService;
use Tests\TestCase;

class EdgeCaseMitigationServiceTest extends TestCase
{
    public function test_non_borderline_returns_empty_mitigations(): void
    {
        $service = new EdgeCaseMitigationService;

        $result = $service->analyze(
            ['jumlah_permohonan' => 20000, 'pendapatan_bulanan' => 5000],
            85,
            ['recommended_action' => 'auto_approve'],
            30000,
            ['flags' => []],
        );

        $this->assertFalse($result['is_borderline']);
        $this->assertSame([], $result['mitigations']);
    }

    public function test_gray_zone_builds_structured_mitigations(): void
    {
        $service = new EdgeCaseMitigationService;

        $result = $service->analyze(
            [
                'jumlah_permohonan' => 70000,
                'pendapatan_bulanan' => 2800,
                'jumlah_komitmen_sedia_ada' => 1600,
            ],
            68,
            ['recommended_action' => 'officer_review'],
            45000,
            ['flags' => ['Skor CCRIS rendah']],
        );

        $this->assertTrue($result['is_borderline']);
        $this->assertSame('gray', $result['zone']);
        $this->assertTrue($result['auto_approve_blocked']);

        $reduce = collect($result['mitigations'])->firstWhere('code', 'reduce_amount');
        $this->assertNotNull($reduce);
        $this->assertArrayHasKey('suggested_amount', $reduce);
        $this->assertArrayHasKey('suggested_amount_label', $reduce);
    }
}
