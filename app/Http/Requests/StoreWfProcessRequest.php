<?php

namespace App\Http\Requests;

class StoreWfProcessRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wfp_workflow_code' => 'required|string|exists:wf_workflow_name,wfa_workflow_code',
            'wfp_process_name' => 'required|string|max:255',
            'wfp_process_desc_bm' => 'nullable|string',
            'wfp_sequence' => 'required|integer|min:0',
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
