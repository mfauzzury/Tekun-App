<?php

namespace App\Http\Requests;

class AnalyzePenilaianPantasDokumenRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,csv,txt|max:10240',
            'jenis' => 'nullable|in:ewallet,duitnow_qr,bil_utiliti,platform',
        ];
    }
}
