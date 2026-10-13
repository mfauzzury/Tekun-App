<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestAiProviderRequest;
use App\Http\Requests\UpdateAiProviderRequest;
use App\Http\Traits\ApiResponse;
use App\Services\AiProviderService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

class AiProviderController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AiProviderService $aiProviderService,
        protected AuditService $auditService,
    ) {}

    public function show(): JsonResponse
    {
        return $this->sendOk($this->aiProviderService->status());
    }

    public function update(UpdateAiProviderRequest $request): JsonResponse
    {
        $before = $this->aiProviderService->status();
        $changed = $this->aiProviderService->update($request->validated());
        $after = $this->aiProviderService->status();

        // Never log the key itself — only that it changed and its masked hint.
        $this->auditService->log(
            'ai_provider.updated',
            $request->user(),
            'AiProvider',
            null,
            array_intersect_key($before, array_flip(['key_source', 'key_hint', 'model', 'chat_model', 'timeout'])),
            array_intersect_key($after, array_flip(['key_source', 'key_hint', 'model', 'chat_model', 'timeout'])) + ['changed' => $changed],
        );

        return $this->sendOk($after);
    }

    public function test(TestAiProviderRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->sendOk($this->aiProviderService->testConnection(
            $data['api_key'] ?? null,
            $data['model'] ?? null,
        ));
    }
}
