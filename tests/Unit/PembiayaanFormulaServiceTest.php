<?php

namespace Tests\Unit;

use App\Services\PembiayaanFormulaService;
use Tests\TestCase;

class PembiayaanFormulaServiceTest extends TestCase
{
    public function test_flat_rate_calculation_and_schedule_totals(): void
    {
        $service = new PembiayaanFormulaService;
        $result = $service->flatRate(100000, 4, 36);

        $this->assertSame('flat_rate', $result['method']);
        $this->assertSame(12000.0, $result['jumlah_keuntungan']);
        $this->assertSame(112000.0, $result['jumlah_bayaran_balik']);
        $this->assertSame(3111.11, $result['ansuran_bulanan']);
        $this->assertCount(36, $result['jadual']);

        $sumPokok = array_sum(array_column($result['jadual'], 'pokok'));
        $sumKeuntungan = array_sum(array_column($result['jadual'], 'keuntungan'));
        $this->assertEqualsWithDelta(100000, $sumPokok, 0.05);
        $this->assertEqualsWithDelta(12000, $sumKeuntungan, 0.05);
        $this->assertSame(0.0, $result['jadual'][35]['baki_pokok']);
        $this->assertSame(0.0, $result['jadual'][35]['baki_bayaran_balik']);
    }

    public function test_reducing_balance_schedule_pays_off_principal(): void
    {
        $service = new PembiayaanFormulaService;
        $result = $service->reducingBalance(100000, 4, 36);

        $this->assertSame('reducing_balance', $result['method']);
        $this->assertGreaterThan(0, $result['ansuran_bulanan']);
        $this->assertLessThan(3111.11, $result['ansuran_bulanan']);
        $this->assertCount(36, $result['jadual']);

        $sumPokok = array_sum(array_column($result['jadual'], 'pokok'));
        $this->assertEqualsWithDelta(100000, $sumPokok, 0.05);
        $this->assertSame(0.0, $result['jadual'][35]['baki_pokok']);
        $this->assertSame(0.0, $result['jadual'][35]['baki_bayaran_balik']);
    }

    public function test_compare_returns_both_methods(): void
    {
        $service = new PembiayaanFormulaService;
        $result = $service->compare(50000, 4, 24);

        $this->assertArrayHasKey('flat_rate', $result);
        $this->assertArrayHasKey('reducing_balance', $result);
        $this->assertArrayHasKey('perbandingan', $result);
        $this->assertGreaterThan(
            (float) $result['reducing_balance']['ansuran_bulanan'],
            (float) $result['flat_rate']['ansuran_bulanan'],
        );
    }
}
