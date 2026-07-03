<?php

namespace App\Http\Requests;

class UpdateUsahawanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('usahawan');

        return [
            'noUsahawan' => 'nullable|string|max:20|unique:usahawan,no_usahawan,'.$id,
            'nama' => 'sometimes|required|string|min:1',
            'noIc' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'poskod' => 'nullable|string|max:10',
            'negeri' => 'nullable|string|max:100',
            'noTelefon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'jenisPerniagaan' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
        ];
    }
}
