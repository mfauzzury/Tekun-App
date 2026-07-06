<?php

namespace App\Http\Requests;

class VerifyOtpRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => 'required|in:sms,email',
            'telefon' => 'required_if:channel,sms|nullable|string|min:7|max:20',
            'email' => 'required_if:channel,email|nullable|email',
            'code' => 'required|string|size:6',
        ];
    }
}
