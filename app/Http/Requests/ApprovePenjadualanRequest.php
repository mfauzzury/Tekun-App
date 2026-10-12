<?php

namespace App\Http\Requests;

class ApprovePenjadualanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tindakan' => 'required|in:lulus,tolak',
            'catatan_pegawai' => 'nullable|string|max:2000',
        ];
    }
}
