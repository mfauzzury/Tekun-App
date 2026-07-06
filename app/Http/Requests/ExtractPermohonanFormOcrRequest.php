<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPermohonanDocumentUpload;

class ExtractPermohonanFormOcrRequest extends BaseFormRequest
{
    use ValidatesPermohonanDocumentUpload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $uploaded = $this->file('file');
                    if (! $uploaded instanceof \Illuminate\Http\UploadedFile) {
                        $fail('Fail borang diperlukan.');

                        return;
                    }

                    $ext = strtolower($uploaded->getClientOriginalExtension());
                    if (! in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                        $fail('Format fail tidak disokong untuk AI-OCR. Sila gunakan PDF, JPG, atau PNG.');
                    }
                },
                ...$this->permohonanDocumentFileRule(null, false),
            ],
        ];
    }
}
