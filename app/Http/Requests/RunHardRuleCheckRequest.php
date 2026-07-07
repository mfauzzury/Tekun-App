<?php

namespace App\Http\Requests;

class RunHardRuleCheckRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'umur' => 'nullable|integer|min:1|max:120',
            'no_kp' => 'nullable|string|max:20',
            'pendapatan_bulanan' => 'nullable|numeric|min:0',
            'jumlah_komitmen_sedia_ada' => 'nullable|numeric|min:0',
            'jumlah_pembiayaan_aktif' => 'nullable|integer|min:0',
            'jumlah_pembiayaan_aktif_rm' => 'nullable|numeric|min:0',
            'muflis' => 'nullable|boolean',
        ];
    }
}
