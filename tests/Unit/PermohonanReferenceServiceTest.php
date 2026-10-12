<?php

namespace Tests\Unit;

use App\Models\Permohonan;
use App\Services\PermohonanReferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermohonanReferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ignores_workflow_demo_refs_when_generating_next_numeric_reference(): void
    {
        $year = now()->format('Y');

        Permohonan::create([
            'no_rujukan' => "PM-{$year}-WF-SEM-003",
            'nama' => 'Workflow sample',
            'status' => 'Draf',
        ]);
        Permohonan::create([
            'no_rujukan' => "PM-{$year}-0004",
            'nama' => 'Staff draft',
            'status' => 'Draf',
        ]);

        $next = app(PermohonanReferenceService::class)->generateNextNoRujukan((int) $year);

        $this->assertSame("PM-{$year}-0005", $next);
    }

    public function test_skips_existing_numeric_gaps(): void
    {
        $year = now()->format('Y');

        Permohonan::create([
            'no_rujukan' => "PM-{$year}-0001",
            'nama' => 'First',
            'status' => 'Draf',
        ]);
        Permohonan::create([
            'no_rujukan' => "PM-{$year}-0003",
            'nama' => 'Third',
            'status' => 'Draf',
        ]);

        $next = app(PermohonanReferenceService::class)->generateNextNoRujukan((int) $year);

        $this->assertSame("PM-{$year}-0004", $next);
    }
}
