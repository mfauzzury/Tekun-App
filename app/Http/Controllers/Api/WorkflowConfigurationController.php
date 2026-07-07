<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWfAuthorizedRoleRequest;
use App\Http\Requests\StoreWfProcessDetailRequest;
use App\Http\Requests\StoreWfProcessRequest;
use App\Http\Requests\StoreWfWorkflowRequest;
use App\Http\Requests\UpdateWfAuthorizedRoleRequest;
use App\Http\Requests\UpdateWfProcessDetailRequest;
use App\Http\Requests\UpdateWfProcessRequest;
use App\Http\Requests\UpdateWfWorkflowRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Role;
use App\Models\WfAuthorizedRole;
use App\Models\WfLookup;
use App\Models\WfProcess;
use App\Models\WfProcessDetail;
use App\Models\WfWorkflowName;
use Illuminate\Http\JsonResponse;

class WorkflowConfigurationController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $workflows = WfWorkflowName::orderBy('wfa_workflow_code')->get();

        return $this->sendOk($workflows);
    }

    public function storeWorkflow(StoreWfWorkflowRequest $request): JsonResponse
    {
        $data = $request->validated();

        $workflow = WfWorkflowName::create([
            'wfa_workflow_code' => strtoupper($data['wfa_workflow_code']),
            'wfa_workflow_title' => $data['wfa_workflow_title'],
            'wfa_prevent_self_process' => $data['wfa_prevent_self_process'] ?? null,
            'wfa_involve_posting' => $data['wfa_involve_posting'] ?? 1,
        ]);

        return $this->sendCreated($workflow);
    }

    public function updateWorkflow(UpdateWfWorkflowRequest $request, string $code): JsonResponse
    {
        $workflow = WfWorkflowName::find($code);

        if (! $workflow) {
            return $this->sendError(404, 'NOT_FOUND', 'Workflow not found');
        }

        $data = $request->validated();

        $workflow->update([
            'wfa_workflow_title' => $data['wfa_workflow_title'],
            'wfa_prevent_self_process' => $data['wfa_prevent_self_process'] ?? null,
            'wfa_involve_posting' => $data['wfa_involve_posting'] ?? 1,
        ]);

        return $this->sendOk($workflow->fresh());
    }

    public function destroyWorkflow(string $code): JsonResponse
    {
        $deleted = WfWorkflowName::where('wfa_workflow_code', $code)->delete();

        if (! $deleted) {
            return $this->sendError(404, 'NOT_FOUND', 'Workflow not found');
        }

        return $this->sendOk(['success' => true]);
    }

    public function processes(string $code): JsonResponse
    {
        $processes = WfProcess::where('wfp_workflow_code', $code)
            ->withCount(['processDetails as wfp_process_details_count'])
            ->orderBy('wfp_sequence')
            ->get();

        return $this->sendOk($processes);
    }

    public function storeProcess(StoreWfProcessRequest $request): JsonResponse
    {
        $process = WfProcess::create($request->validated());

        return $this->sendCreated($process);
    }

    public function updateProcess(UpdateWfProcessRequest $request, int $id): JsonResponse
    {
        $process = WfProcess::find($id);

        if (! $process) {
            return $this->sendError(404, 'NOT_FOUND', 'Process not found');
        }

        $process->update($request->validated());

        return $this->sendOk($process->fresh());
    }

    public function destroyProcess(int $id): JsonResponse
    {
        $process = WfProcess::find($id);

        if (! $process) {
            return $this->sendError(404, 'NOT_FOUND', 'Process not found');
        }

        $detailCount = WfProcessDetail::where('wpd_process_id', $id)->count();

        if ($detailCount > 0) {
            return $this->sendError(409, 'HAS_DETAILS', 'Cannot delete process with existing process details');
        }

        $process->delete();

        return $this->sendOk(['success' => true]);
    }

    public function details(int $id): JsonResponse
    {
        $details = WfProcessDetail::where('wpd_process_id', $id)
            ->orderBy('wpd_order')
            ->orderBy('wpd_status_code')
            ->get()
            ->map(function ($detail) {
                $extended = $detail->wpd_extended_field ?? [];
                $detail->wpd_status_desc = $extended['wpd_status_desc'] ?? null;
                $detail->wpd_reroute_url = $extended['wpd_reroute_url'] ?? null;

                $lookup = WfLookup::where('wfl_code', $detail->wpd_status_code)->first();
                $detail->wfl_isPositive = $lookup?->wfl_isPositive ?? null;

                return $detail;
            });

        return $this->sendOk($details);
    }

    public function storeProcessDetail(StoreWfProcessDetailRequest $request): JsonResponse
    {
        $data = $request->validated();
        $statusDesc = $data['wpd_status_desc'];

        $rerouteProcess = $data['wpd_reroute_process'] ?? null;
        $rerouteUrl = $data['wpd_reroute_url'] ?? null;

        if ($rerouteProcess !== null) {
            $targetProcess = WfProcess::find($rerouteProcess);
            if ($targetProcess && $targetProcess->wfp_workflow_code !== WfProcess::find($data['wpd_process_id'])?->wfp_workflow_code) {
                if (empty($rerouteUrl)) {
                    return $this->sendError(422, 'VALIDATION_ERROR', 'URL is required for cross-workflow reroute');
                }
            }
        }

        $extendedField = [
            'wpd_status_desc' => $statusDesc,
            'wpd_reroute_url' => $rerouteUrl ? substr($rerouteUrl, 0, 500) : null,
        ];

        $detail = WfProcessDetail::create([
            'wpd_process_id' => $data['wpd_process_id'],
            'wpd_status_code' => $data['wpd_status_code'],
            'wpd_reroute_process' => $rerouteProcess,
            'wpd_order' => $data['wpd_order'],
            'wpd_extended_field' => $extendedField,
        ]);

        $detail->wpd_status_desc = $statusDesc;
        $detail->wpd_reroute_url = $extendedField['wpd_reroute_url'];

        return $this->sendCreated($detail);
    }

    public function updateProcessDetail(UpdateWfProcessDetailRequest $request, int $id): JsonResponse
    {
        $detail = WfProcessDetail::find($id);

        if (! $detail) {
            return $this->sendError(404, 'NOT_FOUND', 'Process detail not found');
        }

        $data = $request->validated();
        $statusDesc = $data['wpd_status_desc'];
        $rerouteProcess = $data['wpd_reroute_process'] ?? null;
        $rerouteUrl = $data['wpd_reroute_url'] ?? null;

        if ($rerouteProcess !== null) {
            $targetProcess = WfProcess::find($rerouteProcess);
            $sourceProcess = WfProcess::find($detail->wpd_process_id);
            if ($targetProcess && $sourceProcess && $targetProcess->wfp_workflow_code !== $sourceProcess->wfp_workflow_code) {
                if (empty($rerouteUrl)) {
                    return $this->sendError(422, 'VALIDATION_ERROR', 'URL is required for cross-workflow reroute');
                }
            }
        }

        $detail->update([
            'wpd_status_code' => $data['wpd_status_code'],
            'wpd_reroute_process' => $rerouteProcess,
            'wpd_order' => $data['wpd_order'],
            'wpd_extended_field' => [
                'wpd_status_desc' => $statusDesc,
                'wpd_reroute_url' => $rerouteUrl ? substr($rerouteUrl, 0, 500) : null,
            ],
        ]);

        $fresh = $detail->fresh();
        if ($fresh) {
            $fresh->wpd_status_desc = $statusDesc;
            $fresh->wpd_reroute_url = $rerouteUrl ? substr($rerouteUrl, 0, 500) : null;
        }

        return $this->sendOk($fresh);
    }

    public function destroyProcessDetail(int $id): JsonResponse
    {
        $deleted = WfProcessDetail::where('wpd_process_details_id', $id)->delete();

        if (! $deleted) {
            return $this->sendError(404, 'NOT_FOUND', 'Process detail not found');
        }

        return $this->sendOk(['success' => true]);
    }

    public function authorizedRoles(int $id): JsonResponse
    {
        $roles = WfAuthorizedRole::where('war_process_id', $id)->get();
        $roleLookup = Role::pluck('description', 'name');

        $roles = $roles->map(function ($role) use ($roleLookup) {
            $role->war_group_name = $roleLookup[$role->war_group_code] ?? null;

            return $role;
        });

        return $this->sendOk($roles);
    }

    public function storeAuthorizedRole(StoreWfAuthorizedRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = WfAuthorizedRole::create([
            'war_process_id' => $data['war_process_id'],
            'war_group_code' => $data['war_group_code'],
            'war_limit_min' => isset($data['war_limit_min']) && $data['war_limit_min'] !== '' ? $data['war_limit_min'] : null,
            'war_limit_max' => isset($data['war_limit_max']) && $data['war_limit_max'] !== '' ? $data['war_limit_max'] : null,
        ]);

        return $this->sendCreated($role);
    }

    public function updateAuthorizedRole(UpdateWfAuthorizedRoleRequest $request, int $id): JsonResponse
    {
        $role = WfAuthorizedRole::find($id);

        if (! $role) {
            return $this->sendError(404, 'NOT_FOUND', 'Authorized role not found');
        }

        $data = $request->validated();

        $role->update([
            'war_group_code' => $data['war_group_code'],
            'war_limit_min' => isset($data['war_limit_min']) && $data['war_limit_min'] !== '' ? $data['war_limit_min'] : null,
            'war_limit_max' => isset($data['war_limit_max']) && $data['war_limit_max'] !== '' ? $data['war_limit_max'] : null,
        ]);

        return $this->sendOk($role->fresh());
    }

    public function destroyAuthorizedRole(int $id): JsonResponse
    {
        $deleted = WfAuthorizedRole::where('war_authorized_role_id', $id)->delete();

        if (! $deleted) {
            return $this->sendError(404, 'NOT_FOUND', 'Authorized role not found');
        }

        return $this->sendOk(['success' => true]);
    }

    public function statusLookup(): JsonResponse
    {
        $rows = WfLookup::orderBy('wfl_code')->get([
            'wfl_code as kod',
            'wfl_desc as keterangan',
            'wfl_isPositive',
        ]);

        return $this->sendOk($rows);
    }

    public function rerouteProcessOptions(): JsonResponse
    {
        $processes = WfProcess::where('wfp_status', '1')
            ->with('workflow')
            ->orderBy('wfp_workflow_code')
            ->orderBy('wfp_sequence')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->wfp_process_id,
                'description' => "{$p->wfp_workflow_code}: ".($p->workflow->wfa_workflow_title ?? $p->wfp_workflow_code)." - {$p->wfp_process_name}",
                'wfp_workflow_code' => $p->wfp_workflow_code,
            ]);

        return $this->sendOk($processes);
    }

    public function roleLookup(): JsonResponse
    {
        $roles = Role::orderBy('name')
            ->get(['name as code', 'description']);

        return $this->sendOk($roles);
    }
}
