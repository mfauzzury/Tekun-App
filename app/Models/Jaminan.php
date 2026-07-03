<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jaminan extends Model
{
    use HasFactory;

    protected $table = 'jaminan';

    protected $fillable = [
        'rujukan',
        'nama',
        'jenis',
        'nilai',
        'status',
        'risiko',
        'no_pinjaman',
        'tarikh_mula',
        'tarikh_tamat',
        'dokumen',
    ];

    protected function casts(): array
    {
        return [
            'nilai' => 'decimal:2',
            'tarikh_mula' => 'date',
            'tarikh_tamat' => 'date',
        ];
    }
}
