<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use App\Services\FraudNetworkService;
use Database\Seeders\PocSindiketDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FraudNetworkServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PocSindiketDemoSeeder::class);
    }

    private function service(): FraudNetworkService
    {
        return app(FraudNetworkService::class);
    }

    private function permohonan(string $noRujukan): Permohonan
    {
        return Permohonan::where('no_rujukan', $noRujukan)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, array<string, mixed>>
     */
    private function signalsByCode(array $result): array
    {
        return collect($result['isyarat'])->keyBy('kod')->all();
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(PocSindiketDemoSeeder::class);

        $this->assertSame(6, Permohonan::where('no_rujukan', 'like', 'PM-POC-K%')->count());
    }

    public function test_k2_syndicate_pattern_is_detected(): void
    {
        $result = $this->service()->analyze($this->permohonan('PM-POC-K2-001'));
        $signals = $this->signalsByCode($result);

        $this->assertArrayHasKey('KLUSTER_SINDIKET', $signals);
        $this->assertArrayHasKey('IC_GUNA_SEMULA', $signals);
        $this->assertArrayHasKey('HALAJU_PERMOHONAN', $signals);
        $this->assertSame('tinggi', $signals['ALAMAT_DIKONGSI']['tahap']);
        $this->assertSame('tinggi', $signals['TELEFON_DIKONGSI']['tahap']);
        $this->assertSame('tinggi', $signals['IC_GUNA_SEMULA']['tahap']);
        $this->assertStringContainsString('PM-POC-K2-005', implode(' ', $signals['IC_GUNA_SEMULA']['bukti']));
        $this->assertArrayNotHasKey('AKAUN_BANK_DIKONGSI', $signals);

        $this->assertTrue($result['amaran']);
        $this->assertSame('tinggi', $result['tahap']);
        $this->assertSame(100, $result['skor_risiko']);
        $this->assertGreaterThanOrEqual(4, $result['kluster']['saiz']);

        $related = collect($result['kluster']['permohonan_berkaitan'])->keyBy('no_rujukan');
        $this->assertTrue($related->has('PM-POC-K2-002'));
        $this->assertContains('telefon', $related['PM-POC-K2-002']['kaitan']);
        $this->assertContains('alamat', $related['PM-POC-K2-002']['kaitan']);
        $this->assertContains('ic', $related['PM-POC-K2-005']['kaitan']);
        $this->assertFalse($related->has('PM-POC-K1-001'));

        $this->assertContains('Tahan pengeluaran dana sehingga pengesahan identiti bersemuka', $result['cadangan_tindakan']);
        $this->assertContains('Rujuk kepada Unit Integriti', $result['cadangan_tindakan']);
        $this->assertNotFalse(\DateTimeImmutable::createFromFormat(DATE_ATOM, $result['dianalisis_pada']));
    }

    public function test_k2_graph_has_nodes_edges_and_masked_labels(): void
    {
        $graph = $this->service()->analyze($this->permohonan('PM-POC-K2-001'))['rangkaian'];

        $nodes = collect($graph['nodes']);
        $this->assertNotEmpty($graph['edges']);
        $this->assertLessThanOrEqual(40, $nodes->count());
        $this->assertSame($nodes->count(), $nodes->pluck('id')->unique()->count());
        $this->assertSame(1, $nodes->where('utama', true)->count());
        $this->assertSame(5, $nodes->where('jenis', 'pemohon')->count());

        $ids = $nodes->pluck('id')->all();
        foreach ($graph['edges'] as $edge) {
            $this->assertContains($edge['dari'], $ids);
            $this->assertContains($edge['ke'], $ids);
            $this->assertContains($edge['label'], ['telefon', 'alamat', 'ic', 'emel', 'akaun bank']);
        }

        $icNode = $nodes->firstWhere('jenis', 'ic');
        $this->assertSame('880312-**-**23', $icNode['label']);
        $this->assertSame('012-***6789', $nodes->firstWhere('jenis', 'telefon')['label']);
        $this->assertNotNull($nodes->firstWhere('jenis', 'alamat'));

        $json = json_encode($graph);
        $this->assertStringNotContainsString('880312105523', $json);
        $this->assertStringNotContainsString('0123456789', $json);
    }

    public function test_ic_reuse_applicant_is_linked_to_cluster(): void
    {
        $result = $this->service()->analyze($this->permohonan('PM-POC-K2-005'));
        $signals = $this->signalsByCode($result);

        $this->assertArrayHasKey('IC_GUNA_SEMULA', $signals);
        $this->assertArrayHasKey('KLUSTER_SINDIKET', $signals);
        $this->assertSame(5, $result['kluster']['saiz']);
        $this->assertTrue($result['amaran']);
    }

    public function test_clean_applicant_has_no_signals(): void
    {
        $result = $this->service()->analyze($this->permohonan('PM-POC-K1-001'));

        $this->assertSame([], $result['isyarat']);
        $this->assertFalse($result['amaran']);
        $this->assertSame('rendah', $result['tahap']);
        $this->assertSame(0, $result['skor_risiko']);
        $this->assertSame(1, $result['kluster']['saiz']);
        $this->assertSame([], $result['kluster']['permohonan_berkaitan']);
        $this->assertCount(1, $result['rangkaian']['nodes']);
        $this->assertSame([], $result['rangkaian']['edges']);
    }

    public function test_draft_applications_are_ignored(): void
    {
        Permohonan::where('no_rujukan', 'like', 'PM-POC-K2-00%')
            ->where('no_rujukan', '!=', 'PM-POC-K2-001')
            ->update(['status' => 'Draf']);

        $result = $this->service()->analyze($this->permohonan('PM-POC-K2-001'));

        $this->assertSame([], $result['isyarat']);
        $this->assertFalse($result['amaran']);
    }

    public function test_repeat_application_by_same_person(): void
    {
        $original = $this->permohonan('PM-POC-K1-001');
        Permohonan::create([
            'no_rujukan' => 'PM-POC-K1-002',
            'nama' => 'ROSNAH BT ABDUL LATIF',
            'status' => 'Dalam Semakan',
            'jumlah_permohonan' => 8000,
            'details' => ['nama' => 'ROSNAH BT ABDUL LATIF', 'no_ic_baru' => '790605-10-6628'],
        ]);

        $signals = $this->signalsByCode($this->service()->analyze($original));

        $this->assertSame('sederhana', $signals['PERMOHONAN_BERULANG']['tahap']);
        $this->assertArrayNotHasKey('IC_GUNA_SEMULA', $signals);
    }

    public function test_disbursement_check_blocks_k2_and_allows_k1(): void
    {
        $blocked = $this->service()->disbursementCheck($this->permohonan('PM-POC-K2-001'));
        $this->assertFalse($blocked['boleh_salur']);
        $this->assertTrue($blocked['amaran']);
        $this->assertSame('tinggi', $blocked['tahap']);
        $this->assertContains('Corak sindiket / rangkaian dikesan', $blocked['sebab']);
        $this->assertArrayHasKey('skor_risiko', $blocked['analisis']);
        $this->assertNotEmpty($blocked['disemak_pada']);

        $allowed = $this->service()->disbursementCheck($this->permohonan('PM-POC-K1-001'));
        $this->assertTrue($allowed['boleh_salur']);
        $this->assertFalse($allowed['amaran']);
        $this->assertSame([], $allowed['sebab']);
    }

    public function test_sales_document_not_configured_returns_fallback(): void
    {
        config(['services.anthropic.key' => '']);
        Http::fake();

        $result = $this->service()->analyzeSalesDocument($this->fakeImage(), 'image/png');

        $this->assertFalse($result['ok']);
        $this->assertSame('meragukan', $result['keputusan']);
        $this->assertNotSame('', $result['message']);
        Http::assertNothingSent();
    }

    public function test_sales_document_configured_computes_verdict_in_php(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode([
                    'skor_keaslian' => 30,
                    'tanda_palsu' => ['Fon jumlah besar berbeza daripada baris lain', 'ID transaksi TX-1001 berulang'],
                    'semakan_aritmetik' => ['lulus' => false, 'catatan' => 'Jumlah item RM1,240 tetapi jumlah besar RM2,140.'],
                    'ringkasan' => 'Dokumen menunjukkan tanda suntingan.',
                    'keputusan' => 'asli',
                ])]],
                'model' => 'claude-haiku-4-5',
                'stop_reason' => 'end_turn',
            ]),
        ]);

        $result = $this->service()->analyzeSalesDocument($this->fakeImage(), 'image/png');

        $this->assertTrue($result['ok']);
        $this->assertSame(30, $result['skor_keaslian']);
        $this->assertSame('palsu', $result['keputusan']);
        $this->assertCount(2, $result['tanda_palsu']);
        $this->assertFalse($result['semakan_aritmetik']['lulus']);
        $this->assertSame('Dokumen menunjukkan tanda suntingan.', $result['ringkasan']);
        Http::assertSentCount(1);
    }

    public function test_sales_document_api_failure_never_throws(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'overloaded']], 529)]);

        $result = $this->service()->analyzeSalesDocument($this->fakeImage(), 'image/png');

        $this->assertFalse($result['ok']);
        $this->assertSame('meragukan', $result['keputusan']);
        $this->assertStringContainsString('529', $result['message']);
    }

    private function fakeImage(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'resit').'.png';
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));

        return $path;
    }
}
