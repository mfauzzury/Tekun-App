<?php

namespace Tests\Unit;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use App\Services\AinaChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AinaChatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_message_uses_anthropic_messages_api(): void
    {
        config([
            'services.anthropic.key' => 'test-key',
            'services.anthropic.model' => 'claude-haiku-4-5',
            'services.sppt.system_url' => 'http://localhost/admin',
        ]);

        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Untuk daftar permohonan, pergi ke menu Permohonan.'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $session = ChatSession::create([
            'openai_thread_id' => 'claude-test',
            'title' => 'New Chat',
            'user_id' => $user->id,
            'session_type' => 'solo',
            'chat_type' => 'user',
        ]);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'user',
            'content' => 'Macam mana nak daftar permohonan?',
        ]);

        $service = app(AinaChatService::class);
        $result = $service->sendMessage($session->fresh(), 'Macam mana nak daftar permohonan?');

        $this->assertSame('Untuk daftar permohonan, pergi ke menu Permohonan.', $result['content']);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && ($body['model'] ?? '') === 'claude-haiku-4-5'
                && str_contains((string) ($body['system'] ?? ''), 'AINA')
                && ($body['messages'][0]['role'] ?? '') === 'user';
        });
    }
}
