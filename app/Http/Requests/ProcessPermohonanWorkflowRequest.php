<?php

namespace App\Http\Requests;

use App\Services\PermohonanWorkflowService;

class ProcessPermohonanWorkflowRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $stages = implode(',', PermohonanWorkflowService::stages());

        return [
            'stage' => 'required|string|in:'.$stages,
            'keputusan' => 'required|string|in:lulus,tolak',
            'catatan' => 'nullable|string|max:2000',
        ];
    }
}
