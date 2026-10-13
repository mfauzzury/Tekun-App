<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use App\Services\AlternativeCreditScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AlternativeCreditScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.anthropic.key' => '', 'services.anthropic.model' => 'claude-haiku-4-5']);
    }

    private function service(): AlternativeCreditScoringService
    {
        return app(AlternativeCreditScoringService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function permohonan(array $overrides = [], float $jumlah = 5000, string $status = 'Dalam Proses'): Permohonan
    {
        $this->seq++;

        return Permohonan::create([
            'no_rujukan' => 'PM-ALT-'.str_pad((string) $this->seq, 4, '0', STR_PAD_LEFT),
            'nama' => 'Peniaga Pasar Malam '.$this->seq,
            'kategori_pembiayaan' => 'TEKUN Niaga',
            'status' => $status,
            'negeri' => 'Selangor',
            'jumlah_permohonan' => $jumlah,
            'details' => array_replace([
                'pendapatan' => 3200,
                'pendapatan_bulan' => 1,
                'komitmen_bulanan' => 300,
                'tempoh_berniaga' => '4 tahun',
                'tempoh_pembiayaan' => 24,
                'kaedah_perniagaan' => 'Pasar malam',
                'jantina' => 'L',
                'bangsa' => 'Melayu',
                'agama' => 'Islam',
                'negeri' => 'Selangor',
                'umur' => 38,
                'data_alternatif' => [
                    'ewallet' => ['penyedia' => "Touch 'n Go eWallet", 'purata_masuk_bulanan' => 2800, 'bilangan_transaksi_bulanan' => 120, 'bulan_aktif' => 10],
                    'duitnow_qr' => ['purata_jualan_bulanan' => 3000, 'bilangan_transaksi_bulanan' => 180, 'bulan_aktif' => 10, 'trend_pertumbuhan_peratus' => 8],
                    'bil_utiliti' => ['bulan_dibayar_tepat' => 11, 'bulan_dinilai' => 12],
                    'ccris_tersedia' => false,
                    'penyata_bank_tersedia' => false,
                ],
            ], $overrides),
        ]);
    }

    public function test_k1_night_market_trader_scores_well_from_alternative_data(): void
    {
        $result = $this->service()->score($this->permohonan(), false);

        $this->assertTrue($result['fail_nipis']);
        $this->assertGreaterThanOrEqual(75, $result['skor']);
        $this->assertContains($result['gred'], ['A', 'B']);
        $this->assertSame(3000.0, $result['pendapatan_disahkan_bulanan']);
        $this->assertGreaterThan(0, $result['cadangan_had']);
        $this->assertFalse($result['permohonan_melebihi_had']);
        $this->assertNotEmpty(array_filter($result['faktor'], fn (array $f) => $f['arah'] === 'naik'));
        $this->assertSame('TEKUN-ALT-SCORECARD v1', $result['model']['nama']);
        $this->assertSame('templat', $result['penjelasan_sumber']);
        $this->assertNotEmpty($result['penjelasan_ai']);
        $this->assertCount(7, $result['sumber_data']);

        $thin = collect($result['faktor'])->firstWhere('kod', 'fail_nipis');
        $this->assertSame(0.0, $thin['sumbangan']);
        $this->assertSame('neutral', $thin['arah']);

        $abs = array_map(fn (array $f) => abs($f['sumbangan']), $result['faktor']);
        $sorted = $abs;
        rsort($sorted);
        $this->assertSame($sorted, $abs);
    }

    public function test_thin_file_without_alternative_data_scores_lower_with_low_confidence(): void
    {
        $k1 = $this->service()->score($this->permohonan(), false);
        $none = $this->service()->score($this->permohonan([
            'data_alternatif' => ['ccris_tersedia' => false, 'penyata_bank_tersedia' => false],
        ]), false);

        $this->assertTrue($none['fail_nipis']);
        $this->assertLessThan($k1['skor'], $none['skor']);
        $this->assertLessThan(50, $none['keyakinan']);
        $this->assertSame(0.0, $none['pendapatan_disahkan_bulanan']);
        $thin = collect($none['faktor'])->firstWhere('kod', 'fail_nipis');
        $this->assertStringContainsString('data alternatif', $thin['penerangan']);
    }

    public function test_high_debt_service_ratio_lowers_score(): void
    {
        $result = $this->service()->score($this->permohonan([], 50000), false);

        $dsr = collect($result['faktor'])->firstWhere('kod', 'nisbah_khidmat_hutang');
        $this->assertSame('turun', $dsr['arah']);
        $this->assertGreaterThan(0.6, $result['nisbah_khidmat_hutang']);
        $this->assertTrue($result['permohonan_melebihi_had']);
        $this->assertLessThan(50000, $result['cadangan_had']);
        $this->assertSame(0.0, fmod($result['cadangan_had'], 500));
    }

    public function test_counterfactual_bias_check_proves_invariance(): void
    {
        $bias = $this->service()->score($this->permohonan(), false)['bias'];

        $this->assertTrue($bias['lulus']);
        $this->assertCount(4, $bias['ujian_kontrafaktual']);
        foreach ($bias['ujian_kontrafaktual'] as $test) {
            $this->assertSame(0, $test['beza']);
            $this->assertNotSame($test['nilai_asal'], $test['nilai_uji']);
        }
        $this->assertSame(['jantina', 'bangsa', 'agama', 'negeri', 'umur'], array_column($bias['atribut_dikecualikan'], 'kod'));
    }

    public function test_contributions_sum_to_score_minus_base(): void
    {
        foreach ([[], ['data_alternatif' => []]] as $overrides) {
            $result = $this->service()->score($this->permohonan($overrides, 20000), false);
            $sum = array_sum(array_column($result['faktor'], 'sumbangan'));
            $this->assertEqualsWithDelta($result['skor'] - $result['skor_asas'], $sum, 2.0);
        }
    }

    public function test_ai_narrative_does_not_change_score(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode(['penjelasan' => 'Pemohon menunjukkan aliran jualan digital yang stabil.'])]],
                'model' => 'claude-haiku-4-5',
                'stop_reason' => 'end_turn',
            ]),
        ]);

        $permohonan = $this->permohonan();
        $withAi = $this->service()->score($permohonan, true);
        $plain = $this->service()->score($permohonan, false);

        $this->assertSame('ai', $withAi['penjelasan_sumber']);
        $this->assertSame('Pemohon menunjukkan aliran jualan digital yang stabil.', $withAi['penjelasan_ai']);
        $this->assertSame($plain['skor'], $withAi['skor']);
        $this->assertSame($plain['faktor'], $withAi['faktor']);
        Http::assertSentCount(1);
    }

    public function test_not_configured_uses_template_without_http(): void
    {
        Http::fake();

        $result = $this->service()->score($this->permohonan(), true);

        $this->assertSame('templat', $result['penjelasan_sumber']);
        $this->assertStringContainsString('Faktor yang menaikkan skor', (string) $result['penjelasan_ai']);
        Http::assertNothingSent();
    }

    public function test_fairness_report_structure(): void
    {
        foreach (['L', 'L', 'L', 'P', 'P', 'P'] as $i => $jantina) {
            $this->permohonan(['jantina' => $jantina, 'bangsa' => $i % 2 ? 'Cina' : 'Melayu']);
        }
        $this->permohonan([], 5000, 'Draf');

        $report = $this->service()->fairnessReport();

        $this->assertSame(6, $report['jumlah_permohonan']);
        $this->assertNotEmpty($report['kumpulan']);
        $this->assertSame(['jantina', 'bangsa', 'negeri'], array_column($report['nisbah_impak_buruk'], 'atribut'));
        $jantina = collect($report['kumpulan'])->where('atribut', 'jantina')->pluck('bilangan', 'nilai')->all();
        $this->assertSame(['Lelaki' => 3, 'Perempuan' => 3], $jantina);
        $negeri = collect($report['nisbah_impak_buruk'])->firstWhere('atribut', 'negeri');
        $this->assertTrue($negeri['lulus_peraturan_4_5']);
        $this->assertArrayHasKey('nota', $report);
        $this->assertArrayHasKey('dijana_pada', $report);
    }

    public function test_extract_from_csv_statement_coerces_numbers(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode([
                    'data' => [
                        'purata_jualan_bulanan' => 'RM 3,050.40',
                        'bilangan_transaksi_bulanan' => '182',
                        'bulan_aktif' => 10,
                        'trend_pertumbuhan_peratus' => '8.5',
                    ],
                    'bulan_dikesan' => ['2026-01', '2026-02', 'bukan-bulan'],
                    'catatan' => 'Jualan QR stabil.',
                ])]],
                'model' => 'claude-haiku-4-5',
                'stop_reason' => 'end_turn',
            ]),
        ]);

        $path = tempnam(sys_get_temp_dir(), 'qr').'.csv';
        file_put_contents($path, "tarikh,amaun\n2026-01-05,120.50\n2026-02-07,98.00\n");

        try {
            $result = $this->service()->extractFromStatement($path, 'text/csv', 'duitnow_qr');
        } finally {
            @unlink($path);
        }

        $this->assertTrue($result['ok']);
        $this->assertSame('duitnow_qr', $result['jenis']);
        $this->assertSame(3050.4, $result['data']['purata_jualan_bulanan']);
        $this->assertSame(182, $result['data']['bilangan_transaksi_bulanan']);
        $this->assertSame(10, $result['data']['bulan_aktif']);
        $this->assertSame(8.5, $result['data']['trend_pertumbuhan_peratus']);
        $this->assertSame(['2026-01', '2026-02'], $result['bulan_dikesan']);
        Http::assertSent(fn (Request $request) => str_contains(json_encode($request->data()), '2026-01-05'));
    }

    public function test_extract_from_statement_not_configured_returns_not_ok(): void
    {
        Http::fake();

        $result = $this->service()->extractFromStatement('/nonexistent.csv', 'text/csv', 'ewallet');

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }
}
