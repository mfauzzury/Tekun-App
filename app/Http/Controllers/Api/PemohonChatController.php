<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PemohonChatRequest;
use App\Http\Traits\ApiResponse;
use App\Services\PemohonChatService;
use Illuminate\Http\JsonResponse;

class PemohonChatController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PemohonChatService $chatService,
    ) {}

    public function send(PemohonChatRequest $request): JsonResponse
    {
        try {
            $reply = $this->chatService->reply(
                $request->validated('message'),
                $request->validated('history') ?? [],
            );
        } catch (\RuntimeException $e) {
            return $this->sendError(502, 'CHAT_FAILED', $e->getMessage());
        }

        return $this->sendOk(['reply' => $reply]);
    }
}
