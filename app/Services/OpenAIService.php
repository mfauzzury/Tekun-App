<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private string $apiKey;

    private string $baseUrl = 'https://api.openai.com/v1';

    private string $assistantId;

    private string $vectorStoreId;

    private ?string $userChatAssistantId = null;

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.key');
        $this->assistantId = (string) config('services.openai.assistant_id', '');
        $this->userChatAssistantId = config('services.openai.user_chat_assistant_id') ?: null;
        $this->vectorStoreId = (string) config('services.openai.vector_store_id', '');
    }

    private function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2',
        ];
    }

    public function uploadFileForVision(string $filePath, string $filename): array
    {
        $upload = fn () => Http::timeout(90)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'OpenAI-Beta' => 'assistants=v2',
            ])->attach('file', file_get_contents($filePath), $filename)
            ->post("{$this->baseUrl}/files", ['purpose' => 'vision']);

        $response = $upload();
        if ($response->serverError()) {
            sleep(2);
            $response = $upload();
        }

        $data = $response->json();
        if (! isset($data['id'])) {
            $errMsg = $data['error']['message'] ?? $data['error']['code'] ?? $response->body();

            return ['error' => (string) $errMsg, 'details' => $data];
        }

        return ['file_id' => $data['id']];
    }

    public function createThread(): array
    {
        $response = Http::timeout(30)
            ->withHeaders($this->headers())
            ->post("{$this->baseUrl}/threads", []);

        $data = $response->json();
        if ($response->failed()) {
            $errMsg = $data['error']['message'] ?? $data['error']['code'] ?? $response->body();
            Log::warning('OpenAI createThread failed', ['status' => $response->status(), 'body' => $data]);

            return ['error' => "OpenAI API: {$errMsg}"];
        }

        return $data;
    }

    /**
     * @param  array<string>  $imageFileIds
     */
    public function sendMessage(
        string $threadId,
        string $message,
        array $imageFileIds = [],
        ?string $documentText = null,
        bool $isUserChat = false,
    ): array {
        $this->cancelActiveRuns($threadId);

        $textContent = $message;
        if ($documentText) {
            $textContent = "[Dokumen yang dilampirkan:\n\n{$documentText}]\n\n---\n\nSoalan pengguna: {$message}";
        }

        $content = $this->buildMessageContent($textContent, $imageFileIds);

        $postMessage = fn () => Http::timeout(90)
            ->withHeaders($this->headers())
            ->post("{$this->baseUrl}/threads/{$threadId}/messages", [
                'role' => 'user',
                'content' => $content,
            ]);

        $msgResponse = $postMessage();
        if ($msgResponse->serverError()) {
            sleep(2);
            $msgResponse = $postMessage();
        }

        if ($msgResponse->failed()) {
            $body = $msgResponse->json();
            $err = $body['error'] ?? [];
            $code = $err['code'] ?? '';
            $msg = $err['message'] ?? $err['code'] ?? $msgResponse->body();
            $errMsg = $code ? "{$code}|{$msg}" : "OpenAI API: {$msg}";
            Log::warning('OpenAI add message failed', ['status' => $msgResponse->status(), 'body' => $body]);

            return ['error' => $errMsg];
        }

        $assistantId = ($isUserChat && $this->userChatAssistantId) ? $this->userChatAssistantId : $this->assistantId;
        if ($isUserChat && ! $this->userChatAssistantId) {
            Log::warning('OpenAI: OPENAI_USER_CHAT_ASSISTANT_ID not set; using fallback assistant for User Chat');
        }

        $runResponse = Http::timeout(30)
            ->withHeaders($this->headers())
            ->post("{$this->baseUrl}/threads/{$threadId}/runs", [
                'assistant_id' => $assistantId,
            ]);

        $run = $runResponse->json();
        if ($runResponse->failed()) {
            $err = $run['error'] ?? [];
            $code = $err['code'] ?? '';
            $msg = $err['message'] ?? $err['code'] ?? $runResponse->body();
            $errMsg = $code ? "{$code}|{$msg}" : "OpenAI API: {$msg}";
            Log::warning('OpenAI create run failed', ['status' => $runResponse->status(), 'body' => $run]);

            return ['error' => $errMsg];
        }

        $runId = $run['id'] ?? null;
        if (! $runId) {
            return ['error' => 'Failed to create run', 'details' => $run];
        }

        $maxAttempts = 60;
        $attempt = 0;
        $runData = $run;

        do {
            sleep(1);
            $statusResponse = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/threads/{$threadId}/runs/{$runId}");
            $runData = $statusResponse->json();
            $status = $runData['status'] ?? 'failed';
            $attempt++;

            if ($status === 'requires_action') {
                $toolOutputs = $this->handleToolCalls($runData);

                Http::withHeaders($this->headers())
                    ->post("{$this->baseUrl}/threads/{$threadId}/runs/{$runId}/submit_tool_outputs", [
                        'tool_outputs' => $toolOutputs,
                    ]);

                $status = 'in_progress';
            }
        } while (in_array($status, ['queued', 'in_progress']) && $attempt < $maxAttempts);

        if ($status !== 'completed') {
            $lastErr = $runData['last_error'] ?? [];
            $code = $lastErr['code'] ?? '';
            $message = $lastErr['message'] ?? $lastErr['code'] ?? "Run ended with status: {$status}";
            $errMsg = $code ? "{$code}|{$message}" : $message;
            Log::warning('OpenAI run not completed', ['status' => $status, 'last_error' => $lastErr]);

            return ['error' => $errMsg];
        }

        $messagesResponse = Http::withHeaders($this->headers())
            ->get("{$this->baseUrl}/threads/{$threadId}/messages", [
                'order' => 'desc',
                'limit' => 1,
            ]);

        $messages = $messagesResponse->json();
        $latest = $messages['data'][0] ?? null;

        if (! $latest || $latest['role'] !== 'assistant') {
            return ['error' => 'No assistant response found'];
        }

        return $this->parseMessage($latest);
    }

    /**
     * @param  array<string>  $imageBase64Urls
     * @return array{content: string, citations: array<int, string>}|array{error: string}
     */
    public function chatWithVision(string $userMessage, ?string $documentText, array $imageBase64Urls): array
    {
        if (empty($imageBase64Urls)) {
            return ['error' => 'No images provided'];
        }

        $textContent = $userMessage;
        if ($documentText) {
            $textContent = "[Dokumen yang dilampirkan:\n\n{$documentText}]\n\n---\n\nSoalan pengguna: {$userMessage}";
        }

        $content = [['type' => 'text', 'text' => $textContent]];
        foreach (array_slice($imageBase64Urls, 0, 3) as $url) {
            if (str_starts_with($url, 'data:')) {
                $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url, 'detail' => 'low']];
            }
        }

        $response = Http::timeout(90)
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => 'gpt-4o',
                'max_tokens' => 4096,
                'messages' => [
                    ['role' => 'system', 'content' => $this->visionSystemPrompt()],
                    ['role' => 'user', 'content' => $content],
                ],
            ]);

        $data = $response->json();
        if ($response->failed()) {
            $errMsg = $data['error']['message'] ?? $data['error']['code'] ?? $response->body();

            return ['error' => "OpenAI API: {$errMsg}"];
        }

        $choice = $data['choices'][0] ?? null;
        $text = $choice['message']['content'] ?? '';

        return ['content' => trim($text), 'citations' => []];
    }

    public function createUserChatAssistant(): array
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/assistants", [
                'name' => 'AINA User Chat AI',
                'instructions' => $this->userChatSystemPrompt(),
                'model' => 'gpt-4o',
                'tools' => $this->userChatAssistantTools(),
                'tool_resources' => [
                    'file_search' => [
                        'vector_store_ids' => [$this->vectorStoreId],
                    ],
                ],
            ]);

        return $response->json();
    }

    public function updateUserChatAssistant(string $assistantId): array
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/assistants/{$assistantId}", [
                'instructions' => $this->userChatSystemPrompt(),
                'tools' => $this->userChatAssistantTools(),
                'tool_resources' => [
                    'file_search' => [
                        'vector_store_ids' => [$this->vectorStoreId],
                    ],
                ],
            ]);

        return $response->json();
    }

    /**
     * @param  array<string>  $imageFileIds
     * @return array<int, array<string, mixed>>|string
     */
    private function buildMessageContent(string $text, array $imageFileIds): array|string
    {
        if (empty($imageFileIds)) {
            return $text;
        }

        $blocks = [['type' => 'text', 'text' => $text]];
        foreach (array_slice($imageFileIds, 0, 3) as $fileId) {
            $blocks[] = ['type' => 'image_file', 'image_file' => ['file_id' => $fileId, 'detail' => 'low']];
        }

        return $blocks;
    }

    private function cancelActiveRuns(string $threadId): void
    {
        $runsResponse = Http::timeout(10)
            ->withHeaders($this->headers())
            ->get("{$this->baseUrl}/threads/{$threadId}/runs", ['limit' => 10]);

        if ($runsResponse->failed()) {
            return;
        }

        $data = $runsResponse->json();
        $runs = $data['data'] ?? [];

        foreach ($runs as $run) {
            $status = $run['status'] ?? '';
            if (in_array($status, ['queued', 'in_progress'])) {
                Http::timeout(10)
                    ->withHeaders($this->headers())
                    ->post("{$this->baseUrl}/threads/{$threadId}/runs/{$run['id']}/cancel");
            }
        }
    }

    /**
     * @return list<array{tool_call_id: string, output: string}>
     */
    private function handleToolCalls(array $runData): array
    {
        $toolCalls = $runData['required_action']['submit_tool_outputs']['tool_calls'] ?? [];
        $outputs = [];

        foreach ($toolCalls as $call) {
            $name = $call['function']['name'] ?? '';
            $callId = $call['id'];
            $output = ['error' => "Unsupported tool in User Chat: {$name}"];

            $outputs[] = [
                'tool_call_id' => $callId,
                'output' => json_encode($output, JSON_UNESCAPED_UNICODE),
            ];
        }

        return $outputs;
    }

    /**
     * @return array{role: string, content: string, citations: array<int, string>}
     */
    private function parseMessage(array $message): array
    {
        $content = '';
        $citations = [];

        foreach ($message['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $text = $block['text']['value'] ?? '';
                $annotations = $block['text']['annotations'] ?? [];

                foreach ($annotations as $annotation) {
                    if ($annotation['type'] === 'file_citation') {
                        $citations[] = $annotation['file_citation']['file_id'] ?? '';
                        $text = str_replace($annotation['text'], '', $text);
                    }
                }

                $content .= $text;
            }
        }

        return [
            'role' => $message['role'],
            'content' => trim($content),
            'citations' => array_values(array_unique($citations)),
        ];
    }

    private function visionSystemPrompt(): string
    {
        return 'You are **AINA** — a helpful assistant for SPPT Pengurusan Pembiayaan end users. Analyze the attached image(s) and answer '
            .'in the same language the user uses (Bahasa Malaysia or English). Explain procedures from documentation '
            .'when visible; do not invent database queries or live data.';
    }

    /**
     * @return list<array{type: string}>
     */
    private function userChatAssistantTools(): array
    {
        return [
            ['type' => 'file_search'],
        ];
    }

    private function userChatSystemPrompt(): string
    {
        $baseUrl = rtrim((string) config('services.sppt.system_url', 'http://localhost'), '/');

        return <<<PROMPT
You are **AINA** (*AI Navigation & Innovation*) — a helpful assistant for **end users** of the **SPPT Pengurusan Pembiayaan** system (TEKUN financing management).

## Identity & greeting (User Chat)
- Your public name is **AINA**. On the **first user message** in a conversation, give a **brief greeting** (1–2 sentences): introduce yourself as AINA, state you help with how-to and navigation from official documentation, and invite questions. Match the user's language (Bahasa Malaysia or English). **Do not** repeat this full greeting on every subsequent reply in the same thread.

## CRITICAL RESTRICTIONS (NEVER BREAK)
- **NEVER run SQL queries.** You do NOT have access to query the database.
- **NEVER reveal or describe database schema** (table names, columns, structure) to the user.
- Use ONLY the Knowledge Base documents (User Manuals, BRS, Walkthrough guides, procedure docs) to answer.

## What You CAN Do
- Answer how-to questions based on documentation
- Explain procedures, workflows, and features from the Knowledge Base
- Answer in the same language the user uses (Bahasa Malaysia or English)

## What You CANNOT Do
- Query live data — tell the user to contact their administrator or support
- Provide SQL or database schema information

## Menu Navigation & Links
- Base system URL: {$baseUrl}
- When the KB has a direct URL or menuid, include it as Markdown: [Papar di sini](url)

## Diagrams — Use PlantUML (MANDATORY)
When user asks for ERD or diagrams, output PlantUML in a ```plantuml ... ``` fenced block, not ASCII art.

## Response Style
- Be helpful and concise; use clear formatting (headings, lists)
PROMPT;
    }
}
