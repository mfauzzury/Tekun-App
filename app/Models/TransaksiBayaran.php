<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiBayaran extends Model
{
    use HasFactory;

    protected $table = 'transaksi_bayaran';

    protected $fillable = [
        'rujukan',
        'akaun_id',
        'batch_id',
        'jumlah',
        'kepada_tawidh',
        'kepada_tunggakan',
        'kepada_ansuran',
        'saluran',
        'mod',
        'status',
        'no_rujukan_luar',
        'tarikh_bayaran',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'kepada_tawidh' => 'decimal:2',
            'kepada_tunggakan' => 'decimal:2',
            'kepada_ansuran' => 'decimal:2',
            'tarikh_bayaran' => 'datetime',
        ];
    }

    public function akaun(): BelongsTo
    {
        return $this->belongsTo(AkaunPembiayaan::class, 'akaun_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchBayaran::class, 'batch_id');
    }
}
