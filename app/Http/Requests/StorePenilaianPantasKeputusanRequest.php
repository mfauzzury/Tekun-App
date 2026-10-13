<?php

namespace App\Http\Requests;

class StorePenilaianPantasKeputusanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keputusan' => 'required|in:lulus,tolak,rujuk',
            'jumlah_diluluskan' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:2000|required_if:keputusan,tolak|required_if:keputusan,rujuk',
            'akui_amaran' => 'nullable|boolean',
        ];
    }
}
