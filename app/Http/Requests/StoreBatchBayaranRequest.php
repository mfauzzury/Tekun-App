<?php

namespace App\Http\Requests;

class StoreBatchBayaranRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'saluran' => 'required|in:fpx,jompay,kaunter,auto_debit,ewallet',
            'rows' => 'required|array|min:1|max:500',
            'rows.*.no_akaun' => 'required|string|max:30',
            'rows.*.jumlah' => 'required|numeric|min:0.01',
            'rows.*.no_rujukan_luar' => 'nullable|string|max:80',
        ];
    }
}
