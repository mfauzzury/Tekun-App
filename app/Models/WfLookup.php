<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfLookup extends Model
{
    use HasFactory;

    protected $table = 'wf_lookup';

    protected $primaryKey = 'wfl_code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'wfl_code',
        'wfl_desc',
        'wfl_isPositive',
        'wfl_order',
    ];
}
