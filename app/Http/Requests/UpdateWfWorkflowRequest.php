<?php

namespace App\Http\Requests;

class UpdateWfWorkflowRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wfa_workflow_title' => 'required|string|max:255',
            'wfa_prevent_self_process' => 'nullable|integer',
            'wfa_involve_posting' => 'nullable|integer',
        ];
    }
}
