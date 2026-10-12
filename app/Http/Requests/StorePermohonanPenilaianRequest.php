<?php

namespace App\Http\Requests;

class StorePermohonanPenilaianRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keputusan' => 'required|string|in:Lulus,Tolak,Tangguh',
            'skor_kredit' => 'nullable|string|max:20',
            'cadangan_pembiayaan' => 'nullable|string|max:100',
            'cadangan_jenis_pembiayaan' => 'nullable|string|max:100',
            'catatan' => 'nullable|string|max:5000',
            'kadar_keuntungan_tahunan' => 'nullable|numeric|min:0|max:30',
            'tempoh_pembiayaan' => 'nullable|integer|min:1|max:360',
            'kaedah_pengiraan' => 'nullable|string|in:flat_rate,reducing_balance',
            'buka_akaun' => 'nullable|boolean',
        ];
    }
}
