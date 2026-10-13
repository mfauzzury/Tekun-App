<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetActiveAiProviderRequest;
use App\Http\Requests\TestAiProviderRequest;
use App\Http\Requests\UpdateAiProviderRequest;
use App\Http\Traits\ApiResponse;
use App\Services\AiProviderService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function update(UpdateAiProviderRequest $request, string $provider): JsonResponse
    {
        $before = $this->aiProviderService->status()['penyedia'][$provider];
        $changed = $this->aiProviderService->update($provider, $request->validated());
        $after = $this->aiProviderService->status();

        // Never log key values — only that they changed and the masked hint.
        $fields = array_flip(['key_source', 'key_hint', 'model', 'chat_model', 'vision_model', 'base_url', 'vision']);
        $this->auditService->log(
            'ai_provider.updated',
            $request->user(),
            'AiProvider',
            null,
            ['provider' => $provider] + array_intersect_key($before, $fields),
            ['provider' => $provider] + array_intersect_key($after['penyedia'][$provider], $fields) + ['changed' => $changed],
        );

        return $this->sendOk($after);
    }

    public function setActive(SetActiveAiProviderRequest $request): JsonResponse
    {
        $provider = $request->validated()['provider'];
        $untuk = $request->validated()['untuk'] ?? 'teks';
        $field = $untuk === 'imej' ? 'aktif_imej' : 'aktif';
        $previous = $this->aiProviderService->status()[$field];

        $error = $this->aiProviderService->setActive($provider, $untuk);
        if ($error !== null) {
            return $this->sendError(422, 'VALIDATION_ERROR', $error, ['provider' => [$error]]);
        }

        $this->auditService->log('ai_provider.activated', $request->user(), 'AiProvider', null, [$field => $previous], [$field => $provider]);

        return $this->sendOk($this->aiProviderService->status());
    }

    public function test(TestAiProviderRequest $request, string $provider): JsonResponse
    {
        $data = $request->validated();

        return $this->sendOk($this->aiProviderService->testConnection(
            $provider,
            $data['api_key'] ?? null,
            $data['model'] ?? null,
            $data['base_url'] ?? null,
        ));
    }
}
