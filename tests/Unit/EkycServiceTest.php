<?php

namespace Tests\Unit;

use App\Services\EkycService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EkycServiceTest extends TestCase
{
    protected const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected string $mykadPath;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.anthropic.key' => 'test-key',
            'services.anthropic.model' => 'claude-haiku-4-5',
        ]);
        $this->mykadPath = sys_get_temp_dir().'/ekyc-test-'.uniqid().'.png';
        file_put_contents($this->mykadPath, base64_decode(self::PNG_1X1));
    }

    protected function tearDown(): void
    {
        @unlink($this->mykadPath);
        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function fakeClaude(array $payload): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode($payload, JSON_THROW_ON_ERROR)]],
                'model' => 'claude-haiku-4-5',
                'stop_reason' => 'end_turn',
            ], 200),
        ]);
    }

    /**
     * @return list<array{base64: string, mime: string, arahan: string}>
     */
    protected function frames(): array
    {
        return [
            ['base64' => self::PNG_1X1, 'mime' => 'image/png', 'arahan' => 'Pandang terus ke kamera'],
            ['base64' => 'data:image/png;base64,'.self::PNG_1X1, 'mime' => 'image/png', 'arahan' => 'Pusing kepala ke kiri'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function facePayload(array $overrides = []): array
    {
        return $overrides + [
            'padanan_wajah' => 92,
            'liveness' => 88,
            'tanda_deepfake' => [],
            'cabaran' => [
                ['arahan' => 'Pandang terus ke kamera', 'lulus' => true, 'catatan' => 'Wajah jelas'],
                ['arahan' => 'Pusing kepala ke kiri', 'lulus' => true, 'catatan' => 'Kepala dipusing'],
            ],
            'ringkasan' => 'Wajah sepadan dan pemohon hadir secara langsung.',
            'keputusan' => 'gagal',
        ];
    }

    public function test_validate_ic_parses_valid_number(): void
    {
        $this->travelTo('2026-10-13');
        $result = app(EkycService::class)->validateIc('850101105371');

        $this->assertTrue($result['valid']);
        $this->assertSame('850101-10-5371', $result['no_ic']);
        $this->assertSame('1985-01-01', $result['tarikh_lahir']);
        $this->assertSame(41, $result['umur']);
        $this->assertSame('L', $result['jantina']);
        $this->assertSame('10', $result['kod_negeri']);
        $this->assertSame('Selangor', $result['negeri_lahir']);
        $this->assertSame([], $result['isu']);
    }

    public function test_validate_ic_female_and_foreign_born_code(): void
    {
        $result = app(EkycService::class)->validateIc('900215-71-5372');

        $this->assertTrue($result['valid']);
        $this->assertSame('P', $result['jantina']);
        $this->assertSame('Lahir di luar negara', $result['negeri_lahir']);
    }

    public function test_validate_ic_rejects_invalid_date(): void
    {
        $result = app(EkycService::class)->validateIc('850231-10-5371');

        $this->assertFalse($result['valid']);
        $this->assertNull($result['tarikh_lahir']);
        $this->assertNotEmpty($result['isu']);
    }

    public function test_validate_ic_rejects_invalid_pb_code(): void
    {
        $result = app(EkycService::class)->validateIc('850101-99-5371');

        $this->assertFalse($result['valid']);
        $this->assertNull($result['negeri_lahir']);
        $this->assertStringContainsString('PB', implode(' ', $result['isu']));
    }

    public function test_validate_ic_rejects_wrong_length(): void
    {
        $this->assertFalse(app(EkycService::class)->validateIc('85010110537')['valid']);
    }

    public function test_validate_ic_flags_synthetic_serial_without_invalidating(): void
    {
        $service = app(EkycService::class);

        foreach (['850101-10-1234', '850101-10-0000', '850101-10-4321'] as $ic) {
            $result = $service->validateIc($ic);
            $this->assertTrue($result['valid'], $ic);
            $this->assertContains('Corak nombor siri luar biasa — kemungkinan identiti sintetik.', $result['isu'], $ic);
        }
    }

    public function test_validate_ic_flags_underage(): void
    {
        $this->travelTo('2026-10-13');
        $result = app(EkycService::class)->validateIc('150101-10-5371');

        $this->assertTrue($result['valid']);
        $this->assertSame(11, $result['umur']);
        $this->assertNotEmpty($result['isu']);
    }

    public function test_compare_matches_name_variants(): void
    {
        $result = app(EkycService::class)->compareWithApplication(
            ['no_ic' => '850101-10-5371', 'nama' => 'AHMAD B. ALI', 'alamat' => 'NO 12, JLN MAWAR 3, TMN MELATI, 43000 KAJANG, SELANGOR'],
            ['no_ic_baru' => '850101105371', 'nama' => 'Ahmad bin Ali', 'alamat' => 'No. 12 Jalan Mawar 3, Taman Melati, 43000 Kajang, Selangor'],
        );

        $rows = collect($result['padanan'])->keyBy('medan');
        $this->assertTrue($rows['no_ic']['padan']);
        $this->assertSame(100, $rows['no_ic']['skor']);
        $this->assertTrue($rows['nama']['padan']);
        $this->assertTrue($rows['alamat']['padan']);
        $this->assertSame('No. MyKad', $rows['no_ic']['label']);
        $this->assertGreaterThanOrEqual(90, $result['skor_keseluruhan']);
    }

    public function test_compare_detects_mismatch_and_renormalises_missing_fields(): void
    {
        $result = app(EkycService::class)->compareWithApplication(
            ['no_ic' => '850101-10-5371', 'nama' => 'SITI AMINAH BINTI YUSOF', 'alamat' => null],
            ['no_ic_baru' => '850101-10-5371', 'nama' => 'MUHAMMAD FAIZ BIN RAHMAN'],
        );

        $rows = collect($result['padanan'])->keyBy('medan');
        $this->assertFalse($rows['nama']['padan']);
        $this->assertFalse($rows['alamat']['padan']);
        $this->assertSame(0, $rows['alamat']['skor']);
        $expected = (int) round((100 * 50 + $rows['nama']['skor'] * 35) / 85);
        $this->assertSame($expected, $result['skor_keseluruhan']);
    }

    public function test_ocr_mykad_parses_model_json(): void
    {
        $this->fakeClaude([
            'adalah_mykad' => true,
            'data' => [
                'no_ic' => '850101105371',
                'nama' => 'AHMAD BIN ALI',
                'alamat' => 'NO 12 JALAN MAWAR 3 TAMAN MELATI 43000 KAJANG SELANGOR',
                'warganegara' => 'WARGANEGARA',
                'agama' => 'ISLAM',
                'jantina' => 'LELAKI',
            ],
            'kualiti' => ['skor' => 140, 'isu' => ['Sedikit silau']],
            'tanda_palsu' => ['Fon tidak konsisten', ''],
        ]);

        $result = app(EkycService::class)->ocrMyKad($this->mykadPath, 'image/png');

        $this->assertTrue($result['ok']);
        $this->assertSame('ai', $result['sumber']);
        $this->assertSame('850101-10-5371', $result['data']['no_ic']);
        $this->assertSame('AHMAD BIN ALI', $result['data']['nama']);
        $this->assertSame(100, $result['kualiti']['skor']);
        $this->assertSame(['Sedikit silau'], $result['kualiti']['isu']);
        $this->assertSame(['Fon tidak konsisten'], $result['tanda_palsu']);
        $this->assertTrue($result['adalah_mykad']);
        Http::assertSentCount(1);
    }

    public function test_ocr_mykad_not_configured_sends_nothing(): void
    {
        config(['services.anthropic.key' => '']);
        Http::fake();

        $result = app(EkycService::class)->ocrMyKad($this->mykadPath, 'image/png');

        $this->assertFalse($result['ok']);
        $this->assertSame('tiada', $result['sumber']);
        $this->assertSame('API key AI belum dikonfigurasi', $result['message']);
        Http::assertNothingSent();
    }

    public function test_ocr_mykad_degrades_on_api_error(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'overloaded']], 529)]);

        $result = app(EkycService::class)->ocrMyKad($this->mykadPath, 'image/png');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('overloaded', $result['message']);
    }

    public function test_face_and_liveness_passes_when_thresholds_met(): void
    {
        $this->fakeClaude($this->facePayload());

        $result = app(EkycService::class)->faceAndLiveness($this->mykadPath, 'image/png', $this->frames());

        $this->assertTrue($result['ok']);
        $this->assertSame('lulus', $result['keputusan']);
        $this->assertSame(92, $result['padanan_wajah']);
        $this->assertCount(2, $result['cabaran']);
        $this->assertSame('Pusing kepala ke kiri', $result['cabaran'][1]['arahan']);
        Http::assertSent(fn ($request) => count($request['messages'][0]['content']) === 4);
    }

    public function test_face_and_liveness_semak_when_challenge_failed_or_deepfake(): void
    {
        $service = app(EkycService::class);

        $payload = $this->facePayload();
        $payload['cabaran'][1]['lulus'] = false;
        $payload['keputusan'] = 'lulus';
        $this->fakeClaude($payload);
        $this->assertSame('semak', $service->faceAndLiveness($this->mykadPath, 'image/png', $this->frames())['keputusan']);

        $this->fakeClaude($this->facePayload(['tanda_deepfake' => ['Corak moire skrin']]));
        $this->assertSame('semak', $service->faceAndLiveness($this->mykadPath, 'image/png', $this->frames())['keputusan']);
    }

    public function test_face_and_liveness_gagal_on_low_scores(): void
    {
        $service = app(EkycService::class);

        $this->fakeClaude($this->facePayload(['padanan_wajah' => 30, 'keputusan' => 'lulus']));
        $this->assertSame('gagal', $service->faceAndLiveness($this->mykadPath, 'image/png', $this->frames())['keputusan']);

        $this->fakeClaude($this->facePayload(['liveness' => 25, 'keputusan' => 'lulus']));
        $this->assertSame('gagal', $service->faceAndLiveness($this->mykadPath, 'image/png', $this->frames())['keputusan']);
    }

    public function test_face_and_liveness_without_mykad_is_at_most_semak(): void
    {
        $this->fakeClaude($this->facePayload());

        $result = app(EkycService::class)->faceAndLiveness(null, null, $this->frames());

        $this->assertTrue($result['ok']);
        $this->assertSame(0, $result['padanan_wajah']);
        $this->assertSame('semak', $result['keputusan']);
        $this->assertStringContainsString('MyKad', $result['message']);
    }

    public function test_face_and_liveness_without_frames(): void
    {
        Http::fake();

        $result = app(EkycService::class)->faceAndLiveness($this->mykadPath, 'image/png', []);

        $this->assertFalse($result['ok']);
        $this->assertSame('semak', $result['keputusan']);
        $this->assertSame('Tiada imej selfie', $result['message']);
        Http::assertNothingSent();
    }

    public function test_face_and_liveness_not_configured_sends_nothing(): void
    {
        config(['services.anthropic.key' => '']);
        Http::fake();

        $result = app(EkycService::class)->faceAndLiveness($this->mykadPath, 'image/png', $this->frames());

        $this->assertFalse($result['ok']);
        $this->assertSame('API key AI belum dikonfigurasi', $result['message']);
        Http::assertNothingSent();
    }
}
