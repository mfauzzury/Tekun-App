<?php

namespace App\Http\Requests;

class PemohonDataAlternatifRequest extends UpdateDataAlternatifRequest
{
    public function rules(): array
    {
        return parent::rules() + ['access_token' => 'required|string|max:80'];
    }
}
