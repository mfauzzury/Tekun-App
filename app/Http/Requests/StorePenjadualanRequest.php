<?php

namespace App\Http\Requests;

class StorePenjadualanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'akaun_id' => 'required|integer|exists:akaun_pembiayaan,id',
            'jenis' => 'required|in:moratorium,penjadualan_semula,penstrukturan',
            'tempoh_bulan' => 'nullable|integer|min:1|max:120',
            'tarikh_mula' => 'nullable|date',
            'tarikh_kuat_kuasa' => 'nullable|date',
            'bayaran_baharu' => 'nullable|numeric|min:0',
            'sebab' => 'nullable|string|max:2000',
        ];
    }
}
