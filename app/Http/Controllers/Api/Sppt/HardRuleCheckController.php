<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunHardRuleCheckRequest;
use App\Http\Traits\ApiResponse;
use App\Services\HardRuleCheckService;
use Illuminate\Http\JsonResponse;

class HardRuleCheckController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected HardRuleCheckService $hardRuleCheck,
    ) {}

    public function show(): JsonResponse
    {
        return $this->sendOk($this->hardRuleCheck->publicSummary());
    }

    public function check(RunHardRuleCheckRequest $request): JsonResponse
    {
        $result = $this->hardRuleCheck->evaluate($request->validated());

        return $this->sendOk($result);
    }
}
