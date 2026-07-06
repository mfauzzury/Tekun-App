<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kutipan extends Model
{
    use HasFactory;

    protected $table = 'kutipan';

    protected $fillable = [
        'rujukan',
        'akaun_id',
        'usahawan_id',
        'nama',
        'no_akaun',
        'cawangan',
        'zon',
        'pegawai',
        'tunggakan',
        'tarikh_akhir_bayaran',
        'hari_lewat',
        'maklumat_psat',
        'hasil_kutipan',
        'janji_bayar',
        'status',
        'catatan',
        'tarikh_lawatan',
        'lokasi_gps',
    ];

    protected function casts(): array
    {
        return [
            'tunggakan' => 'decimal:2',
            'tarikh_akhir_bayaran' => 'date',
            'hari_lewat' => 'integer',
            'hasil_kutipan' => 'decimal:2',
            'janji_bayar' => 'date',
            'tarikh_lawatan' => 'datetime',
        ];
    }

    public function akaun(): BelongsTo
    {
        return $this->belongsTo(AkaunPembiayaan::class, 'akaun_id');
    }

    public function usahawan(): BelongsTo
    {
        return $this->belongsTo(Usahawan::class);
    }
}
