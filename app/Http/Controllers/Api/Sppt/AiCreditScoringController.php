<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunAiCreditScoringRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Permohonan;
use App\Services\AiCreditScoringService;
use Illuminate\Http\JsonResponse;

class AiCreditScoringController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AiCreditScoringService $creditScoring,
    ) {}

    public function score(RunAiCreditScoringRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['permohonan_id'])) {
            $permohonan = Permohonan::find((int) $validated['permohonan_id']);
            if ($permohonan) {
                $validated = array_merge(
                    $this->creditScoring->inputFromPermohonan($permohonan),
                    $validated,
                );
            }
        }

        return $this->sendOk($this->creditScoring->score($validated));
    }

    public function scorePermohonan(int $permohonanId): JsonResponse
    {
        $permohonan = Permohonan::find($permohonanId);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $result = $this->creditScoring->score($this->creditScoring->inputFromPermohonan($permohonan));

        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $details['ai_credit_scoring'] = $result;
        $permohonan->update(['details' => $details]);

        return $this->sendOk($result);
    }
}
