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
        $provider = (string) $this->route('provider');
        $claudeModels = implode(',', array_keys(AiProviderService::MODELS));
        $modelPattern = 'regex:/^[A-Za-z0-9._:\/@-]+$/';

        $rules = [
            'api_key' => 'nullable|string|min:8|max:255',
            'clear_api_key' => 'nullable|boolean',
        ];

        return match ($provider) {
            'anthropic' => $rules + [
                'api_key' => 'nullable|string|min:20|max:255',
                'model' => "required|string|in:{$claudeModels}",
                'chat_model' => "required|string|in:{$claudeModels}",
                'vision_model' => "nullable|string|in:{$claudeModels}",
                'timeout' => 'required|integer|min:10|max:600',
            ],
            'onprem' => $rules + [
                'model' => ['required', 'string', 'max:120', $modelPattern],
                'base_url' => 'required|url:http,https|max:255',
                'vision' => 'nullable|boolean',
            ],
            default => $rules + [
                'model' => ['required', 'string', 'max:120', $modelPattern],
            ],
        };
    }
}
