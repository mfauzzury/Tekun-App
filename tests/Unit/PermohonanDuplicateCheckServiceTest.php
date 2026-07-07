<?php

namespace Tests\Unit;

use App\Models\Permohonan;
use App\Models\Usahawan;
use App\Services\PermohonanDuplicateCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermohonanDuplicateCheckServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermohonanDuplicateCheckService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PermohonanDuplicateCheckService;
    }

    public function test_detects_duplicate_ic_across_permohonan(): void
    {
        Permohonan::create([
            'no_rujukan' => 'PM-2026-0001',
            'nama' => 'Ali Bin Halim',
            'status' => 'Draf',
            'details' => ['no_ic_baru' => '850101-14-5678'],
        ]);

        $errors = $this->service->findDuplicateFieldErrors([
            'no_ic_baru' => '850101145678',
        ]);

        $this->assertArrayHasKey('details.no_ic_baru', $errors);
        $this->assertStringContainsString('PM-2026-0001', $errors['details.no_ic_baru']);
    }

    public function test_allows_same_record_on_update(): void
    {
        $permohonan = Permohonan::create([
            'no_rujukan' => 'PM-2026-0002',
            'nama' => 'Ali Bin Halim',
            'status' => 'Draf',
            'details' => [
                'no_ic_baru' => '850101-14-5678',
                'no_telefon_bimbit' => '012-3456789',
                'email' => 'ali@contoh.com',
            ],
        ]);

        $errors = $this->service->findDuplicateFieldErrors([
            'no_ic_baru' => '850101145678',
            'no_telefon_bimbit' => '0123456789',
            'email' => 'ali@contoh.com',
        ], $permohonan->id);

        $this->assertSame([], $errors);
    }

    public function test_detects_duplicate_phone_and_email(): void
    {
        Permohonan::create([
            'no_rujukan' => 'PM-2026-0003',
            'nama' => 'Siti Aminah',
            'status' => 'Dalam Proses',
            'details' => [
                'no_ic_baru' => '900101-10-1234',
                'no_telefon_bimbit' => '0198765432',
                'email' => 'Siti@Contoh.COM',
            ],
        ]);

        $errors = $this->service->findDuplicateFieldErrors([
            'no_ic_baru' => '910101-10-9999',
            'no_telefon_bimbit' => '019-876 5432',
            'email' => 'siti@contoh.com',
        ]);

        $this->assertArrayHasKey('details.no_telefon_bimbit', $errors);
        $this->assertArrayHasKey('details.email', $errors);
        $this->assertArrayNotHasKey('details.no_ic_baru', $errors);
    }

    public function test_detects_duplicate_ic_in_usahawan(): void
    {
        Usahawan::create([
            'no_usahawan' => 'U-100',
            'nama' => 'Rekod Usahawan',
            'no_ic' => '780512-08-1234',
            'status' => 'Aktif',
        ]);

        $errors = $this->service->findDuplicateFieldErrors([
            'no_ic_baru' => '780512081234',
        ]);

        $this->assertArrayHasKey('details.no_ic_baru', $errors);
        $this->assertStringContainsString('usahawan', strtolower($errors['details.no_ic_baru']));
    }
}
