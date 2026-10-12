<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermohonanPenjadualan extends Model
{
    use HasFactory;

    protected $table = 'permohonan_penjadualan';

    protected $fillable = [
        'rujukan',
        'akaun_id',
        'jenis',
        'status',
        'tempoh_bulan',
        'tarikh_mula',
        'tarikh_kuat_kuasa',
        'bayaran_baharu',
        'sebab',
        'catatan_pegawai',
        'diluluskan_oleh',
        'tarikh_kelulusan',
    ];

    protected function casts(): array
    {
        return [
            'tarikh_mula' => 'date',
            'tarikh_kuat_kuasa' => 'date',
            'bayaran_baharu' => 'decimal:2',
            'tarikh_kelulusan' => 'datetime',
        ];
    }

    public function akaun(): BelongsTo
    {
        return $this->belongsTo(AkaunPembiayaan::class, 'akaun_id');
    }

    public function diluluskanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diluluskan_oleh');
    }
}
