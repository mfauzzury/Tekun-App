<?php

namespace App\Http\Requests;

class UpdateDataAlternatifRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ewallet' => 'nullable|array',
            'ewallet.penyedia' => 'nullable|string|max:60',
            'ewallet.purata_masuk_bulanan' => 'nullable|numeric|min:0',
            'ewallet.bilangan_transaksi_bulanan' => 'nullable|integer|min:0',
            'ewallet.bulan_aktif' => 'nullable|integer|min:0|max:120',
            'duitnow_qr' => 'nullable|array',
            'duitnow_qr.purata_jualan_bulanan' => 'nullable|numeric|min:0',
            'duitnow_qr.bilangan_transaksi_bulanan' => 'nullable|integer|min:0',
            'duitnow_qr.bulan_aktif' => 'nullable|integer|min:0|max:120',
            'duitnow_qr.trend_pertumbuhan_peratus' => 'nullable|numeric|min:-100|max:1000',
            'bil_utiliti' => 'nullable|array',
            'bil_utiliti.bulan_dibayar_tepat' => 'nullable|integer|min:0|max:120',
            'bil_utiliti.bulan_dinilai' => 'nullable|integer|min:0|max:120',
            'platform' => 'nullable|array',
            'platform.nama' => 'nullable|string|max:60',
            'platform.purata_jualan_bulanan' => 'nullable|numeric|min:0',
            'platform.penarafan' => 'nullable|numeric|min:0|max:5',
            'platform.bulan_aktif' => 'nullable|integer|min:0|max:120',
            'ccris_tersedia' => 'nullable|boolean',
            'penyata_bank_tersedia' => 'nullable|boolean',
        ];
    }
}
