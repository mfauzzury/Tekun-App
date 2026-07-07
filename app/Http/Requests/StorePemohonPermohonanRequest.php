<?php

namespace App\Http\Requests;

class StorePemohonPermohonanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isDraft = $this->input('status') === 'Draf';

        return [
            'nama' => ($isDraft ? 'nullable' : 'required').'|string|min:1',
            'kategori_pembiayaan' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50|in:Draf,Dalam Semakan',
            'jumlah_permohonan' => 'nullable|numeric|min:0',
            'pemohon_email' => 'nullable|email|max:255',
            'pemohon_telefon' => 'nullable|string|max:20',
            'details' => 'nullable|array',
        ];
    }
}
