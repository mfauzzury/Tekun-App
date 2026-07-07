<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunAiRiskScoringRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Permohonan;
use App\Services\AiRiskScoringService;
use Illuminate\Http\JsonResponse;

class AiRiskScoringController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AiRiskScoringService $riskScoring,
    ) {}

    public function score(RunAiRiskScoringRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['permohonan_id'])) {
            $permohonan = Permohonan::find((int) $validated['permohonan_id']);
            if ($permohonan) {
                $validated = array_merge(
                    $this->riskScoring->inputFromPermohonan($permohonan),
                    $validated,
                );
            }
        }

        return $this->sendOk($this->riskScoring->score($validated));
    }

    public function scorePermohonan(int $permohonanId): JsonResponse
    {
        $permohonan = Permohonan::find($permohonanId);
        if (! $permohonan) {
            return $this->sendError(404, 'NOT_FOUND', 'Permohonan not found');
        }

        $result = $this->riskScoring->score($this->riskScoring->inputFromPermohonan($permohonan));

        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $details['ai_risk_scoring'] = $result;
        $permohonan->update(['details' => $details]);

        return $this->sendOk($result);
    }
}
