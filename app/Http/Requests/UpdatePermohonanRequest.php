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

        return [
            'noRujukan' => 'nullable|string|max:30|unique:permohonan,no_rujukan,'.$id,
            'usahawanId' => 'nullable|integer|exists:usahawan,id',
            'nama' => 'sometimes|required|string|min:1',
            'kategoriPembiayaan' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'jumlahPermohonan' => 'nullable|numeric|min:0',
            'tarikhPermohonan' => 'nullable|date',
            'details' => 'nullable|array',
        ];
    }
}
