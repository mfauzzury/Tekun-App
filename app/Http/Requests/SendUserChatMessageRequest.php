<?php

namespace App\Http\Requests;

class SendUserChatMessageRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|min:1|max:2000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,webp,gif|max:4096',
        ];
    }
}
