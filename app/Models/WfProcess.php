<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WfProcess extends Model
{
    use HasFactory, HasLegacyAuditColumns;

    protected $table = 'wf_process';

    protected $primaryKey = 'wfp_process_id';

    protected $fillable = [
        'wfp_workflow_code',
        'wfp_process_name',
        'wfp_process_desc_bm',
        'wfp_process_desc_bi',
        'wfp_sequence',
        'wfp_status',
        'wfp_duration_kpi',
        'wfp_duration_kpi_withquery',
        'wfp_is_email_notification',
        'wfp_is_todo_notification',
        'wfp_is_by_unit',
        'wfp_is_by_ptj',
        'wfp_is_allow_query',
        'wfp_extended_field',
    ];

    protected function casts(): array
    {
        return [
            'wfp_extended_field' => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WfWorkflowName::class, 'wfp_workflow_code', 'wfa_workflow_code');
    }

    public function processDetails(): HasMany
    {
        return $this->hasMany(WfProcessDetail::class, 'wpd_process_id', 'wfp_process_id');
    }

    public function authorizedRoles(): HasMany
    {
        return $this->hasMany(WfAuthorizedRole::class, 'war_process_id', 'wfp_process_id');
    }
}
