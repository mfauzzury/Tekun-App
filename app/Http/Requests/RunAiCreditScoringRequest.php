<?php

namespace App\Http\Requests;

class RunAiCreditScoringRequest extends BaseFormRequest
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
            'no_ssm' => 'nullable|string|max:30',
            'kategori_pembiayaan' => 'nullable|string|max:100',
            'sektor_perniagaan' => 'nullable|string|max:100',
            'tempoh_perniagaan_tahun' => 'nullable|integer|min:0|max:100',
            'pendapatan_bulanan' => 'nullable|numeric|min:0',
            'jumlah_komitmen_sedia_ada' => 'nullable|numeric|min:0',
            'jumlah_permohonan' => 'nullable|numeric|min:0',
            'negeri' => 'nullable|string|max:100',
            'muflis' => 'nullable|boolean',
            'permohonan_id' => 'nullable|integer|exists:permohonan,id',
        ];
    }
}
