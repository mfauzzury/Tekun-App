<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotisKutipan extends Model
{
    use Auditable, HasFactory;

    protected $table = 'notis_kutipan';

    protected $fillable = [
        'rujukan',
        'akaun_id',
        'tahap',
        'jenis',
        'status',
        'hari_lewat',
        'jumlah_tunggakan',
        'mesej',
        'tarikh_dijadual',
        'tarikh_dihantar',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'hari_lewat' => 'integer',
            'jumlah_tunggakan' => 'decimal:2',
            'tarikh_dijadual' => 'datetime',
            'tarikh_dihantar' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function akaun(): BelongsTo
    {
        return $this->belongsTo(AkaunPembiayaan::class, 'akaun_id');
    }
}
