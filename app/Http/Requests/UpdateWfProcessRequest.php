<?php

namespace App\Http\Requests;

class UpdateWfProcessRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wfp_process_name' => 'sometimes|required|string|max:255',
            'wfp_process_desc_bm' => 'nullable|string',
            'wfp_sequence' => 'sometimes|required|integer|min:0',
            'wfp_duration_kpi' => 'nullable|integer|min:0',
            'wfp_duration_kpi_withquery' => 'nullable',
            'wfp_status' => 'nullable|string|max:5',
            'wfp_is_email_notification' => 'nullable|integer',
            'wfp_is_todo_notification' => 'nullable|integer',
            'wfp_is_by_unit' => 'nullable|integer',
            'wfp_is_by_ptj' => 'nullable|integer',
            'wfp_is_allow_query' => 'nullable|integer',
        ];
    }
}
