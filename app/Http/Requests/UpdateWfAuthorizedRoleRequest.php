<?php

namespace App\Http\Requests;

class UpdateWfAuthorizedRoleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'war_group_code' => 'required|string|max:100|exists:roles,name',
            'war_limit_min' => 'nullable|numeric',
            'war_limit_max' => 'nullable|numeric',
        ];
    }
}
