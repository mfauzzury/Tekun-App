import { apiRequest } from "@/api/client";
import type {
  WfAuthorizedRole,
  WfAuthorizedRoleInput,
  WfAuthorizedRoleUpdateInput,
  WfLookupOption,
  WfProcess,
  WfProcessDetail,
  WfProcessDetailInput,
  WfProcessDetailUpdateInput,
  WfProcessInput,
  WfProcessUpdateInput,
  WfRerouteProcessOption,
  WfRoleReferenceOption,
  WfWorkflow,
  WfWorkflowInput,
  WfWorkflowUpdateInput,
} from "@/types";

export async function listWorkflowConfigurations() {
  return apiRequest<{ data: WfWorkflow[] }>("/api/workflow-configuration");
}

export async function storeWorkflow(input: WfWorkflowInput) {
  return apiRequest<{ data: WfWorkflow }>("/api/workflow-configuration/workflow", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function updateWorkflow(code: string, input: WfWorkflowUpdateInput) {
  return apiRequest<{ data: WfWorkflow }>(`/api/workflow-configuration/workflow/${encodeURIComponent(code)}`, {
    method: "PUT",
    body: JSON.stringify(input),
  });
}

export async function destroyWorkflow(code: string) {
  return apiRequest<{ data: { success: boolean } }>(`/api/workflow-configuration/workflow/${encodeURIComponent(code)}`, {
    method: "DELETE",
  });
}

export async function listWorkflowProcesses(code: string) {
  return apiRequest<{ data: WfProcess[] }>(`/api/workflow-configuration/${encodeURIComponent(code)}/processes`);
}

export async function storeWorkflowProcess(input: WfProcessInput) {
  return apiRequest<{ data: WfProcess }>("/api/workflow-configuration/process", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function updateWorkflowProcess(id: number, input: WfProcessUpdateInput) {
  return apiRequest<{ data: WfProcess }>(`/api/workflow-configuration/process/${id}`, {
    method: "PUT",
    body: JSON.stringify(input),
  });
}

export async function destroyWorkflowProcess(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/workflow-configuration/process/${id}`, {
    method: "DELETE",
  });
}

export async function listWorkflowProcessDetails(processId: number) {
  return apiRequest<{ data: WfProcessDetail[] }>(`/api/workflow-configuration/processes/${processId}/details`);
}

export async function storeWorkflowProcessDetail(input: WfProcessDetailInput) {
  return apiRequest<{ data: WfProcessDetail }>("/api/workflow-configuration/process-detail", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function updateWorkflowProcessDetail(id: number, input: WfProcessDetailUpdateInput) {
  return apiRequest<{ data: WfProcessDetail }>(`/api/workflow-configuration/process-detail/${id}`, {
    method: "PUT",
    body: JSON.stringify(input),
  });
}

export async function destroyWorkflowProcessDetail(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/workflow-configuration/process-detail/${id}`, {
    method: "DELETE",
  });
}

export async function listWorkflowAuthorizedRoles(processId: number) {
  return apiRequest<{ data: WfAuthorizedRole[] }>(`/api/workflow-configuration/processes/${processId}/authorized-roles`);
}

export async function storeWorkflowAuthorizedRole(input: WfAuthorizedRoleInput) {
  return apiRequest<{ data: WfAuthorizedRole }>("/api/workflow-configuration/authorized-role", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function updateWorkflowAuthorizedRole(id: number, input: WfAuthorizedRoleUpdateInput) {
  return apiRequest<{ data: WfAuthorizedRole }>(`/api/workflow-configuration/authorized-role/${id}`, {
    method: "PUT",
    body: JSON.stringify(input),
  });
}

export async function destroyWorkflowAuthorizedRole(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/workflow-configuration/authorized-role/${id}`, {
    method: "DELETE",
  });
}

export async function listWorkflowStatusLookup() {
  return apiRequest<{ data: WfLookupOption[] }>("/api/workflow-configuration/status-lookup");
}

export async function listWorkflowRerouteProcessOptions() {
  return apiRequest<{ data: WfRerouteProcessOption[] }>("/api/workflow-configuration/reroute-process-options");
}

export async function listWorkflowRoleReferences() {
  return apiRequest<{ data: WfRoleReferenceOption[] }>("/api/workflow-configuration/peranan-rujukan");
}
