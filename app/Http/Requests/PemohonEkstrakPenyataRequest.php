<?php

namespace App\Http\Requests;

class PemohonEkstrakPenyataRequest extends AnalyzePenilaianPantasDokumenRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'jenis' => 'required|in:ewallet,duitnow_qr,bil_utiliti,platform',
            'access_token' => 'required|string|max:80',
        ]);
    }
}
