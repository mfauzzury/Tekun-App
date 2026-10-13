<?php

namespace App\Http\Requests;

use App\Services\AiProviderService;

class UpdateAiProviderRequest extends BaseFormRequest
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
            'clear_api_key' => 'nullable|boolean',
            'model' => "required|string|in:{$models}",
            'chat_model' => "required|string|in:{$models}",
            'timeout' => 'required|integer|min:10|max:600',
        ];
    }
}
