<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculatePembiayaanFormulaRequest;
use App\Http\Traits\ApiResponse;
use App\Services\PembiayaanFormulaService;
use Illuminate\Http\JsonResponse;

class PembiayaanFormulaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PembiayaanFormulaService $formulaService,
    ) {}

    public function defaults(): JsonResponse
    {
        return $this->sendOk(config('sppt-repayment.formula', []));
    }

    public function calculate(CalculatePembiayaanFormulaRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->sendOk($this->formulaService->compare(
            (float) $data['jumlah_pokok'],
            (float) $data['kadar_keuntungan_tahunan'],
            (int) $data['tempoh_bulan'],
        ));
    }
}
