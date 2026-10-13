<?php

namespace Tests\Feature;

use App\Models\Permohonan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PemohonPortalAiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'tok-portal-0123456789abcdef0123456789';

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function permohonan(array $details = [], string $status = 'Draf'): Permohonan
    {
        return Permohonan::create([
            'no_rujukan' => 'PM-PORTAL-'.uniqid(),
            'nama' => 'Siti Aminah Binti Ali',
            'status' => $status,
            'jumlah_permohonan' => 5000,
            'pemohon_access_token' => self::TOKEN,
            'details' => array_merge([
                'nama' => 'Siti Aminah Binti Ali',
                'no_ic_baru' => '900215-10-5372',
                'alamat' => 'No 3 Jalan Kenanga 2 Taman Kenanga',
            ], $details),
        ]);
    }

    private function claudeReply(array $payload): array
    {
        return ['content' => [['type' => 'text', 'text' => json_encode($payload)]], 'model' => 'claude-haiku-4-5', 'stop_reason' => 'end_turn'];
    }

    private function withMyKad(Permohonan $permohonan): void
    {
        Storage::fake('public');
        Storage::disk('public')->put("permohonan/{$permohonan->id}/mykad.png", base64_decode(self::PNG));
        $details = $permohonan->details;
        $details['attachments'] = [[
            'id' => 'att-1',
            'name' => 'mykad.png',
            'url' => "/storage/permohonan/{$permohonan->id}/mykad.png",
            'mime_type' => 'image/png',
            'document_class' => 'ic_pemohon_depan',
        ]];
        $permohonan->update(['details' => $details]);
    }

    public function test_wrong_token_is_not_found_on_every_endpoint(): void
    {
        $p = $this->permohonan();
        $base = "/api/public/pemohon/permohonan/{$p->id}";

        $this->getJson("$base/penilaian-pantas?accessToken=wrong")->assertStatus(404);
        $this->postJson("$base/ekyc/mykad", ['accessToken' => 'wrong'])->assertStatus(404);
        $this->postJson("$base/ekyc/wajah", ['accessToken' => 'wrong', 'frames' => [['base64' => self::PNG, 'mime' => 'image/png', 'arahan' => 'Pandang']]])->assertStatus(404);
        $this->putJson("$base/data-alternatif", ['accessToken' => 'wrong'])->assertStatus(404);
    }

    public function test_validation_errors(): void
    {
        $p = $this->permohonan();
        $base = "/api/public/pemohon/permohonan/{$p->id}";

        $this->postJson("$base/ekyc/wajah", ['accessToken' => self::TOKEN, 'frames' => []])
            ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->putJson("$base/data-alternatif", ['accessToken' => self::TOKEN, 'platform' => ['penarafan' => 9]])
            ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->postJson("$base/data-alternatif/ekstrak", ['accessToken' => self::TOKEN])
            ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->postJson("$base/ekyc/mykad", ['accessToken' => self::TOKEN])
            ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_scan_mykad_returns_ocr_fields_and_stores_result(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fake(['api.anthropic.com/*' => Http::response($this->claudeReply([
            'adalah_mykad' => true,
            'data' => ['no_ic' => '900215105372', 'nama' => 'SITI AMINAH BINTI ALI', 'alamat' => 'NO 3 JALAN KENANGA 2 TAMAN KENANGA'],
            'kualiti' => ['skor' => 90, 'isu' => []],
            'tanda_palsu' => [],
        ]))]);
        $p = $this->permohonan();
        $this->withMyKad($p);

        $this->postJson("/api/public/pemohon/permohonan/{$p->id}/ekyc/mykad", ['accessToken' => self::TOKEN])
            ->assertOk()
            ->assertJsonPath('data.ocr.ok', true)
            ->assertJsonPath('data.ocr.data.noIc', '900215-10-5372')
            ->assertJsonPath('data.ic.valid', true)
            ->assertJsonPath('data.padanan.skorKeseluruhan', 100);

        $this->assertTrue($p->fresh()->details['ekyc_mykad']['ocr']['ok']);
    }

    public function test_face_check_is_stored_for_the_pipeline(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fake(['api.anthropic.com/*' => Http::response($this->claudeReply([
            'padanan_wajah' => 92,
            'liveness' => 90,
            'tanda_deepfake' => [],
            'cabaran' => [['lulus' => true, 'catatan' => 'OK']],
            'ringkasan' => 'Wajah sepadan.',
        ]))]);
        $p = $this->permohonan();
        $this->withMyKad($p);

        $this->postJson("/api/public/pemohon/permohonan/{$p->id}/ekyc/wajah", [
            'accessToken' => self::TOKEN,
            'frames' => [['base64' => self::PNG, 'mime' => 'image/png', 'arahan' => 'Pandang terus ke kamera']],
        ])->assertOk()->assertJsonPath('data.keputusan', 'lulus');

        $this->assertSame('lulus', $p->fresh()->details['ekyc_wajah']['keputusan']);
    }

    public function test_data_alternatif_saved_and_not_overwritten_by_form_save(): void
    {
        $p = $this->permohonan();
        $base = "/api/public/pemohon/permohonan/{$p->id}";

        $this->putJson("$base/data-alternatif", [
            'accessToken' => self::TOKEN,
            'ewallet' => ['penyedia' => 'GrabPay', 'purataMasukBulanan' => 2500, 'bulanAktif' => 9],
            'ccrisTersedia' => false,
        ])->assertOk()->assertJsonPath('data.ewallet.purataMasukBulanan', 2500)->assertJsonMissingPath('data.accessToken');

        $this->putJson($base, [
            'accessToken' => self::TOKEN,
            'status' => 'Draf',
            'details' => ['nama' => 'Siti Aminah Binti Ali', 'dataAlternatif' => ['ewallet' => ['purataMasukBulanan' => 1]]],
        ])->assertOk();

        $this->assertSame(2500, $p->fresh()->details['data_alternatif']['ewallet']['purata_masuk_bulanan']);
    }

    public function test_statement_extraction_degrades_without_ai_key(): void
    {
        config(['services.anthropic.key' => null]);
        Http::fake();
        $p = $this->permohonan();

        $this->post("/api/public/pemohon/permohonan/{$p->id}/data-alternatif/ekstrak", [
            'accessToken' => self::TOKEN,
            'jenis' => 'ewallet',
            'file' => UploadedFile::fake()->createWithContent('penyata.csv', "tarikh,jumlah\n2026-09-01,120.50\n"),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.ok', false);

        Http::assertNothingSent();
    }

    public function test_progress_is_applicant_safe(): void
    {
        config(['services.anthropic.key' => null]);
        Http::fake();
        $p = $this->permohonan(['dihantar_pada' => now()->toIso8601String()], 'Dalam Semakan');
        $base = "/api/public/pemohon/permohonan/{$p->id}/penilaian-pantas";

        $this->getJson("$base?accessToken=".self::TOKEN)->assertOk()->assertJsonPath('data.status', 'tiada');

        $this->postJson($base, ['accessToken' => self::TOKEN])->assertOk()->assertJsonPath('data.status', 'selesai');

        $response = $this->getJson("$base?accessToken=".self::TOKEN)
            ->assertOk()
            ->assertJsonPath('data.status', 'selesai')
            ->assertJsonCount(5, 'data.langkah')
            ->assertJsonStructure(['data' => ['langkah' => [['kod', 'label', 'status', 'ms']], 'jumlahMs', 'statusPermohonan', 'mesej']]);

        $body = $response->getContent();
        foreach (['skor', 'skorKredit', 'laluan', 'isyarat', 'cadangan', 'tahapRisiko', 'hasil'] as $forbidden) {
            $this->assertStringNotContainsString('"'.$forbidden.'":', $body, "leaked key {$forbidden}");
        }
    }
}
