<?php

namespace Tests\Unit;

use App\Services\PermohonanFormOcrService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PermohonanFormOcrServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.anthropic.key' => 'test-key',
            'services.anthropic.model' => 'claude-haiku-4-5',
        ]);
    }

    public function test_extract_from_image_parses_model_json(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                            'confidence' => 88,
                            'field_confidence' => [
                                'nama' => 95,
                                'no_ic_baru' => 92,
                            ],
                            'fields' => [
                                'nama' => 'AHMAD BIN ALI',
                                'no_ic_baru' => '800101015432',
                                'jumlah_permohonan' => 'RM 25,000.00',
                                'oku' => 'TIDAK',
                            ],
                        ], JSON_THROW_ON_ERROR),
                ]],
            ], 200),
        ]);

        $file = UploadedFile::fake()->image('borang.jpg');
        $service = app(PermohonanFormOcrService::class);
        $result = $service->extractFromUpload($file);

        $this->assertSame('AHMAD BIN ALI', $result['fields']['nama']);
        $this->assertSame('800101-01-5432', $result['fields']['no_ic_baru']);
        $this->assertSame('25000', $result['fields']['jumlah_permohonan']);
        $this->assertFalse($result['fields']['oku']);
        $this->assertSame(88, $result['confidence']);
        $this->assertSame(4, $result['populated_count']);
    }

    public function test_extract_parses_markdown_wrapped_model_json(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => "```json\n".json_encode([
                        'confidence' => 75,
                        'field_confidence' => ['nama' => 80],
                        'fields' => ['nama' => 'SITI AMINAH'],
                    ], JSON_THROW_ON_ERROR)."\n```",
                ]],
            ], 200),
        ]);

        $file = UploadedFile::fake()->image('borang.jpg');
        $result = app(PermohonanFormOcrService::class)->extractFromUpload($file);

        $this->assertSame('SITI AMINAH', $result['fields']['nama']);
        $this->assertSame(75, $result['confidence']);
    }

    public function test_extract_normalizes_bangsa_taraf_and_status_kediaman(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'confidence' => 80,
                        'field_confidence' => [],
                        'fields' => [
                            'bangsa' => 'CINA',
                            'taraf_perkahwinan' => 'BUJANG',
                            'status_kediaman' => 'SENDIRI',
                        ],
                    ], JSON_THROW_ON_ERROR),
                ]],
            ], 200),
        ]);

        $file = UploadedFile::fake()->image('borang.jpg');
        $result = app(PermohonanFormOcrService::class)->extractFromUpload($file);

        $this->assertSame('Cina', $result['fields']['bangsa']);
        $this->assertSame('Bujang', $result['fields']['taraf_perkahwinan']);
        $this->assertSame('Sendiri', $result['fields']['status_kediaman']);
    }

    public function test_extract_throws_when_api_key_missing(): void
    {
        config(['services.anthropic.key' => '']);

        $file = UploadedFile::fake()->image('borang.jpg');
        $service = app(PermohonanFormOcrService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ANTHROPIC_API_KEY');

        $service->extractFromUpload($file);
    }
}
