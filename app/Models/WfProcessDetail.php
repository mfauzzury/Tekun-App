<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfProcessDetail extends Model
{
    use HasFactory, HasLegacyAuditColumns;

    protected $table = 'wf_process_details';

    protected $primaryKey = 'wpd_process_details_id';

    protected $fillable = [
        'wpd_process_id',
        'wpd_status_code',
        'wpd_reroute_process',
        'wpd_order',
        'wpd_extended_field',
    ];

    protected function casts(): array
    {
        return [
            'wpd_extended_field' => 'array',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(WfProcess::class, 'wpd_process_id', 'wfp_process_id');
    }

    public function rerouteProcess(): BelongsTo
    {
        return $this->belongsTo(WfProcess::class, 'wpd_reroute_process', 'wfp_process_id');
    }
}
