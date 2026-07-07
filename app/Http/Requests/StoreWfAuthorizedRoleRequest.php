<?php

namespace App\Http\Requests;

class StoreWfAuthorizedRoleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'war_process_id' => 'required|integer|exists:wf_process,wfp_process_id',
            'war_group_code' => 'required|string|max:100|exists:roles,name',
            'war_limit_min' => 'nullable|numeric',
            'war_limit_max' => 'nullable|numeric',
        ];
    }
}
