<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenilaianPantas extends Model
{
    use HasFactory, Auditable;

    protected $table = 'penilaian_pantas';

    protected $fillable = [
        'permohonan_id',
        'status',
        'laluan',
        'tahap_risiko',
        'skor_kredit',
        'langkah',
        'cadangan',
        'dihantar_pada',
        'mula_pada',
        'siap_pada',
        'jumlah_ms',
        'hujung_ke_hujung_ms',
        'sumber',
        'dimulakan_oleh',
        'keputusan_pegawai',
        'keputusan_oleh',
        'keputusan_pada',
        'catatan_pegawai',
    ];

    protected function casts(): array
    {
        return [
            'langkah' => 'array',
            'cadangan' => 'array',
            'skor_kredit' => 'integer',
            'jumlah_ms' => 'integer',
            'hujung_ke_hujung_ms' => 'integer',
            'dihantar_pada' => 'datetime',
            'mula_pada' => 'datetime',
            'siap_pada' => 'datetime',
            'keputusan_pada' => 'datetime',
        ];
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function pemulai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dimulakan_oleh');
    }

    public function pegawaiKeputusan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'keputusan_oleh');
    }
}
