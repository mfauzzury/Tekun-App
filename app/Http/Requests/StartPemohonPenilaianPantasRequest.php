<?php

namespace App\Http\Requests;

class StartPemohonPenilaianPantasRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'access_token' => 'required|string|max:80',
        ];
    }
}
