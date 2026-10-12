<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchBayaran extends Model
{
    use HasFactory;

    protected $table = 'batch_bayaran';

    protected $fillable = [
        'rujukan',
        'saluran',
        'status',
        'jumlah_rekod',
        'jumlah_berjaya',
        'jumlah_gagal',
        'jumlah_keseluruhan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_keseluruhan' => 'decimal:2',
        ];
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(TransaksiBayaran::class, 'batch_id');
    }
}
