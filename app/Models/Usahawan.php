<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Usahawan extends Model
{
    use HasFactory;

    protected $table = 'usahawan';

    protected $fillable = [
        'no_usahawan',
        'nama',
        'no_ic',
        'alamat',
        'poskod',
        'negeri',
        'no_telefon',
        'email',
        'jenis_perniagaan',
        'status',
    ];

    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class);
    }

    public function akaunPembiayaan(): HasMany
    {
        return $this->hasMany(AkaunPembiayaan::class);
    }
}
