<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AkaunPembiayaan extends Model
{
    use HasFactory;

    protected $table = 'akaun_pembiayaan';

    protected $fillable = [
        'no_akaun',
        'permohonan_id',
        'usahawan_id',
        'ic',
        'nama',
        'nama_syarikat',
        'ssm',
        'pukonsa',
        'cawangan',
        'negeri',
        'produk',
        'tarikh_mula',
        'tarikh_tamat',
        'jumlah_pembiayaan',
        'baki_pokok',
        'baki_keuntungan',
        'baki_simpanan',
        'penalti',
        'tunggakan',
        'baki_akhir',
        'bayaran_bulanan',
        'status',
        'risiko',
        'no_bsas',
        'snc',
    ];

    protected function casts(): array
    {
        return [
            'tarikh_mula' => 'date',
            'tarikh_tamat' => 'date',
            'jumlah_pembiayaan' => 'decimal:2',
            'baki_pokok' => 'decimal:2',
            'baki_keuntungan' => 'decimal:2',
            'baki_simpanan' => 'decimal:2',
            'penalti' => 'decimal:2',
            'tunggakan' => 'decimal:2',
            'baki_akhir' => 'decimal:2',
            'bayaran_bulanan' => 'decimal:2',
            'snc' => 'boolean',
        ];
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function usahawan(): BelongsTo
    {
        return $this->belongsTo(Usahawan::class);
    }

    public function pengeluaranDana(): HasMany
    {
        return $this->hasMany(PengeluaranDana::class, 'akaun_id');
    }
}
