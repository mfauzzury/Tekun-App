<?php

namespace App\Http\Requests;

class CalculatePembiayaanFormulaRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jumlah_pokok' => 'required|numeric|min:1000|max:1000000',
            'kadar_keuntungan_tahunan' => 'nullable|numeric|min:0|max:30',
            'tempoh_bulan' => 'nullable|integer|min:1|max:360',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $defaults = config('sppt-repayment.formula', []);

        $data['kadar_keuntungan_tahunan'] = (float) ($data['kadar_keuntungan_tahunan']
            ?? $defaults['default_kadar_keuntungan_tahunan']
            ?? 4);
        $data['tempoh_bulan'] = (int) ($data['tempoh_bulan']
            ?? $defaults['default_tempoh_bulan']
            ?? 36);

        return $data;
    }
}
