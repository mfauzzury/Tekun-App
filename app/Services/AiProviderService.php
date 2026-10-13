<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Anthropic (Claude) provider configuration managed from Integrasi AI > Penyedia AI.
 *
 * Values saved here override the ANTHROPIC_* env values at boot, so every
 * service that reads config('services.anthropic.*') picks them up unchanged.
 * The API key is stored encrypted (APP_KEY) and never returned to the client.
 */
class AiProviderService
{
    public const KEY_API_KEY = 'aiAnthropicApiKey';

    public const KEY_MODEL = 'aiAnthropicModel';

    public const KEY_CHAT_MODEL = 'aiAnthropicChatModel';

    public const KEY_TIMEOUT = 'aiAnthropicTimeout';

    /**
     * Models selectable from the admin page.
     *
     * @var array<string, array{label: string, tier: string}>
     */
    public const MODELS = [
        'claude-opus-5-5' => ['label' => 'Claude Opus 5.5', 'tier' => 'Paling berkebolehan'],
        'claude-sonnet-5-5' => ['label' => 'Claude Sonnet 5.5', 'tier' => 'Seimbang'],
        'claude-haiku-5-5' => ['label' => 'Claude Haiku 5.5', 'tier' => 'Pantas & jimat'],
        'claude-sonnet-4-6' => ['label' => 'Claude Sonnet 4.6', 'tier' => 'Generasi terdahulu'],
        'claude-haiku-4-5' => ['label' => 'Claude Haiku 4.5', 'tier' => 'Generasi terdahulu'],
    ];

    /**
     * Models that still accept a non-default `temperature`. Newer models reject it with a 400.
     *
     * @var list<string>
     */
    protected const SAMPLING_MODELS = ['claude-haiku-4-5', 'claude-sonnet-4-6', 'claude-opus-4-6'];

    public function __construct(
        protected SettingService $settingService,
    ) {}

    /**
     * Sampling params safe to send for the given model.
     *
     * @return array<string, float>
     */
    public static function samplingParams(string $model, float $temperature): array
    {
        return in_array($model, self::SAMPLING_MODELS, true) ? ['temperature' => $temperature] : [];
    }

    /**
     * Effort params for models that support output_config.effort (the 5.x generation).
     *
     * @return array<string, array<string, string>>
     */
    public static function effortParams(string $model, string $effort): array
    {
        return in_array($model, self::SAMPLING_MODELS, true) ? [] : ['output_config' => ['effort' => $effort]];
    }

    /**
     * First text block of a Messages API response. Newer models may return thinking blocks before it.
     *
     * @param  array<string, mixed>  $response
     */
    public static function textFromResponse(array $response): ?string
    {
        foreach ((array) ($response['content'] ?? []) as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text' && trim((string) ($block['text'] ?? '')) !== '') {
                return (string) $block['text'];
            }
        }

        return null;
    }

    /**
     * Overlay saved settings onto config('services.anthropic.*').
     */
    public function applyToConfig(): void
    {
        $rows = $this->rows();

        $key = $this->decryptKey($rows[self::KEY_API_KEY] ?? null);
        if ($key !== null) {
            config(['services.anthropic.key' => $key]);
        }

        $model = $rows[self::KEY_MODEL] ?? null;
        if ($model && isset(self::MODELS[$model])) {
            config(['services.anthropic.model' => $model]);
        }

        $chatModel = $rows[self::KEY_CHAT_MODEL] ?? null;
        if ($chatModel && isset(self::MODELS[$chatModel])) {
            config(['services.anthropic.chat_model' => $chatModel]);
        }

        $timeout = (int) ($rows[self::KEY_TIMEOUT] ?? 0);
        if ($timeout > 0) {
            config(['services.anthropic.timeout' => $timeout]);
        }
    }

    /**
     * Current provider status for the admin page (API key masked).
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $rows = $this->rows();
        $stored = $rows[self::KEY_API_KEY] ?? null;
        $dbKey = $this->decryptKey($stored);
        $envKey = (string) config('services.anthropic.env_key', '');

        if ($dbKey !== null) {
            $source = 'database';
            $activeKey = $dbKey;
        } elseif ($envKey !== '') {
            $source = 'env';
            $activeKey = $envKey;
        } else {
            $source = 'none';
            $activeKey = '';
        }

        return [
            'provider' => 'anthropic',
            'name' => 'Anthropic Claude',
            'key_configured' => $activeKey !== '',
            'key_source' => $source,
            'key_hint' => $activeKey !== '' ? '••••'.substr($activeKey, -4) : null,
            'key_unreadable' => $stored !== null && $stored !== '' && $dbKey === null,
            'model' => (string) config('services.anthropic.model'),
            'chat_model' => (string) config('services.anthropic.chat_model'),
            'timeout' => (int) config('services.anthropic.timeout'),
            'models' => collect(self::MODELS)
                ->map(fn (array $meta, string $id) => ['id' => $id] + $meta)
                ->values()
                ->all(),
        ];
    }

    /**
     * Persist provider settings. Returns the field names that changed (never values of the key).
     *
     * @param  array{api_key?: ?string, clear_api_key?: bool, model?: string, chat_model?: string, timeout?: int}  $data
     * @return list<string>
     */
    public function update(array $data): array
    {
        $changed = [];

        if (! empty($data['clear_api_key'])) {
            DB::table('settings')->where('key', self::KEY_API_KEY)->delete();
            $changed[] = 'api_key_cleared';
        } elseif (! empty($data['api_key'])) {
            $this->settingService->set(self::KEY_API_KEY, Crypt::encryptString(trim($data['api_key'])));
            $changed[] = 'api_key';
        }

        foreach (['model' => self::KEY_MODEL, 'chat_model' => self::KEY_CHAT_MODEL, 'timeout' => self::KEY_TIMEOUT] as $field => $settingKey) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $this->settingService->set($settingKey, (string) $data[$field]);
                $changed[] = $field;
            }
        }

        if (in_array('api_key_cleared', $changed, true)) {
            config(['services.anthropic.key' => config('services.anthropic.env_key')]);
        }
        $this->applyToConfig();

        return $changed;
    }

    /**
     * Verify the key and model by retrieving the model from the Models API (no tokens billed).
     *
     * @return array{ok: bool, status: ?int, latency_ms: int, model: string, display_name: ?string, message: string}
     */
    public function testConnection(?string $apiKey = null, ?string $model = null): array
    {
        $apiKey = trim((string) ($apiKey ?: config('services.anthropic.key', '')));
        $model = $model ?: (string) config('services.anthropic.model');

        if ($apiKey === '') {
            return $this->testResult(false, null, 0, $model, null, 'Tiada API key dikonfigurasi.');
        }

        $started = microtime(true);
        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(15)->get('https://api.anthropic.com/v1/models/'.rawurlencode($model));
        } catch (\Throwable $e) {
            return $this->testResult(false, null, $this->elapsed($started), $model, null, 'Gagal menghubungi Anthropic: '.$e->getMessage());
        }

        $latency = $this->elapsed($started);
        $status = $response->status();

        if ($response->successful()) {
            return $this->testResult(true, $status, $latency, $model, $response->json('display_name'), 'Sambungan berjaya. Model tersedia.');
        }

        $message = match ($status) {
            401 => 'API key tidak sah.',
            403 => 'API key tiada kebenaran untuk model ini.',
            404 => 'Model tidak ditemui atau tiada akses untuk akaun ini.',
            429 => 'Had kadar dicapai. Cuba sebentar lagi.',
            default => (string) ($response->json('error.message') ?? 'Ralat tidak dijangka daripada Anthropic.'),
        };

        return $this->testResult(false, $status, $latency, $model, null, $message);
    }

    /**
     * @return array<string, string|null>
     */
    protected function rows(): array
    {
        return DB::table('settings')
            ->whereIn('key', [self::KEY_API_KEY, self::KEY_MODEL, self::KEY_CHAT_MODEL, self::KEY_TIMEOUT])
            ->pluck('value', 'key')
            ->toArray();
    }

    protected function decryptKey(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            $key = Crypt::decryptString($stored);
        } catch (DecryptException) {
            // Saved with a different APP_KEY, or tampered with.
            return null;
        }

        return $key !== '' ? $key : null;
    }

    protected function elapsed(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }

    /**
     * @return array{ok: bool, status: ?int, latency_ms: int, model: string, display_name: ?string, message: string}
     */
    protected function testResult(bool $ok, ?int $status, int $latency, string $model, ?string $displayName, string $message): array
    {
        return [
            'ok' => $ok,
            'status' => $status,
            'latency_ms' => $latency,
            'model' => $model,
            'display_name' => $displayName,
            'message' => $message,
        ];
    }
}
