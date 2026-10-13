<?php

namespace App\Http\Requests;

class TestAiProviderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'api_key' => 'nullable|string|min:8|max:255',
            'model' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9._:\/@-]+$/'],
            'base_url' => 'nullable|url:http,https|max:255',
        ];
    }
}
