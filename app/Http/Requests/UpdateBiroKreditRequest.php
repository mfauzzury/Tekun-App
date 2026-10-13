<?php

namespace App\Http\Requests;

class UpdateBiroKreditRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tersambung' => 'required|boolean',
        ];
    }
}
