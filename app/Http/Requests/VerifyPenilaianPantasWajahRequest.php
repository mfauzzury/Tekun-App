<?php

namespace App\Http\Requests;

class VerifyPenilaianPantasWajahRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'frames' => 'required|array|min:1|max:4',
            'frames.*.base64' => 'required|string|max:4000000',
            'frames.*.mime' => 'required|in:image/jpeg,image/png,image/webp',
            'frames.*.arahan' => 'required|string|max:120',
        ];
    }
}
