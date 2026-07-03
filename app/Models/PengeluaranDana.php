<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengeluaranDana extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran_dana';

    protected $fillable = [
        'rujukan',
        'akaun_id',
        'id_pembiayaan',
        'nama',
        'jumlah',
        'jenis',
        'fasa',
        'peratus_fasa',
        'bank',
        'no_akaun_bank',
        'status',
        'no_rujukan_bank',
        'fraud_risk',
        'bsas_verified',
        'legal_docs_complete',
        'tarikh_pengeluaran',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'peratus_fasa' => 'decimal:2',
            'bsas_verified' => 'boolean',
            'legal_docs_complete' => 'boolean',
            'tarikh_pengeluaran' => 'date',
        ];
    }

    public function akaun(): BelongsTo
    {
        return $this->belongsTo(AkaunPembiayaan::class, 'akaun_id');
    }
}
