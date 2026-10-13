<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
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

    private function actingAsUser(array $permissions = ['settings.view', 'settings.edit']): User
    {
        $role = Role::create([
            'name' => 'admin',
            'description' => 'Admin',
            'permissions' => $permissions,
        ]);

        $user = User::factory()->create([
            'role' => 'admin',
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    private function validPayload(array $overrides = []): array
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
        $this->putJson('/api/platform/ai/providers', $this->validPayload())->assertStatus(401);
        $this->postJson('/api/platform/ai/providers/test')->assertStatus(401);
    }

    public function test_requires_settings_permissions(): void
    {
        $this->actingAsUser(['posts.view']);

        $this->getJson('/api/platform/ai/providers')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'FORBIDDEN');
        $this->putJson('/api/platform/ai/providers', $this->validPayload())->assertStatus(403);
    }

    public function test_view_only_user_cannot_update_or_test(): void
    {
        $this->actingAsUser(['settings.view']);

        $this->getJson('/api/platform/ai/providers')->assertOk();
        $this->putJson('/api/platform/ai/providers', $this->validPayload())->assertStatus(403);
        $this->postJson('/api/platform/ai/providers/test')->assertStatus(403);
    }

    public function test_validation_error(): void
    {
        $this->actingAsUser();

        $this->putJson('/api/platform/ai/providers', $this->validPayload([
            'model' => 'gpt-4o',
            'timeout' => 5,
            'apiKey' => 'short',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['details' => ['model', 'timeout', 'apiKey']]]);
    }

    public function test_show_returns_status_without_key(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/platform/ai/providers')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'provider', 'keyConfigured', 'keySource', 'keyHint', 'model', 'chatModel', 'timeout',
                'models' => [['id', 'label', 'tier']],
            ]])
            ->assertJsonMissingPath('data.apiKey');
    }

    public function test_update_stores_key_encrypted_and_masks_it(): void
    {
        $this->actingAsUser();

        $response = $this->putJson('/api/platform/ai/providers', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('data.keyConfigured', true)
            ->assertJsonPath('data.keySource', 'database')
            ->assertJsonPath('data.keyHint', '••••WXYZ')
            ->assertJsonPath('data.model', 'claude-opus-5-5')
            ->assertJsonPath('data.chatModel', 'claude-haiku-5-5')
            ->assertJsonPath('data.timeout', 120);

        $this->assertStringNotContainsString(self::KEY, $response->getContent());

        $stored = DB::table('settings')->where('key', AiProviderService::KEY_API_KEY)->value('value');
        $this->assertNotSame(self::KEY, $stored);
        $this->assertSame(self::KEY, Crypt::decryptString($stored));
        $this->assertSame(self::KEY, config('services.anthropic.key'));

        $audit = DB::table('audit_logs')->where('action', 'ai_provider.updated')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString(self::KEY, (string) $audit->new_values);
    }

    public function test_update_without_key_keeps_existing_key(): void
    {
        $this->actingAsUser();
        $this->putJson('/api/platform/ai/providers', $this->validPayload())->assertOk();

        $this->putJson('/api/platform/ai/providers', $this->validPayload(['apiKey' => null, 'model' => 'claude-haiku-4-5']))
            ->assertOk()
            ->assertJsonPath('data.keySource', 'database')
            ->assertJsonPath('data.model', 'claude-haiku-4-5');
    }

    public function test_clear_key_falls_back_to_env(): void
    {
        config(['services.anthropic.env_key' => null]);
        $this->actingAsUser();
        $this->putJson('/api/platform/ai/providers', $this->validPayload())->assertOk();

        $this->putJson('/api/platform/ai/providers', $this->validPayload(['apiKey' => null, 'clearApiKey' => true]))
            ->assertOk()
            ->assertJsonPath('data.keyConfigured', false)
            ->assertJsonPath('data.keySource', 'none');
    }

    public function test_generic_settings_endpoint_cannot_overwrite_ai_keys(): void
    {
        $this->actingAsUser();
        $this->putJson('/api/platform/ai/providers', $this->validPayload())->assertOk();

        $this->putJson('/api/settings', ['aiAnthropicApiKey' => 'hijack', 'aiAnthropicModel' => 'claude-haiku-4-5'])->assertOk();

        $this->assertSame(self::KEY, Crypt::decryptString(
            DB::table('settings')->where('key', AiProviderService::KEY_API_KEY)->value('value')
        ));
        $this->assertSame('claude-opus-5-5', DB::table('settings')->where('key', AiProviderService::KEY_MODEL)->value('value'));
    }

    public function test_connection_success(): void
    {
        Http::fake([
            'api.anthropic.com/v1/models/*' => Http::response(['id' => 'claude-opus-5-5', 'display_name' => 'Claude Opus 5.5'], 200),
        ]);
        $this->actingAsUser();

        $this->postJson('/api/platform/ai/providers/test', ['apiKey' => self::KEY, 'model' => 'claude-opus-5-5'])
            ->assertOk()
            ->assertJsonPath('data.ok', true)
            ->assertJsonPath('data.displayName', 'Claude Opus 5.5');

        Http::assertSent(fn ($request) => $request->hasHeader('x-api-key', self::KEY)
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && str_ends_with($request->url(), '/v1/models/claude-opus-5-5'));
    }

    public function test_connection_reports_invalid_key(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => ['message' => 'invalid x-api-key']], 401),
        ]);
        $this->actingAsUser();

        $this->postJson('/api/platform/ai/providers/test', ['apiKey' => self::KEY])
            ->assertOk()
            ->assertJsonPath('data.ok', false)
            ->assertJsonPath('data.status', 401);
    }

    public function test_connection_without_any_key(): void
    {
        Http::fake();
        config(['services.anthropic.key' => null]);
        $this->actingAsUser();

        $this->postJson('/api/platform/ai/providers/test')
            ->assertOk()
            ->assertJsonPath('data.ok', false);

        Http::assertNothingSent();
    }

    public function test_sampling_params_dropped_for_models_that_reject_temperature(): void
    {
        $this->assertSame(['temperature' => 0.2], AiProviderService::samplingParams('claude-haiku-4-5', 0.2));
        $this->assertSame([], AiProviderService::samplingParams('claude-opus-5-5', 0.2));
        $this->assertSame([], AiProviderService::samplingParams('claude-haiku-5-5', 0.2));
    }
}
