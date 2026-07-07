<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendUserChatMessageRequest;
use App\Http\Requests\StoreUserChatSessionRequest;
use App\Http\Requests\UpdateUserChatSessionRequest;
use App\Http\Traits\ApiResponse;
use App\Models\ChatMessage;
use App\Models\ChatMessageFavorite;
use App\Models\ChatSession;
use App\Models\ChatSessionFavorite;
use App\Services\AinaChatService;
use App\Services\ChatAttachmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserChatController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AinaChatService $ainaChatService,
        protected ChatAttachmentService $chatAttachment,
    ) {}

    /** @return Builder<ChatSession> */
    private function userChatScope(): Builder
    {
        return ChatSession::where('user_id', Auth::id())->where('chat_type', 'user');
    }

    public function newUserChatSession(StoreUserChatSessionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $session = ChatSession::create([
            'openai_thread_id' => $this->ainaChatService->createSessionThreadId(),
            'title' => $data['title'] ?? 'New Chat',
            'module_filter' => $data['module_filter'] ?? null,
            'user_id' => Auth::id(),
            'session_type' => 'solo',
            'chat_type' => 'user',
        ]);

        return $this->sendCreated(['session' => $session->toArray(), 'messages' => []]);
    }

    public function myUserChatSessions(): JsonResponse
    {
        $userId = Auth::id();
        $sessions = $this->userChatScope()->orderBy('updated_at', 'desc')->limit(20)->get();
        $favIds = ChatSessionFavorite::where('user_id', $userId)
            ->whereIn('chat_session_id', $sessions->pluck('id'))
            ->pluck('chat_session_id')
            ->all();
        $data = $sessions->map(fn ($s) => array_merge($s->toArray(), [
            'is_favorited' => in_array($s->id, $favIds),
        ]))->all();

        return $this->sendOk($data);
    }

    public function getUserChatSession(int $sessionId): JsonResponse
    {
        $session = $this->userChatScope()->with(['messages.replyToMessage', 'messages.replyToUser'])
            ->find($sessionId);
        if (! $session) {
            return $this->sendError(404, 'NOT_FOUND', 'Chat session not found');
        }
        $data = $session->toArray();
        $data['is_favorited'] = ChatSessionFavorite::where('user_id', Auth::id())
            ->where('chat_session_id', $sessionId)->exists();

        return $this->sendOk($data);
    }

    public function sendUserChatMessage(SendUserChatMessageRequest $request, int $sessionId): JsonResponse
    {
        set_time_limit(120);

        $session = $this->userChatScope()->find($sessionId);
        if (! $session) {
            return $this->sendError(404, 'NOT_FOUND', 'Chat session not found');
        }

        $userMessage = $request->input('message');
        $files = $request->file('attachments');
        if ($files && ! is_array($files)) {
            $files = [$files];
        }
        $files = $files ?? [];

        $imageBase64Urls = [];
        $documentTexts = [];
        foreach ($files as $file) {
            if (! $this->chatAttachment->isSupported($file)) {
                continue;
            }
            if ($this->chatAttachment->isImage($file)) {
                $b = $this->chatAttachment->getImageAsBase64($file);
                if ($b) {
                    $imageBase64Urls[] = $b;
                }
            } else {
                $t = $this->chatAttachment->extractTextFromDocument($file);
                if ($t) {
                    $documentTexts[] = "[{$file->getClientOriginalName()}]\n{$t}";
                }
            }
        }

        $documentText = ! empty($documentTexts) ? implode("\n\n", $documentTexts) : null;
        if ($documentText && mb_strlen($documentText) > 50000) {
            $documentText = mb_substr($documentText, 0, 50000)."\n\n[... dokumen dipendekkan ...]";
        }

        $userMsg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);
        if ($session->messages()->count() === 1) {
            $session->update(['title' => mb_substr($userMessage, 0, 60)]);
        }
        broadcast(new ChatMessageSent($userMsg));

        try {
            $response = $this->ainaChatService->sendMessage(
                $session,
                $userMessage,
                $imageBase64Urls,
                $documentText,
            );
        } catch (\Throwable $e) {
            Log::error('UserChat sendMessage exception', ['message' => $e->getMessage()]);

            return $this->sendError(500, 'INTERNAL_ERROR', $this->normalizeChatAiError($e->getMessage()));
        }

        if (isset($response['error'])) {
            return $this->sendError(500, 'INTERNAL_ERROR', $this->normalizeChatAiError($response['error']));
        }

        $assistantMessage = ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => 'assistant',
            'content' => $response['content'],
            'citations' => $response['citations'] ?? [],
        ]);
        broadcast(new ChatMessageSent($assistantMessage));

        return $this->sendOk($assistantMessage);
    }

    public function updateUserChatSession(UpdateUserChatSessionRequest $request, int $sessionId): JsonResponse
    {
        $session = $this->userChatScope()->find($sessionId);
        if (! $session) {
            return $this->sendError(404, 'NOT_FOUND', 'Chat session not found');
        }

        $data = $request->validated();
        $updates = [];
        if (array_key_exists('module_filter', $data)) {
            $updates['module_filter'] = $data['module_filter'];
        }
        if (! empty($updates)) {
            $session->update($updates);
        }

        return $this->sendOk($session->fresh()->toArray());
    }

    public function deleteUserChatSession(int $sessionId): JsonResponse
    {
        $this->userChatScope()->where('id', $sessionId)->delete();

        return $this->sendOk(['success' => true]);
    }

    public function toggleUserChatSessionFavorite(int $sessionId): JsonResponse
    {
        $session = $this->userChatScope()->find($sessionId);
        if (! $session) {
            return $this->sendError(404, 'NOT_FOUND', 'Chat session not found');
        }

        $fav = ChatSessionFavorite::firstOrCreate([
            'user_id' => Auth::id(),
            'chat_session_id' => $sessionId,
        ]);
        if (! $fav->wasRecentlyCreated) {
            $fav->delete();

            return $this->sendOk(['favorited' => false]);
        }

        return $this->sendOk(['favorited' => true]);
    }

    public function searchUserChatMessages(Request $request, int $sessionId): JsonResponse
    {
        $session = $this->userChatScope()->find($sessionId);
        if (! $session) {
            return $this->sendError(404, 'NOT_FOUND', 'Sesi tidak dijumpai');
        }

        $q = $request->input('q');
        if (empty($q) || strlen($q) < 2) {
            return $this->sendOk([], ['count' => 0]);
        }

        $messages = ChatMessage::where('chat_session_id', $sessionId)
            ->where('content', 'like', '%'.addcslashes($q, '%_').'%')
            ->orderBy('created_at', 'desc')->limit(50)->get();

        return $this->sendOk($messages, ['count' => $messages->count()]);
    }

    public function userChatFavorites(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 5), 20);
        $page = (int) $request->input('page', 1);
        $sessionIds = $this->userChatScope()->pluck('id');
        $query = ChatMessageFavorite::with(['chatMessage.session'])
            ->where('user_id', Auth::id())
            ->whereHas('chatMessage', fn ($q) => $q->whereIn('chat_session_id', $sessionIds))
            ->orderBy('created_at', 'desc');
        $total = $query->count();
        $rows = $query->skip(($page - 1) * $limit)->take($limit)->get();
        $items = $rows->map(fn ($f) => [
            'id' => $f->id,
            'message' => $f->chatMessage,
            'session' => $f->chatMessage?->session,
            'created_at' => $f->created_at?->toIso8601String(),
        ]);

        return $this->sendOk($items, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }

    public function toggleUserChatMessageFavorite(int $messageId): JsonResponse
    {
        $msg = ChatMessage::find($messageId);
        if (! $msg) {
            return $this->sendError(404, 'NOT_FOUND', 'Mesej tidak dijumpai');
        }
        $session = $this->userChatScope()->find($msg->chat_session_id);
        if (! $session) {
            return $this->sendError(403, 'FORBIDDEN', 'Tiada akses ke sesi ini');
        }

        $fav = ChatMessageFavorite::firstOrCreate([
            'user_id' => Auth::id(),
            'chat_message_id' => $messageId,
        ]);
        if (! $fav->wasRecentlyCreated) {
            $fav->delete();

            return $this->sendOk(['favorited' => false]);
        }

        return $this->sendOk(['favorited' => true]);
    }

    public function userChatSuggestions(): JsonResponse
    {
        return $this->sendOk($this->chatSuggestionsList());
    }

    /** @return list<array{id: string, label: string, module: string}> */
    private function chatSuggestionsList(): array
    {
        return [
            ['id' => 'permohonan-baru', 'label' => 'Macam mana nak daftar permohonan baru?', 'module' => 'Permohonan'],
            ['id' => 'penilaian', 'label' => 'Cara proses penilaian dan kelulusan?', 'module' => 'Pembiayaan'],
            ['id' => 'pengeluaran-dana', 'label' => 'Macam mana nak proses pengeluaran dana?', 'module' => 'Pembiayaan'],
            ['id' => 'terima-bayaran', 'label' => 'Cara terima bayaran pembiayaan?', 'module' => 'Pembayaran'],
            ['id' => 'tetapan', 'label' => 'Macam mana nak kemas kini tetapan SPPT?', 'module' => 'Tetapan (Setup)'],
        ];
    }

    private function normalizeChatAiError(string $raw): string
    {
        $lower = strtolower($raw);
        if (
            str_contains($lower, 'credit balance')
            || str_contains($lower, 'insufficient_quota')
            || str_contains($lower, 'exceeded your current quota')
            || str_contains($lower, 'check your plan and billing')
        ) {
            return 'Had kredit API Anthropic telah habis. Sila tambah kredit di dashboard Anthropic atau hubungi pentadbir sistem.';
        }
        if (
            str_contains($lower, 'rate_limit')
            || str_contains($lower, 'too many requests')
            || str_contains($lower, 'rate limit')
        ) {
            return 'Terlalu banyak permintaan ke API AI (rate limit). Sila tunggu 2–3 minit dan cuba semula.';
        }
        if (
            str_contains($lower, 'authentication')
            || str_contains($lower, 'invalid api key')
            || str_contains($lower, 'incorrect api key')
            || str_contains($lower, 'invalid x-api-key')
        ) {
            return 'API key Anthropic tidak sah. Sila semak konfigurasi ANTHROPIC_API_KEY.';
        }
        if (str_contains($lower, 'overloaded') || str_contains($lower, 'server had an error') || str_contains($lower, 'something went wrong')) {
            return 'Server AI sibuk. Sila cuba semula dalam beberapa minit.';
        }

        return $raw;
    }
}
