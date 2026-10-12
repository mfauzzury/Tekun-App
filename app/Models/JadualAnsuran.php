<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadualAnsuran extends Model
{
    use HasFactory;

    protected $table = 'jadual_ansuran';

    protected $fillable = [
        'akaun_id',
        'no_ansuran',
        'tarikh_bayaran',
        'jumlah_ansuran',
        'pokok',
        'keuntungan',
        'baki',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tarikh_bayaran' => 'date',
            'jumlah_ansuran' => 'decimal:2',
            'pokok' => 'decimal:2',
            'keuntungan' => 'decimal:2',
            'baki' => 'decimal:2',
        ];
    }

    public function akaun(): BelongsTo
    {
        return $this->belongsTo(AkaunPembiayaan::class, 'akaun_id');
    }
}
