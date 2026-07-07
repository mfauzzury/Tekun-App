<?php

namespace App\Http\Requests;

class StoreUserChatSessionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'module_filter' => 'nullable|string|max:100',
        ];
    }
}
