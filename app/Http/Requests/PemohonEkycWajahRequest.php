<?php

namespace App\Http\Requests;

class PemohonEkycWajahRequest extends VerifyPenilaianPantasWajahRequest
{
    public function rules(): array
    {
        return parent::rules() + ['access_token' => 'required|string|max:80'];
    }
}
