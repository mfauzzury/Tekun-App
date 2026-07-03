<?php

namespace App\Http\Controllers\Api\Sppt;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SpptReferenceDataController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->sendOk(config('sppt-reference-data'));
    }
}
