<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin Messages API client shared by the POC 1 AI services (e-KYC, fraud, credit scoring).
 *
 * Reads key/model/timeout from config('services.anthropic.*'), which Penyedia AI may override.
 */
class AnthropicClient
{
    protected const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    protected const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function isConfigured(): bool
    {
        return (string) config('services.anthropic.key', '') !== '';
    }

    public function model(): string
    {
        return (string) config('services.anthropic.model', 'claude-haiku-4-5');
    }

    /**
     * Ask for a JSON object, optionally with images/PDFs attached.
     *
     * @param  list<array{path?: string, base64?: string, mime: string}>  $files
     * @return array{data: array<string, mixed>, latency_ms: int, model: string}
     *
     * @throws RuntimeException when the key is missing, the call fails, or the reply is not JSON
     */
    public function json(string $system, string $prompt, array $files = [], int $maxTokens = 4096): array
    {
        $apiKey = (string) config('services.anthropic.key', '');
        if ($apiKey === '') {
            throw new RuntimeException('Anthropic API key tidak dikonfigurasi.');
        }

        $model = $this->model();
        $content = [];
        foreach ($files as $file) {
            $content[] = $this->fileBlock($file);
        }
        $content[] = ['type' => 'text', 'text' => $prompt];

        $started = microtime(true);
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(min((int) config('services.anthropic.timeout', 300), 180))->post(self::ENDPOINT, [
            'model' => $model,
            'system' => $system."\n\nBalas dengan SATU objek JSON sahaja, tanpa teks lain dan tanpa markdown.",
            'messages' => [['role' => 'user', 'content' => $content]],
            'max_tokens' => $maxTokens,
        ] + AiProviderService::samplingParams($model, 0.0) + AiProviderService::effortParams($model, 'low'));
        $latency = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            throw new RuntimeException('Anthropic API ralat '.$response->status().': '.($response->json('error.message') ?? 'tidak diketahui'));
        }

        $text = AiProviderService::textFromResponse((array) $response->json());
        if ($text === null) {
            throw new RuntimeException('Tiada teks dalam respons model (stop_reason: '.($response->json('stop_reason') ?? '-').').');
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
        $mime = strtolower($file['mime']);
        if ($mime === 'image/jpg') {
            $mime = 'image/jpeg';
        }

        $data = $file['base64'] ?? null;
        if ($data === null) {
            $path = (string) ($file['path'] ?? '');
            if ($path === '' || ! is_readable($path)) {
                throw new RuntimeException('Fail tidak dapat dibaca: '.basename($path));
            }
            $data = base64_encode((string) file_get_contents($path));
        }

        if ($mime === 'application/pdf') {
            return ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $data]];
        }

        if (! in_array($mime, self::IMAGE_MIMES, true)) {
            throw new RuntimeException('Jenis fail tidak disokong untuk analisis AI: '.$mime);
        }

        return ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $data]];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;

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
