<?php

namespace App\Services;

use App\Models\ChatSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AinaChatService
{
    private const MAX_HISTORY_MESSAGES = 40;

    public function createSessionThreadId(): string
    {
        return 'claude-'.Str::uuid();
    }

    /**
     * @param  array<string>  $imageBase64Urls  data:image/...;base64,... URLs
     * @return array{content: string, citations: array<int, string>}|array{error: string}
     */
    public function sendMessage(
        ChatSession $session,
        string $userMessage,
        array $imageBase64Urls = [],
        ?string $documentText = null,
    ): array {
        $apiKey = (string) config('services.anthropic.key', '');
        if ($apiKey === '') {
            return ['error' => 'ANTHROPIC_API_KEY tidak dikonfigurasi.'];
        }

        $messages = $this->buildMessages($session, $userMessage, $imageBase64Urls, $documentText);
        if ($messages === []) {
            return ['error' => 'Tiada mesej untuk dihantar ke AI.'];
        }

        $model = (string) config('services.anthropic.chat_model', config('services.anthropic.model', 'claude-haiku-4-5'));
        $timeout = min((int) config('services.anthropic.timeout', 300), 120);

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout($timeout)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 4096,
            'system' => $this->systemPrompt(),
            'messages' => $messages,
        ]);

        if (! $response->successful()) {
            $body = $response->json();
            $error = $body['error']['message'] ?? $body['error']['type'] ?? $response->body();
            Log::warning('AinaChat Anthropic failed', ['status' => $response->status(), 'body' => $body]);

            return ['error' => (string) $error];
        }

        $text = $response->json('content.0.text');
        if (! is_string($text) || trim($text) === '') {
            return ['error' => 'Claude tidak mengembalikan jawapan.'];
        }

        return ['content' => trim($text), 'citations' => []];
    }

    /**
     * @param  array<string>  $imageBase64Urls
     * @return list<array{role: string, content: string|array<int, array<string, mixed>>}>
     */
    private function buildMessages(
        ChatSession $session,
        string $latestUserMessage,
        array $imageBase64Urls,
        ?string $documentText,
    ): array {
        $dbMessages = $session->messages()->orderBy('created_at')->get();
        if ($dbMessages->isEmpty()) {
            return [[
                'role' => 'user',
                'content' => $this->buildUserContent($latestUserMessage, $imageBase64Urls, $documentText),
            ]];
        }

        $dbMessages = $dbMessages->slice(-self::MAX_HISTORY_MESSAGES)->values();
        $history = [];
        $lastIndex = $dbMessages->count() - 1;

        foreach ($dbMessages as $index => $msg) {
            $isLast = $index === $lastIndex;
            if ($isLast && $msg->role === 'user') {
                $history[] = [
                    'role' => 'user',
                    'content' => $this->buildUserContent($latestUserMessage, $imageBase64Urls, $documentText),
                ];

                continue;
            }

            $role = $msg->role === 'assistant' ? 'assistant' : 'user';
            $history[] = [
                'role' => $role,
                'content' => (string) ($msg->content ?? ''),
            ];
        }

        return $this->normalizeAlternatingRoles($history);
    }

    /**
     * @param  list<array{role: string, content: string|array<int, array<string, mixed>>}>  $messages
     * @return list<array{role: string, content: string|array<int, array<string, mixed>>}>
     */
    private function normalizeAlternatingRoles(array $messages): array
    {
        $normalized = [];

        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];

            if ($normalized !== [] && end($normalized)['role'] === $role) {
                $lastKey = array_key_last($normalized);
                $prev = $normalized[$lastKey]['content'];
                if (is_string($prev) && is_string($content)) {
                    $normalized[$lastKey]['content'] = trim($prev."\n\n".$content);
                } else {
                    $normalized[] = $message;
                }

                continue;
            }

            $normalized[] = $message;
        }

        if ($normalized !== [] && $normalized[0]['role'] !== 'user') {
            array_shift($normalized);
        }

        return $normalized;
    }

    /**
     * @param  array<string>  $imageBase64Urls
     * @return string|array<int, array<string, mixed>>
     */
    private function buildUserContent(string $text, array $imageBase64Urls, ?string $documentText): string|array
    {
        $textContent = $text;
        if ($documentText) {
            $textContent = "[Dokumen yang dilampirkan:\n\n{$documentText}]\n\n---\n\nSoalan pengguna: {$text}";
        }

        if ($imageBase64Urls === []) {
            return $textContent;
        }

        $blocks = [['type' => 'text', 'text' => $textContent]];
        foreach (array_slice($imageBase64Urls, 0, 3) as $url) {
            $parsed = $this->parseDataUrl($url);
            if ($parsed) {
                $blocks[] = [
                    'type' => 'image',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => $parsed['media_type'],
                        'data' => $parsed['data'],
                    ],
                ];
            }
        }

        return $blocks;
    }

    /** @return array{media_type: string, data: string}|null */
    private function parseDataUrl(string $url): ?array
    {
        if (! str_starts_with($url, 'data:')) {
            return null;
        }

        if (! preg_match('#^data:([^;]+);base64,(.+)$#', $url, $matches)) {
            return null;
        }

        return [
            'media_type' => $matches[1],
            'data' => $matches[2],
        ];
    }

    private function systemPrompt(): string
    {
        $baseUrl = rtrim((string) config('services.sppt.system_url', 'http://localhost'), '/');

        return <<<PROMPT
You are **AINA** (*AI Navigation & Innovation*) — a helpful assistant for **end users** of the **SPPT Pengurusan Pembiayaan** system (TEKUN financing management).

## Identity & greeting (User Chat)
- Your public name is **AINA**. On the **first user message** in a conversation, give a **brief greeting** (1–2 sentences): introduce yourself as AINA, state you help with how-to and navigation from official documentation, and invite questions. Match the user's language (Bahasa Malaysia or English). **Do not** repeat this full greeting on every subsequent reply in the same thread.

## CRITICAL RESTRICTIONS (NEVER BREAK)
- **NEVER run SQL queries.** You do NOT have access to query the database.
- **NEVER reveal or describe database schema** (table names, columns, structure) to the user.
- Answer from SPPT user manuals, BRS, walkthrough guides, and attached documents when provided.

## What You CAN Do
- Answer how-to questions about SPPT procedures and workflows
- Explain features and navigation for Permohonan, Pembiayaan, Pembayaran, and related modules
- Answer in the same language the user uses (Bahasa Malaysia or English)
- Analyze images and documents the user attaches in the current message

## What You CANNOT Do
- Query live data — tell the user to contact their administrator or support
- Provide SQL or database schema information

## Menu Navigation & Links
- Base system URL: {$baseUrl}
- When you know a direct admin path, include it as Markdown: [Papar di sini](url)

## Diagrams — Use PlantUML (MANDATORY)
When user asks for ERD or diagrams, output PlantUML in a ```plantuml ... ``` fenced block, not ASCII art.

## Response Style
- Be helpful and concise; use clear formatting (headings, lists)
PROMPT;
    }
}
