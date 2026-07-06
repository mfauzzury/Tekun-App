<?php

namespace App\Http\Requests;

class UpdatePermohonanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('permohonan');

        $isDraft = $this->input('status') === 'Draf';

        return [
            'no_rujukan' => 'nullable|string|max:30|unique:permohonan,no_rujukan,'.$id,
            'usahawan_id' => 'nullable|integer|exists:usahawan,id',
            'nama' => ($isDraft ? 'nullable' : 'sometimes|required').'|string|min:1',
            'kategori_pembiayaan' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50|in:Draf,Dalam Proses,Menunggu Dokumen,Lengkap',
            'jumlah_permohonan' => 'nullable|numeric|min:0',
            'tarikh_permohonan' => 'nullable|date',
            'details' => 'nullable|array',
        ];
    }
}
