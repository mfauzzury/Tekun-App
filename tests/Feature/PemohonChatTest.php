<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PemohonChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.anthropic.key' => 'test-key']);
    }

    public function test_chat_returns_reply(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Dokumen asas: Kad Pengenalan, Lesen Perniagaan.']],
            ], 200),
        ]);

        $response = $this->postJson('/api/public/pemohon/chat', [
            'message' => 'Apakah dokumen yang diperlukan?',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.reply', 'Dokumen asas: Kad Pengenalan, Lesen Perniagaan.');
    }

    public function test_chat_validation_error_without_message(): void
    {
        $response = $this->postJson('/api/public/pemohon/chat', []);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_chat_returns_error_when_api_key_missing(): void
    {
        config(['services.anthropic.key' => '']);

        $response = $this->postJson('/api/public/pemohon/chat', [
            'message' => 'Hai',
        ]);

        $response->assertStatus(502);
        $response->assertJsonPath('error.code', 'CHAT_FAILED');
    }
}
