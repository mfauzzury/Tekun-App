<?php

namespace App\Http\Requests;

use App\Services\DocumentClassificationService;

class UpdatePermohonanDocumentClassRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = implode(',', DocumentClassificationService::allowedClasses());

        return [
            'document_class' => "required|string|in:{$allowed}",
            'document_class_other' => 'nullable|required_if:document_class,lain_lain|string|max:255',
        ];
    }
}
