<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Permohonan;
use App\Models\User;
use App\Models\WfProcess;
use App\Models\WfProcessDetail;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class PermohonanWorkflowService
{
    public const STAGE_SEMAKAN = 'semakan';

    public const STAGE_SOKONGAN = 'sokongan';

    public const STAGE_KELULUSAN = 'kelulusan';

    /**
     * @return list<string>
     */
    public static function stages(): array
    {
        return [
            self::STAGE_SEMAKAN,
            self::STAGE_SOKONGAN,
            self::STAGE_KELULUSAN,
        ];
    }

    public function resolveWorkflowCode(Permohonan $permohonan): ?string
    {
        if (filled($permohonan->wf_workflow_code)) {
            return $permohonan->wf_workflow_code;
        }

        $scheme = trim((string) ($permohonan->kategori_pembiayaan ?? ''));
        $map = config('sppt-workflow.scheme_workflows', []);

        return $map[$scheme] ?? config('sppt-workflow.default_workflow_code');
    }

    public function permissionForStage(string $stage): string
    {
        $process = $this->processForStage(
            (string) config('sppt-workflow.default_workflow_code'),
            $stage,
        );

        $permission = is_array($process?->wfp_extended_field)
            ? ($process->wfp_extended_field['permohonan_permission'] ?? null)
            : null;

        if (is_string($permission) && $permission !== '') {
            return $permission;
        }

        return match ($stage) {
            self::STAGE_SEMAKAN => Permission::SPPT_SEMAKAN,
            self::STAGE_SOKONGAN => Permission::SPPT_SOKONGAN,
            self::STAGE_KELULUSAN => Permission::SPPT_KELULUSAN,
            default => throw new InvalidArgumentException('Invalid workflow stage'),
        };
    }

    public function expectedStatusForStage(string $stage, ?string $workflowCode = null): string
    {
        $workflowCode ??= (string) config('sppt-workflow.default_workflow_code');
        $process = $this->processForStage($workflowCode, $stage);

        $status = is_array($process?->wfp_extended_field)
            ? ($process->wfp_extended_field['permohonan_status'] ?? null)
            : null;

        if (is_string($status) && $status !== '') {
            return $status;
        }

        return match ($stage) {
            self::STAGE_SEMAKAN => 'Dalam Proses',
            self::STAGE_SOKONGAN => 'Disemak',
            self::STAGE_KELULUSAN => 'Disokong',
            default => throw new InvalidArgumentException('Invalid workflow stage'),
        };
    }

    public function appliesCawanganScope(string $stage, ?string $workflowCode = null): bool
    {
        $workflowCode ??= (string) config('sppt-workflow.default_workflow_code');
        $process = $this->processForStage($workflowCode, $stage);

        if ($process) {
            return (bool) $process->wfp_is_by_ptj;
        }

        return in_array($stage, [self::STAGE_SEMAKAN, self::STAGE_SOKONGAN], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function initialAssignment(Permohonan $permohonan): ?array
    {
        if (($permohonan->status ?? '') === 'Draf') {
            return null;
        }

        if (filled($permohonan->wf_workflow_code) && filled($permohonan->wf_current_process_id)) {
            return null;
        }

        $workflowCode = $this->resolveWorkflowCode($permohonan);
        if (! $workflowCode) {
            return null;
        }

        $process = $this->processForStage($workflowCode, self::STAGE_SEMAKAN);
        if (! $process) {
            return null;
        }

        return [
            'wf_workflow_code' => $workflowCode,
            'wf_current_process_id' => $process->wfp_process_id,
        ];
    }

    /**
     * @param  Builder<Permohonan>  $query
     * @return Builder<Permohonan>
     */
    public function applyStageFilter(Builder $query, string $stage, User $user): Builder
    {
        $workflowCode = (string) config('sppt-workflow.default_workflow_code');
        $process = $this->processForStage($workflowCode, $stage);
        $legacyStatus = $this->expectedStatusForStage($stage, $workflowCode);

        $query->where(function (Builder $builder) use ($workflowCode, $process, $legacyStatus) {
            if ($process) {
                $builder->where(function (Builder $configured) use ($workflowCode, $process) {
                    $configured->where('wf_workflow_code', $workflowCode)
                        ->where('wf_current_process_id', $process->wfp_process_id);
                });
            }

            $builder->orWhere(function (Builder $legacy) use ($legacyStatus, $process) {
                $legacy->where('status', $legacyStatus);
                if ($process) {
                    $legacy->where(function (Builder $missingWorkflow) {
                        $missingWorkflow->whereNull('wf_workflow_code')
                            ->orWhereNull('wf_current_process_id');
                    });
                }
            });
        });

        if (! $this->appliesCawanganScope($stage, $workflowCode)) {
            return $query;
        }

        $user->loadMissing('cawangan');
        $branchName = $user->cawangan?->name;

        if (! $branchName) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('cawangan', $branchName);
    }

    public function userCanAccessStage(User $user, string $stage): bool
    {
        if (! in_array($stage, self::stages(), true)) {
            return false;
        }

        return $user->hasPermission($this->permissionForStage($stage));
    }

    public function userCanProcess(Permohonan $permohonan, User $user, string $stage): bool
    {
        if (! $this->userCanAccessStage($user, $stage)) {
            return false;
        }

        $workflowCode = $this->resolveWorkflowCode($permohonan) ?? (string) config('sppt-workflow.default_workflow_code');
        $process = $this->processForStage($workflowCode, $stage);

        if ($process) {
            $matchesProcess = $permohonan->wf_workflow_code === $workflowCode
                && (int) $permohonan->wf_current_process_id === (int) $process->wfp_process_id;

            $matchesLegacy = $permohonan->status === $this->expectedStatusForStage($stage, $workflowCode)
                && (! filled($permohonan->wf_current_process_id) || ! filled($permohonan->wf_workflow_code));

            if (! $matchesProcess && ! $matchesLegacy) {
                return false;
            }
        } elseif ($permohonan->status !== $this->expectedStatusForStage($stage, $workflowCode)) {
            return false;
        }

        if (! $this->appliesCawanganScope($stage, $workflowCode)) {
            return true;
        }

        $user->loadMissing('cawangan');

        return $user->cawangan
            && $permohonan->cawangan === $user->cawangan->name;
    }

    /**
     * @return array{status: string, wf_workflow_code?: string, wf_current_process_id?: int|null, details: array<string, mixed>}
     */
    public function buildTransition(Permohonan $permohonan, User $user, string $stage, string $keputusan, ?string $catatan): array
    {
        $workflowCode = $this->resolveWorkflowCode($permohonan) ?? (string) config('sppt-workflow.default_workflow_code');
        $process = $this->processForStage($workflowCode, $stage);

        if ($process) {
            return $this->buildConfiguredTransition($permohonan, $user, $stage, $keputusan, $catatan, $workflowCode, $process);
        }

        return $this->buildLegacyTransition($permohonan, $user, $stage, $keputusan, $catatan);
    }

    public function processForStage(string $workflowCode, string $stage): ?WfProcess
    {
        return WfProcess::query()
            ->where('wfp_workflow_code', $workflowCode)
            ->where('wfp_status', '1')
            ->orderBy('wfp_sequence')
            ->get()
            ->first(function (WfProcess $process) use ($stage) {
                $extended = is_array($process->wfp_extended_field) ? $process->wfp_extended_field : [];

                return ($extended['permohonan_stage'] ?? null) === $stage;
            });
    }

    /**
     * @return array{status: string, wf_workflow_code?: string, wf_current_process_id?: int|null, details: array<string, mixed>}
     */
    private function buildConfiguredTransition(
        Permohonan $permohonan,
        User $user,
        string $stage,
        string $keputusan,
        ?string $catatan,
        string $workflowCode,
        WfProcess $process,
    ): array {
        $statusCode = $keputusan === 'lulus' ? 'APPROVE' : 'REJECT';

        $detail = WfProcessDetail::query()
            ->where('wpd_process_id', $process->wfp_process_id)
            ->where('wpd_status_code', $statusCode)
            ->orderBy('wpd_order')
            ->first();

        $extended = is_array($detail?->wpd_extended_field) ? $detail->wpd_extended_field : [];
        $newStatus = $extended['permohonan_status'] ?? $this->legacyApproveStatus($stage, $keputusan);

        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $workflow = is_array($details['workflow'] ?? null) ? $details['workflow'] : [];

        $workflow[$stage] = [
            'keputusan' => $keputusan,
            'catatan' => $catatan,
            'by_user_id' => $user->id,
            'by_user_name' => $user->name,
            'at' => now()->toIso8601String(),
            'status' => $newStatus,
            'wf_workflow_code' => $workflowCode,
            'wf_process_id' => $process->wfp_process_id,
            'wf_process_name' => $process->wfp_process_name,
            'wf_status_code' => $statusCode,
        ];

        $details['workflow'] = $workflow;

        $payload = [
            'status' => $newStatus,
            'wf_workflow_code' => $workflowCode,
            'wf_current_process_id' => $detail?->wpd_reroute_process,
            'details' => $details,
        ];

        if ($keputusan === 'lulus' && in_array($newStatus, ['Diluluskan', 'Lengkap'], true)) {
            $payload['wf_current_process_id'] = null;
        }

        return $payload;
    }

    /**
     * @return array{status: string, details: array<string, mixed>}
     */
    private function buildLegacyTransition(
        Permohonan $permohonan,
        User $user,
        string $stage,
        string $keputusan,
        ?string $catatan,
    ): array {
        $status = $this->legacyApproveStatus($stage, $keputusan);

        $details = is_array($permohonan->details) ? $permohonan->details : [];
        $workflow = is_array($details['workflow'] ?? null) ? $details['workflow'] : [];

        $workflow[$stage] = [
            'keputusan' => $keputusan,
            'catatan' => $catatan,
            'by_user_id' => $user->id,
            'by_user_name' => $user->name,
            'at' => now()->toIso8601String(),
            'status' => $status,
        ];

        $details['workflow'] = $workflow;

        return [
            'status' => $status,
            'details' => $details,
        ];
    }

    private function legacyApproveStatus(string $stage, string $keputusan): string
    {
        if ($keputusan === 'tolak') {
            return 'Ditolak';
        }

        return match ($stage) {
            self::STAGE_SEMAKAN => 'Disemak',
            self::STAGE_SOKONGAN => 'Disokong',
            self::STAGE_KELULUSAN => 'Diluluskan',
            default => throw new InvalidArgumentException('Invalid workflow stage'),
        };
    }
}
