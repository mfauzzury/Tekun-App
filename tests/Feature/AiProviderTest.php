<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'sk-ant-test-0123456789abcdefWXYZ';

    private const OPENAI_KEY = 'sk-openai-test-0123456789abcdQRST';

    private function actingAsUser(array $permissions = ['settings.view', 'settings.edit']): User
    {
        $role = Role::create(['name' => 'admin', 'description' => 'Admin', 'permissions' => $permissions]);
        $user = User::factory()->create(['role' => 'admin', 'role_id' => $role->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function anthropicPayload(array $overrides = []): array
    {
        return array_merge([
            'apiKey' => self::KEY,
            'model' => 'claude-opus-5-5',
            'chatModel' => 'claude-haiku-5-5',
            'timeout' => 120,
        ], $overrides);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/platform/ai/providers')->assertStatus(401);
        $this->putJson('/api/platform/ai/providers/anthropic', $this->anthropicPayload())->assertStatus(401);
        $this->postJson('/api/platform/ai/providers/anthropic/test')->assertStatus(401);
        $this->putJson('/api/platform/ai/providers/aktif', ['provider' => 'openai'])->assertStatus(401);
    }

    public function test_requires_settings_permissions(): void
    {
        $this->actingAsUser(['posts.view']);

        $this->getJson('/api/platform/ai/providers')->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
        $this->putJson('/api/platform/ai/providers/anthropic', $this->anthropicPayload())->assertStatus(403);
    }

    public function test_view_only_user_cannot_update_activate_or_test(): void
    {
        $this->actingAsUser(['settings.view']);

        $this->getJson('/api/platform/ai/providers')->assertOk();
        $this->putJson('/api/platform/ai/providers/anthropic', $this->anthropicPayload())->assertStatus(403);
        $this->putJson('/api/platform/ai/providers/aktif', ['provider' => 'openai'])->assertStatus(403);
        $this->postJson('/api/platform/ai/providers/openai/test')->assertStatus(403);
    }

    public function test_unknown_provider_is_not_found(): void
    {
        $this->actingAsUser();

        $this->putJson('/api/platform/ai/providers/gemini', ['model' => 'x'])->assertStatus(404);
    }

    public function test_validation_errors(): void
    {
        $this->actingAsUser();

        $this->putJson('/api/platform/ai/providers/anthropic', $this->anthropicPayload(['model' => 'gpt-4o', 'timeout' => 5, 'apiKey' => 'short']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['details' => ['model', 'timeout', 'apiKey']]]);

        $this->putJson('/api/platform/ai/providers/onprem', ['model' => 'llama 3; rm', 'baseUrl' => 'ftp://x'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['model', 'baseUrl']]]);

        $this->putJson('/api/platform/ai/providers/aktif', ['provider' => 'gemini'])->assertStatus(422);
    }

    public function test_show_lists_all_providers_without_keys(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/platform/ai/providers')
            ->assertOk()
            ->assertJsonPath('data.aktif', 'anthropic')
            ->assertJsonStructure(['data' => ['aktif', 'timeout', 'penyedia' => [
                'anthropic' => ['label', 'keyConfigured', 'keySource', 'keyHint', 'model', 'chatModel', 'vision', 'models', 'dikonfigurasi', 'aktif'],
                'openai' => ['label', 'model', 'baseUrl', 'cadanganModel', 'dikonfigurasi'],
                'deepseek' => ['label', 'vision'],
                'onprem' => ['label', 'onPremise', 'baseUrl', 'keyWajib'],
            ]]])
            ->assertJsonMissingPath('data.penyedia.anthropic.apiKey');
    }

    public function test_anthropic_update_stores_key_encrypted_and_masks_it(): void
    {
        $this->actingAsUser();

        $response = $this->putJson('/api/platform/ai/providers/anthropic', $this->anthropicPayload())
            ->assertOk()
            ->assertJsonPath('data.penyedia.anthropic.keySource', 'database')
            ->assertJsonPath('data.penyedia.anthropic.keyHint', '••••WXYZ')
            ->assertJsonPath('data.penyedia.anthropic.model', 'claude-opus-5-5')
            ->assertJsonPath('data.timeout', 120);

        $this->assertStringNotContainsString(self::KEY, $response->getContent());
        $stored = DB::table('settings')->where('key', AiProviderService::KEY_API_KEY)->value('value');
        $this->assertSame(self::KEY, Crypt::decryptString($stored));
        $this->assertSame(self::KEY, config('services.anthropic.key'));

        $audit = DB::table('audit_logs')->where('action', 'ai_provider.updated')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString(self::KEY, (string) $audit->new_values);
    }

    public function test_clear_key_falls_back_to_env(): void
    {
        config(['services.anthropic.env_key' => null]);
        $this->actingAsUser();
        $this->putJson('/api/platform/ai/providers/anthropic', $this->anthropicPayload())->assertOk();

        $this->putJson('/api/platform/ai/providers/anthropic', $this->anthropicPayload(['apiKey' => null, 'clearApiKey' => true]))
            ->assertOk()
            ->assertJsonPath('data.penyedia.anthropic.keyConfigured', false)
            ->assertJsonPath('data.penyedia.anthropic.keySource', 'none');
    }

    public function test_openai_configure_and_activate(): void
    {
        config(['services.ai.openai.key' => null, 'services.ai.openai.env_key' => null]);
        $this->actingAsUser();

        // Not configured yet → cannot activate.
        $this->putJson('/api/platform/ai/providers/aktif', ['provider' => 'openai'])->assertStatus(422);

        $this->putJson('/api/platform/ai/providers/openai', ['apiKey' => self::OPENAI_KEY, 'model' => 'gpt-4o-mini'])
            ->assertOk()
            ->assertJsonPath('data.penyedia.openai.keyHint', '••••QRST')
            ->assertJsonPath('data.penyedia.openai.dikonfigurasi', true);

        $this->putJson('/api/platform/ai/providers/aktif', ['provider' => 'openai'])
            ->assertOk()
            ->assertJsonPath('data.aktif', 'openai')
            ->assertJsonPath('data.penyedia.openai.aktif', true);

        $this->assertSame('openai', config('services.ai.active'));
        $this->assertSame(self::OPENAI_KEY, Crypt::decryptString(
            DB::table('settings')->where('key', AiProviderService::settingKey('openai', 'Key'))->value('value')
        ));
    }

    public function test_onprem_needs_no_key_and_stores_endpoint(): void
    {
        $this->actingAsUser();

        $this->putJson('/api/platform/ai/providers/onprem', [
            'baseUrl' => 'http://10.0.0.5:11434/v1',
            'model' => 'qwen2.5vl:7b',
            'vision' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.penyedia.onprem.baseUrl', 'http://10.0.0.5:11434/v1')
            ->assertJsonPath('data.penyedia.onprem.vision', true)
            ->assertJsonPath('data.penyedia.onprem.dikonfigurasi', true);

        $this->putJson('/api/platform/ai/providers/aktif', ['provider' => 'onprem'])->assertOk()->assertJsonPath('data.aktif', 'onprem');
    }

    public function test_generic_settings_endpoint_cannot_overwrite_ai_keys(): void
    {
        $this->actingAsUser();
        $this->putJson('/api/platform/ai/providers/anthropic', $this->anthropicPayload())->assertOk();

        $this->putJson('/api/settings', [
            'aiAnthropicApiKey' => 'hijack',
            'aiProviderActive' => 'onprem',
            'aiProviderOnpremBaseUrl' => 'http://evil.example',
        ])->assertOk();

        $this->assertSame(self::KEY, Crypt::decryptString(DB::table('settings')->where('key', AiProviderService::KEY_API_KEY)->value('value')));
        $this->assertNull(DB::table('settings')->where('key', AiProviderService::KEY_ACTIVE)->value('value'));
        $this->assertNull(DB::table('settings')->where('key', 'aiProviderOnpremBaseUrl')->value('value'));
    }

    public function test_anthropic_connection_success(): void
    {
        Http::fake(['api.anthropic.com/v1/models/*' => Http::response(['id' => 'claude-opus-5-5', 'display_name' => 'Claude Opus 5.5'])]);
        $this->actingAsUser();

        $this->postJson('/api/platform/ai/providers/anthropic/test', ['apiKey' => self::KEY, 'model' => 'claude-opus-5-5'])
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.displayName', 'Claude Opus 5.5');

        Http::assertSent(fn ($r) => $r->hasHeader('x-api-key', self::KEY) && str_ends_with($r->url(), '/v1/models/claude-opus-5-5'));
    }

    public function test_compatible_connection_lists_models(): void
    {
        Http::fake(['10.0.0.5:11434/v1/models' => Http::response(['data' => [['id' => 'qwen2.5vl:7b'], ['id' => 'llama3.1:8b']]])]);
        $this->actingAsUser();

        $this->postJson('/api/platform/ai/providers/onprem/test', ['baseUrl' => 'http://10.0.0.5:11434/v1', 'model' => 'llama3.1:8b'])
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.modelTersedia', ['qwen2.5vl:7b', 'llama3.1:8b']);

        $this->postJson('/api/platform/ai/providers/onprem/test', ['baseUrl' => 'http://10.0.0.5:11434/v1', 'model' => 'tiada:1b'])
            ->assertOk()
            ->assertJsonPath('data.ok', false);
    }

    public function test_compatible_connection_reports_invalid_key(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'Incorrect API key']], 401)]);
        $this->actingAsUser();

        $this->postJson('/api/platform/ai/providers/openai/test', ['apiKey' => self::OPENAI_KEY, 'model' => 'gpt-4o'])
            ->assertOk()
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.status', 401);
    }

    public function test_ai_client_routes_to_active_openai_compatible_provider(): void
    {
        config([
            'services.ai.active' => 'openai',
            'services.ai.openai.key' => self::OPENAI_KEY,
            'services.ai.openai.model' => 'gpt-4o-mini',
        ]);
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => '{"skor": 88}']]],
        ])]);

        $result = app(AiClient::class)->json('Sistem', 'Nilai', [['base64' => 'aGVsbG8=', 'mime' => 'image/png']]);

        $this->assertSame(['skor' => 88], $result['data']);
        Http::assertSent(function ($r) {
            $body = $r->data();

            return $r->hasHeader('Authorization', 'Bearer '.self::OPENAI_KEY)
                && $body['model'] === 'gpt-4o-mini'
                && isset($body['max_completion_tokens'])
                && $body['messages'][1]['content'][0]['image_url']['url'] === 'data:image/png;base64,aGVsbG8=';
        });
    }

    public function test_ai_client_refuses_images_on_text_only_provider_without_image_provider(): void
    {
        config(['services.ai.active' => 'deepseek', 'services.ai.active_vision' => null, 'services.ai.deepseek.key' => 'sk-deepseek-12345678', 'services.ai.deepseek.model' => 'deepseek-chat']);
        Http::fake();

        $this->expectExceptionMessage('tidak menyokong analisis imej');
        try {
            app(AiClient::class)->json('Sistem', 'Baca MyKad', [['base64' => 'aGVsbG8=', 'mime' => 'image/png']]);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_text_and_image_tasks_use_separate_providers_and_models(): void
    {
        config([
            'services.ai.active' => 'onprem',
            'services.ai.active_vision' => 'anthropic',
            'services.ai.onprem.base_url' => 'http://localhost:11434/v1',
            'services.ai.onprem.model' => 'llama3:latest',
            'services.anthropic.key' => self::KEY,
            'services.anthropic.model' => 'claude-haiku-4-5',
            'services.anthropic.vision_model' => 'claude-sonnet-5-5',
        ]);
        Http::fake([
            'localhost:11434/v1/chat/completions' => Http::response(['model' => 'llama3:latest', 'choices' => [['message' => ['content' => '{"jenis":"teks"}']]]]),
            'api.anthropic.com/v1/messages' => Http::response(['model' => 'claude-sonnet-5-5', 'content' => [['type' => 'text', 'text' => '{"jenis":"imej"}']], 'stop_reason' => 'end_turn']),
        ]);

        $client = app(AiClient::class);
        $this->assertSame(['jenis' => 'teks'], $client->json('S', 'teks')['data']);
        $this->assertSame(['jenis' => 'imej'], $client->json('S', 'imej', [['base64' => 'aGVsbG8=', 'mime' => 'image/png']])['data']);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'localhost:11434') && $r->data()['model'] === 'llama3:latest');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.anthropic.com') && $r->data()['model'] === 'claude-sonnet-5-5');
    }

    public function test_activate_image_provider_requires_vision(): void
    {
        config(['services.ai.deepseek.key' => 'sk-deepseek-12345678', 'services.ai.deepseek.model' => 'deepseek-chat']);
        $this->actingAsUser();
        $this->putJson('/api/platform/ai/providers/anthropic', $this->anthropicPayload(['visionModel' => 'claude-sonnet-5-5']))
            ->assertOk()
            ->assertJsonPath('data.penyedia.anthropic.visionModel', 'claude-sonnet-5-5');

        $this->putJson('/api/platform/ai/providers/aktif', ['provider' => 'deepseek', 'untuk' => 'imej'])
            ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->putJson('/api/platform/ai/providers/aktif', ['provider' => 'deepseek', 'untuk' => 'teks'])
            ->assertOk()
            ->assertJsonPath('data.aktif', 'deepseek')
            ->assertJsonPath('data.aktifImej', 'deepseek')
            ->assertJsonPath('data.imejDisokong', false);

        $this->putJson('/api/platform/ai/providers/aktif', ['provider' => 'anthropic', 'untuk' => 'imej'])
            ->assertOk()
            ->assertJsonPath('data.aktif', 'deepseek')
            ->assertJsonPath('data.aktifImej', 'anthropic')
            ->assertJsonPath('data.imejDisokong', true)
            ->assertJsonPath('data.penyedia.anthropic.aktifImej', true);
    }

    public function test_sampling_params_dropped_for_models_that_reject_temperature(): void
    {
        $this->assertSame(['temperature' => 0.2], AiProviderService::samplingParams('claude-haiku-4-5', 0.2));
        $this->assertSame([], AiProviderService::samplingParams('claude-opus-5-5', 0.2));
    }
}
