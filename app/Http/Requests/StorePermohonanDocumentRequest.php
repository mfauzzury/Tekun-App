<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPermohonanDocumentUpload;

class StorePermohonanDocumentRequest extends BaseFormRequest
{
    use ValidatesPermohonanDocumentUpload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = implode(',', \App\Services\DocumentClassificationService::allowedClasses());

        return [
            'file' => $this->permohonanDocumentFileRule(),
            'document_class' => "nullable|string|in:{$allowed}",
            'document_class_other' => 'nullable|required_if:document_class,lain_lain|string|max:255',
        ];
    }
}
