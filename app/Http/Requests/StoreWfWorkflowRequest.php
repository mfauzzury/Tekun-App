<?php

namespace App\Http\Requests;

class StoreWfWorkflowRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wfa_workflow_code' => 'required|string|max:20|unique:wf_workflow_name,wfa_workflow_code',
            'wfa_workflow_title' => 'required|string|max:255',
            'wfa_prevent_self_process' => 'nullable|integer',
            'wfa_involve_posting' => 'nullable|integer',
        ];
    }
}
