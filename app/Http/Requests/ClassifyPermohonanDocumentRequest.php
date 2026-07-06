<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPermohonanDocumentUpload;

class ClassifyPermohonanDocumentRequest extends BaseFormRequest
{
    use ValidatesPermohonanDocumentUpload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => $this->permohonanDocumentFileRule(),
            'applicant_ic' => 'nullable|string|max:20',
            'applicant_name' => 'nullable|string|max:255',
            'spouse_ic' => 'nullable|string|max:20',
            'spouse_name' => 'nullable|string|max:255',
        ];
    }
}
