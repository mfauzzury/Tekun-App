<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Provider-neutral JSON client for the POC 1 AI services (e-KYC, fraud, credit scoring).
 *
 * Routes to the provider made active in Integrasi AI > Penyedia AI: Anthropic via AnthropicClient,
 * or OpenAI / DeepSeek / an on-premise server via the OpenAI-compatible chat completions API.
 */
class AiClient
{
    public function __construct(
        protected AnthropicClient $anthropic,
    ) {}

    /**
     * Provider for a task: text work uses the active provider; image/document work uses the chosen
     * image provider, else the active one. Never silently falls back to a cloud provider, so an
     * on-premise setup cannot leak MyKad images — json() refuses instead.
     */
    public function provider(bool $forFiles = false): string
    {
        $active = (string) config('services.ai.active', 'anthropic');
        $active = array_key_exists($active, AiProviderService::PROVIDERS) ? $active : 'anthropic';
        if (! $forFiles) {
            return $active;
        }

        $vision = (string) config('services.ai.active_vision', '');
        if (array_key_exists($vision, AiProviderService::PROVIDERS) && $this->providerReadsImages($vision)) {
            return $vision;
        }

        return $active;
    }

    public function isConfigured(bool $forFiles = false): bool
    {
        $provider = $this->provider($forFiles);
        if ($provider === 'anthropic') {
            return $this->anthropic->isConfigured();
        }

        $cfg = (array) config("services.ai.{$provider}", []);
        if ($provider === 'onprem') {
            return ! empty($cfg['base_url']) && ! empty($cfg['model']);
        }

        return ! empty($cfg['key']) && ! empty($cfg['model']);
    }

    public function model(bool $forFiles = false): string
    {
        $provider = $this->provider($forFiles);
        if ($provider === 'anthropic') {
            return $forFiles ? $this->anthropicVisionModel() : $this->anthropic->model();
        }

        return (string) config("services.ai.{$provider}.model", '');
    }

    public function supportsFiles(): bool
    {
        return $this->providerReadsImages($this->provider(true));
    }

    protected function providerReadsImages(string $provider): bool
    {
        return $provider === 'anthropic' || (bool) config("services.ai.{$provider}.vision", false);
    }

    protected function anthropicVisionModel(): string
    {
        return (string) (config('services.anthropic.vision_model') ?: $this->anthropic->model());
    }

    /**
     * @param  list<array{path?: string, base64?: string, mime: string}>  $files
     * @return array{data: array<string, mixed>, latency_ms: int, model: string}
     *
     * @throws RuntimeException
     */
    public function json(string $system, string $prompt, array $files = [], int $maxTokens = 4096): array
    {
        $provider = $this->provider($files !== []);
        if ($provider === 'anthropic') {
            return $this->anthropic->json($system, $prompt, $files, $maxTokens, $files !== [] ? $this->anthropicVisionModel() : null);
        }

        if (! $this->isConfigured($files !== [])) {
            throw new RuntimeException('Penyedia AI ('.AiProviderService::PROVIDERS[$provider]['label'].') belum dikonfigurasi.');
        }
        if ($files !== [] && ! $this->supportsFiles()) {
            throw new RuntimeException('Penyedia AI aktif ('.AiProviderService::PROVIDERS[$provider]['label'].') tidak menyokong analisis imej atau dokumen. Pilih penyedia untuk imej di Integrasi AI > Penyedia AI.');
        }

        $cfg = (array) config("services.ai.{$provider}");
        $model = (string) $cfg['model'];

        $content = [];
        foreach ($files as $file) {
            $content[] = $this->fileBlock($file);
        }
        $content[] = ['type' => 'text', 'text' => $prompt];

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system."\n\nBalas dengan SATU objek JSON sahaja, tanpa teks lain dan tanpa markdown."],
                ['role' => 'user', 'content' => $files === [] ? $prompt : $content],
            ],
        ];
        // OpenAI's newer models only accept max_completion_tokens; compatible servers expect max_tokens.
        $body[$provider === 'openai' ? 'max_completion_tokens' : 'max_tokens'] = $maxTokens;
        if ($provider !== 'onprem') {
            $body['response_format'] = ['type' => 'json_object'];
        }

        $request = Http::acceptJson()->timeout(min((int) config('services.anthropic.timeout', 300), 180));
        if (! empty($cfg['key'])) {
            $request = $request->withToken((string) $cfg['key']);
        }

        $started = microtime(true);
        $response = $request->post(rtrim((string) $cfg['base_url'], '/').'/chat/completions', $body);
        $latency = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw new RuntimeException('Ralat penyedia AI '.$response->status().': '.($response->json('error.message') ?? 'tidak diketahui'));
        }

        $text = $response->json('choices.0.message.content');
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Tiada teks dalam respons model.');
        }

        return [
            'data' => $this->decodeJson($text),
            'latency_ms' => $latency,
            'model' => (string) ($response->json('model') ?? $model),
        ];
    }

    /**
     * @param  array{path?: string, base64?: string, mime: string}  $file
     * @return array<string, mixed>
     */
    protected function fileBlock(array $file): array
    {
        $mime = strtolower($file['mime']) === 'image/jpg' ? 'image/jpeg' : strtolower($file['mime']);
        $data = $file['base64'] ?? null;
        if ($data === null) {
            $path = (string) ($file['path'] ?? '');
            if ($path === '' || ! is_readable($path)) {
                throw new RuntimeException('Fail tidak dapat dibaca: '.basename($path));
            }
            $data = base64_encode((string) file_get_contents($path));
        }

        if ($mime === 'application/pdf') {
            return ['type' => 'file', 'file' => ['filename' => 'dokumen.pdf', 'file_data' => "data:{$mime};base64,{$data}"]];
        }

        return ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$data}"]];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)) ?? $text);
        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            }
        }
        if (! is_array($decoded)) {
            throw new RuntimeException('Respons model bukan JSON yang sah.');
        }

        return $decoded;
    }
}
