<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPermohonanDuplicates;
use Illuminate\Contracts\Validation\Validator;

class UpdatePermohonanRequest extends BaseFormRequest
{
    use ValidatesPermohonanDuplicates;

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
            'status' => 'nullable|string|max:50|in:Draf,Dalam Proses,Menunggu Dokumen,Lengkap,Ditolak',
            'jumlah_permohonan' => 'nullable|numeric|min:0',
            'tarikh_permohonan' => 'nullable|date',
            'details' => 'nullable|array',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $excludeId = $this->route('permohonan');
            $this->validatePermohonanDuplicates(
                $validator,
                is_numeric($excludeId) ? (int) $excludeId : null,
            );
        });
    }
}
