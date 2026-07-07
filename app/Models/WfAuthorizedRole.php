<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfAuthorizedRole extends Model
{
    use HasFactory, HasLegacyAuditColumns;

    protected $table = 'wf_authorized_role';

    protected $primaryKey = 'war_authorized_role_id';

    protected $fillable = [
        'war_process_id',
        'war_group_code',
        'war_limit_min',
        'war_limit_max',
    ];

    protected function casts(): array
    {
        return [
            'war_limit_min' => 'decimal:2',
            'war_limit_max' => 'decimal:2',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(WfProcess::class, 'war_process_id', 'wfp_process_id');
    }
}
