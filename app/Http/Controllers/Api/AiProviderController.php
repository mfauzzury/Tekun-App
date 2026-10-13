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
        $fields = array_flip(['key_source', 'key_hint', 'model', 'chat_model', 'base_url', 'vision']);
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
        $previous = $this->aiProviderService->status()['aktif'];

        if (! $this->aiProviderService->setActive($provider)) {
            return $this->sendError(422, 'VALIDATION_ERROR', 'Penyedia ini belum dikonfigurasi sepenuhnya.', [
                'provider' => ['Lengkapkan API key / alamat pelayan dan model sebelum mengaktifkan penyedia ini.'],
            ]);
        }

        $this->auditService->log('ai_provider.activated', $request->user(), 'AiProvider', null, ['aktif' => $previous], ['aktif' => $provider]);

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
