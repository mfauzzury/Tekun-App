<?php

namespace App\Http\Requests;

class StoreBayaranRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'akaun_id' => 'required|integer|exists:akaun_pembiayaan,id',
            'jumlah' => 'required|numeric|min:0.01',
            'saluran' => 'required|in:fpx,jompay,kaunter,auto_debit,ewallet',
            'no_rujukan_luar' => 'nullable|string|max:80',
            'tarikh_bayaran' => 'nullable|date',
        ];
    }
}
