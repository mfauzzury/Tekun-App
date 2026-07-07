<?php

namespace App\Http\Requests;

class UpdateWfProcessDetailRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wpd_status_code' => 'required|string|max:50',
            'wpd_status_desc' => 'required|string|max:255',
            'wpd_reroute_process' => 'nullable|integer|exists:wf_process,wfp_process_id',
            'wpd_reroute_url' => 'nullable|string|max:500',
            'wpd_order' => 'required|integer|min:0',
        ];
    }
}
