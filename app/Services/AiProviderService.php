<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * AI provider configuration managed from Integrasi AI > Penyedia AI: Anthropic Claude, OpenAI ChatGPT,
 * DeepSeek and an on-premise OpenAI-compatible server (Ollama, vLLM, LM Studio…).
 *
 * Values saved here override the env values at boot (config('services.anthropic.*') and
 * config('services.ai.*')), so services pick them up unchanged. API keys are stored encrypted
 * (APP_KEY) and never returned to the client. The active provider drives AiClient.
 */
class AiProviderService
{
    public const KEY_API_KEY = 'aiAnthropicApiKey';

    public const KEY_MODEL = 'aiAnthropicModel';

    public const KEY_CHAT_MODEL = 'aiAnthropicChatModel';

    public const KEY_TIMEOUT = 'aiAnthropicTimeout';

    public const KEY_ACTIVE = 'aiProviderActive';

    public const KEY_ACTIVE_VISION = 'aiProviderActiveVision';

    public const KEY_VISION_MODEL = 'aiAnthropicVisionModel';

    /**
     * @var array<string, array{label: string, penerangan: string, base_url: ?string, on_premise: bool, cadangan_model: list<string>}>
     */
    public const PROVIDERS = [
        'anthropic' => [
            'label' => 'Anthropic Claude',
            'penerangan' => 'Model Claude melalui API awan Anthropic. Menyokong analisis imej MyKad dan dokumen.',
            'base_url' => 'https://api.anthropic.com/v1',
            'on_premise' => false,
            'cadangan_model' => [],
        ],
        'openai' => [
            'label' => 'OpenAI ChatGPT',
            'penerangan' => 'Model GPT melalui API awan OpenAI. Menyokong analisis imej dan PDF.',
            'base_url' => 'https://api.openai.com/v1',
            'on_premise' => false,
            'cadangan_model' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4.1-mini'],
        ],
        'deepseek' => [
            'label' => 'DeepSeek',
            'penerangan' => 'Model DeepSeek melalui API awan. Teks sahaja — OCR MyKad dan pengesahan wajah tidak disokong.',
            'base_url' => 'https://api.deepseek.com',
            'on_premise' => false,
            'cadangan_model' => ['deepseek-chat', 'deepseek-reasoner'],
        ],
        'onprem' => [
            'label' => 'On-premise',
            'penerangan' => 'Pelayan model dalam pusat data TEKUN (Ollama, vLLM, LM Studio) melalui API serasi OpenAI. Data tidak keluar dari rangkaian TEKUN.',
            'base_url' => null,
            'on_premise' => true,
            'cadangan_model' => ['qwen2.5vl:7b', 'llama3.2-vision:11b', 'gemma3:12b', 'llama3.1:8b'],
        ],
    ];

    /**
     * Claude models selectable from the admin page.
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

    /** OpenAI-compatible providers configured through services.ai.{provider}. */
    protected const COMPATIBLE = ['openai', 'deepseek', 'onprem'];

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

    /** Settings key for an OpenAI-compatible provider field (Key, Model, BaseUrl, Vision). */
    public static function settingKey(string $provider, string $field): string
    {
        return 'aiProvider'.ucfirst($provider).$field;
    }

    /**
     * Overlay saved settings onto config('services.anthropic.*') and config('services.ai.*').
     */
    public function applyToConfig(): void
    {
        $rows = $this->rows();

        // Remember the untouched env values once, so clearing a saved key falls back to them.
        foreach (self::COMPATIBLE as $p) {
            if (! config()->has("services.ai.{$p}.env_key")) {
                config(["services.ai.{$p}.env_key" => config("services.ai.{$p}.key")]);
            }
        }

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
        $visionModel = $rows[self::KEY_VISION_MODEL] ?? null;
        if ($visionModel && isset(self::MODELS[$visionModel])) {
            config(['services.anthropic.vision_model' => $visionModel]);
        }

        foreach (self::COMPATIBLE as $p) {
            $pKey = $this->decryptKey($rows[self::settingKey($p, 'Key')] ?? null);
            if ($pKey !== null) {
                config(["services.ai.{$p}.key" => $pKey]);
            }
            $pModel = trim((string) ($rows[self::settingKey($p, 'Model')] ?? ''));
            if ($pModel !== '') {
                config(["services.ai.{$p}.model" => $pModel]);
            }
        }
        $baseUrl = trim((string) ($rows[self::settingKey('onprem', 'BaseUrl')] ?? ''));
        if ($baseUrl !== '') {
            config(['services.ai.onprem.base_url' => $baseUrl]);
        }
        if (isset($rows[self::settingKey('onprem', 'Vision')])) {
            config(['services.ai.onprem.vision' => $rows[self::settingKey('onprem', 'Vision')] === '1']);
        }

        $active = $rows[self::KEY_ACTIVE] ?? null;
        if ($active && isset(self::PROVIDERS[$active])) {
            config(['services.ai.active' => $active]);
        }
        $activeVision = $rows[self::KEY_ACTIVE_VISION] ?? null;
        if ($activeVision && isset(self::PROVIDERS[$activeVision])) {
            config(['services.ai.active_vision' => $activeVision]);
        }
    }

    /**
     * Status of every provider for the admin page (API keys masked).
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $rows = $this->rows();
        $client = app(AiClient::class);
        $active = $client->provider();
        $activeImage = $client->provider(true);

        $penyedia = [];
        foreach (array_keys(self::PROVIDERS) as $p) {
            $penyedia[$p] = $this->providerStatus($p, $rows, $p === $active) + ['aktif_imej' => $p === $activeImage];
        }

        return [
            'aktif' => $active,
            'aktif_imej' => $activeImage,
            'imej_disokong' => $client->supportsFiles(),
            'timeout' => (int) config('services.anthropic.timeout'),
            'penyedia' => $penyedia,
        ];
    }

    /**
     * @param  array<string, string|null>  $rows
     * @return array<string, mixed>
     */
    protected function providerStatus(string $p, array $rows, bool $isActive): array
    {
        $meta = self::PROVIDERS[$p];
        $storedKeyName = $p === 'anthropic' ? self::KEY_API_KEY : self::settingKey($p, 'Key');
        $stored = $rows[$storedKeyName] ?? null;
        $dbKey = $this->decryptKey($stored);
        $envKey = (string) ($p === 'anthropic' ? config('services.anthropic.env_key', '') : config("services.ai.{$p}.env_key", ''));
        [$source, $activeKey] = $dbKey !== null ? ['database', $dbKey] : ($envKey !== '' ? ['env', $envKey] : ['none', '']);

        $base = [
            'kod' => $p,
            'label' => $meta['label'],
            'penerangan' => $meta['penerangan'],
            'on_premise' => $meta['on_premise'],
            'aktif' => $isActive,
            'key_configured' => $activeKey !== '',
            'key_source' => $source,
            'key_hint' => $activeKey !== '' ? '••••'.substr($activeKey, -4) : null,
            'key_unreadable' => $stored !== null && $stored !== '' && $dbKey === null,
            'key_wajib' => $p !== 'onprem',
        ];

        if ($p === 'anthropic') {
            return $base + [
                'model' => (string) config('services.anthropic.model'),
                'chat_model' => (string) config('services.anthropic.chat_model'),
                'vision_model' => (string) (config('services.anthropic.vision_model') ?: config('services.anthropic.model')),
                'base_url' => $meta['base_url'],
                'vision' => true,
                'models' => collect(self::MODELS)->map(fn (array $m, string $id) => ['id' => $id] + $m)->values()->all(),
                'cadangan_model' => [],
                'dikonfigurasi' => $activeKey !== '',
            ];
        }

        $model = (string) config("services.ai.{$p}.model", '');
        $baseUrl = (string) config("services.ai.{$p}.base_url", $meta['base_url'] ?? '');

        return $base + [
            'model' => $model,
            'chat_model' => null,
            'vision_model' => null,
            'base_url' => $baseUrl,
            'vision' => (bool) config("services.ai.{$p}.vision", false),
            'models' => [],
            'cadangan_model' => $meta['cadangan_model'],
            'dikonfigurasi' => $p === 'onprem' ? ($baseUrl !== '' && $model !== '') : ($activeKey !== '' && $model !== ''),
        ];
    }

    /**
     * Persist one provider's settings. Returns the field names that changed (never key values).
     *
     * @param  array<string, mixed>  $data  api_key, clear_api_key, model, chat_model, timeout, base_url, vision
     * @return list<string>
     */
    public function update(string $provider, array $data): array
    {
        $changed = [];
        $keyName = $provider === 'anthropic' ? self::KEY_API_KEY : self::settingKey($provider, 'Key');

        if (! empty($data['clear_api_key'])) {
            DB::table('settings')->where('key', $keyName)->delete();
            $changed[] = 'api_key_cleared';
            if ($provider === 'anthropic') {
                config(['services.anthropic.key' => config('services.anthropic.env_key')]);
            } else {
                config(["services.ai.{$provider}.key" => config("services.ai.{$provider}.env_key")]);
            }
        } elseif (! empty($data['api_key'])) {
            $this->settingService->set($keyName, Crypt::encryptString(trim((string) $data['api_key'])));
            $changed[] = 'api_key';
        }

        $fields = $provider === 'anthropic'
            ? ['model' => self::KEY_MODEL, 'chat_model' => self::KEY_CHAT_MODEL, 'vision_model' => self::KEY_VISION_MODEL, 'timeout' => self::KEY_TIMEOUT]
            : ['model' => self::settingKey($provider, 'Model')];
        if ($provider === 'onprem') {
            $fields += ['base_url' => self::settingKey('onprem', 'BaseUrl'), 'vision' => self::settingKey('onprem', 'Vision')];
        }

        foreach ($fields as $field => $settingKey) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $value = is_bool($data[$field]) ? ($data[$field] ? '1' : '0') : trim((string) $data[$field]);
                $this->settingService->set($settingKey, $value);
                $changed[] = $field;
            }
        }

        $this->applyToConfig();

        return $changed;
    }

    /**
     * Make a configured provider the one used for text work ('teks') or for image/document work ('imej').
     * Returns an error message, or null on success.
     */
    public function setActive(string $provider, string $untuk = 'teks'): ?string
    {
        $status = $this->providerStatus($provider, $this->rows(), false);
        if (! $status['dikonfigurasi']) {
            return 'Lengkapkan API key / alamat pelayan dan model sebelum mengaktifkan penyedia ini.';
        }
        if ($untuk === 'imej' && ! $status['vision']) {
            return self::PROVIDERS[$provider]['label'].' tidak menyokong analisis imej.';
        }

        $this->settingService->set($untuk === 'imej' ? self::KEY_ACTIVE_VISION : self::KEY_ACTIVE, $provider);
        $this->applyToConfig();

        return null;
    }

    /**
     * Verify credentials and model availability without spending tokens (Models API).
     *
     * @return array{ok: bool, status: ?int, latency_ms: int, model: string, display_name: ?string, message: string, model_tersedia: list<string>}
     */
    public function testConnection(string $provider = 'anthropic', ?string $apiKey = null, ?string $model = null, ?string $baseUrl = null): array
    {
        if ($provider === 'anthropic') {
            return $this->testAnthropic($apiKey, $model);
        }

        $cfg = (array) config("services.ai.{$provider}", []);
        $apiKey = trim((string) ($apiKey ?: ($cfg['key'] ?? '')));
        $model = trim((string) ($model ?: ($cfg['model'] ?? '')));
        $baseUrl = rtrim(trim((string) ($baseUrl ?: ($cfg['base_url'] ?? ''))), '/');

        if ($baseUrl === '') {
            return $this->testResult(false, null, 0, $model, null, 'Alamat pelayan (base URL) belum ditetapkan.');
        }
        if ($provider !== 'onprem' && $apiKey === '') {
            return $this->testResult(false, null, 0, $model, null, 'Tiada API key dikonfigurasi.');
        }

        $request = Http::acceptJson()->timeout(15);
        if ($apiKey !== '') {
            $request = $request->withToken($apiKey);
        }

        $started = microtime(true);
        try {
            $response = $request->get($baseUrl.'/models');
        } catch (\Throwable $e) {
            return $this->testResult(false, null, $this->elapsed($started), $model, null, 'Gagal menghubungi pelayan: '.$e->getMessage());
        }
        $latency = $this->elapsed($started);
        $status = $response->status();

        if (! $response->successful()) {
            $message = match ($status) {
                401 => 'API key tidak sah.',
                403 => 'Akses ditolak oleh pelayan.',
                404 => 'Endpoint /models tidak ditemui — semak base URL (contoh: http://pelayan:11434/v1).',
                429 => 'Had kadar dicapai. Cuba sebentar lagi.',
                default => (string) ($response->json('error.message') ?? 'Ralat tidak dijangka daripada pelayan.'),
            };

            return $this->testResult(false, $status, $latency, $model, null, $message);
        }

        $ids = collect((array) $response->json('data', []))->pluck('id')->filter()->map(fn ($id) => (string) $id)->values();
        $found = $model === '' || $ids->isEmpty() || $ids->contains($model);

        return $this->testResult(
            $found,
            $status,
            $latency,
            $model,
            null,
            $found
                ? 'Sambungan berjaya.'.($model !== '' ? ' Model tersedia.' : '')
                : "Sambungan berjaya, tetapi model \"{$model}\" tiada dalam senarai pelayan.",
            $ids->take(30)->all(),
        );
    }

    /**
     * @return array{ok: bool, status: ?int, latency_ms: int, model: string, display_name: ?string, message: string, model_tersedia: list<string>}
     */
    protected function testAnthropic(?string $apiKey, ?string $model): array
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
            ->where(function ($q) {
                $q->whereIn('key', [self::KEY_API_KEY, self::KEY_MODEL, self::KEY_CHAT_MODEL, self::KEY_TIMEOUT, self::KEY_VISION_MODEL, self::KEY_ACTIVE])
                    ->orWhere('key', 'like', 'aiProvider%');
            })
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
     * @param  list<string>  $models
     * @return array{ok: bool, status: ?int, latency_ms: int, model: string, display_name: ?string, message: string, model_tersedia: list<string>}
     */
    protected function testResult(bool $ok, ?int $status, int $latency, string $model, ?string $displayName, string $message, array $models = []): array
    {
        return [
            'ok' => $ok,
            'status' => $status,
            'latency_ms' => $latency,
            'model' => $model,
            'display_name' => $displayName,
            'message' => $message,
            'model_tersedia' => $models,
        ];
    }
}
