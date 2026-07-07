<?php

namespace App\Http\Requests;

class UpdateHardRulesSetupRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'active' => 'required|boolean',
            'rules' => 'required|array|min:1',
            'rules.*.code' => 'required|string|max:50',
            'rules.*.label' => 'required|string|max:500',
            'rules.*.active' => 'nullable|boolean',
            'rules.*.sort' => 'nullable|integer|min:0',
            'rules.*.config' => 'nullable|array',
            'rules.*.config.min_age' => 'nullable|integer|min:1|max:120',
            'rules.*.config.max_age' => 'nullable|integer|min:1|max:120',
            'rules.*.config.ics' => 'nullable|array',
            'rules.*.config.ics.*' => 'string|max:20',
            'rules.*.config.max_ratio' => 'nullable|numeric|min:0|max:1',
            'rules.*.config.max_active_count' => 'nullable|integer|min:0',
            'rules.*.config.max_total_amount' => 'nullable|numeric|min:0',
        ];
    }
}
