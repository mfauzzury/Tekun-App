<?php

namespace App\Models;

use App\Http\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpptCawangan extends Model
{
    use Auditable, HasFactory;

    protected $table = 'sppt_cawangan';

    protected $fillable = [
        'code',
        'name',
        'branch_type',
        'negeri',
        'locality',
        'postal_code',
        'address',
        'phone',
        'fax',
        'contact_person',
        'external_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
