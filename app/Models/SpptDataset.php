<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpptDataset extends Model
{
    use HasFactory;

    protected $table = 'sppt_datasets';

    protected $fillable = [
        'module',
        'dataset_key',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
