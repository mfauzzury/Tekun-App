<?php

namespace App\Http\Requests;

use App\Services\AiProviderService;

class TestAiProviderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $models = implode(',', array_keys(AiProviderService::MODELS));

        return [
            'api_key' => 'nullable|string|min:20|max:255',
            'model' => "nullable|string|in:{$models}",
        ];
    }
}
