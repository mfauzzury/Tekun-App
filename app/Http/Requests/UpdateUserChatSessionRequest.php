<?php

namespace App\Http\Requests;

class UpdateUserChatSessionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_filter' => 'nullable|string|max:100',
        ];
    }
}
