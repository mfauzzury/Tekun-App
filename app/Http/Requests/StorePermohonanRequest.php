<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPermohonanDuplicates;
use Illuminate\Contracts\Validation\Validator;

class StorePermohonanRequest extends BaseFormRequest
{
    use ValidatesPermohonanDuplicates;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isDraft = $this->input('status') === 'Draf';

        return [
            'no_rujukan' => 'nullable|string|max:30|unique:permohonan,no_rujukan',
            'usahawan_id' => 'nullable|integer|exists:usahawan,id',
            'nama' => ($isDraft ? 'nullable' : 'required').'|string|min:1',
            'kategori_pembiayaan' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50|in:Draf,Dalam Proses,Menunggu Dokumen,Lengkap,Ditolak',
            'jumlah_permohonan' => 'nullable|numeric|min:0',
            'tarikh_permohonan' => 'nullable|date',
            'details' => 'nullable|array',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validatePermohonanDuplicates($validator);
        });
    }
}
