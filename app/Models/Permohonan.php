<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Permohonan extends Model
{
    use HasFactory;

    protected $table = 'permohonan';

    protected $fillable = [
        'no_rujukan',
        'usahawan_id',
        'nama',
        'kategori_pembiayaan',
        'status',
        'negeri',
        'cawangan',
        'wf_workflow_code',
        'wf_current_process_id',
        'jumlah_permohonan',
        'tarikh_permohonan',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_permohonan' => 'decimal:2',
            'tarikh_permohonan' => 'date',
            'details' => 'array',
        ];
    }

    public function usahawan(): BelongsTo
    {
        return $this->belongsTo(Usahawan::class);
    }

    public function currentWorkflowProcess(): BelongsTo
    {
        return $this->belongsTo(WfProcess::class, 'wf_current_process_id', 'wfp_process_id');
    }
}
