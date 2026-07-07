<?php

namespace App\Http\Requests;

class StoreSpptCawanganRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:100|unique:sppt_cawangan,code',
            'name' => 'required|string|max:500',
            'branch_type' => 'nullable|string|in:negeri,cawangan,ibu_pejabat',
            'negeri' => 'nullable|string|max:100',
            'locality' => 'nullable|string|max:150',
            'postal_code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:200',
            'fax' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:200',
            'external_id' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
