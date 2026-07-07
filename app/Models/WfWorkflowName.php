<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyAuditColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WfWorkflowName extends Model
{
    use HasFactory, HasLegacyAuditColumns;

    protected $table = 'wf_workflow_name';

    protected $primaryKey = 'wfa_workflow_code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'wfa_workflow_code',
        'wfa_workflow_title',
        'wfa_prevent_self_process',
        'wfa_involve_posting',
    ];

    public function processes(): HasMany
    {
        return $this->hasMany(WfProcess::class, 'wfp_workflow_code', 'wfa_workflow_code');
    }
}
