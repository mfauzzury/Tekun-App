<?php

namespace App\Http\Requests;

use App\Services\AiProviderService;

class SetActiveAiProviderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => 'required|string|in:'.implode(',', array_keys(AiProviderService::PROVIDERS)),
        ];
    }
}
