import { apiRequest } from "./client";
import type {
  AkaunPembiayaan,
  Permohonan,
  PermohonanInput,
  SpptDashboardSummary,
  SpptReferenceData,
  SpptSetupCategory,
  SpptSetupStatusItem,
  Usahawan,
  UsahawanInput,
} from "@/types";

function query(params: Record<string, string | number | undefined>) {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== "") search.set(key, String(value));
  }
  const qs = search.toString();
  return qs ? `?${qs}` : "";
}

export async function fetchSpptDashboardSummary() {
  return apiRequest<{ data: SpptDashboardSummary }>("/api/sppt/dashboard/summary");
}

export async function fetchSpptReferenceData() {
  return apiRequest<{ data: SpptReferenceData }>("/api/sppt/reference-data");
}

export async function fetchSpptSetup() {
  return apiRequest<{ data: SpptSetupCategory[] }>("/api/sppt/setup");
}

export async function fetchSpptSetupCategory(key: string) {
  return apiRequest<{ data: SpptSetupCategory }>(`/api/sppt/setup/${key}`);
}

export async function updateSpptSetupCategory(key: string, items: SpptSetupStatusItem[]) {
  return apiRequest<{ data: SpptSetupCategory }>(`/api/sppt/setup/${key}`, {
    method: "PUT",
    body: JSON.stringify({ items }),
  });
}

export async function fetchSpptDataset(module: string, key?: string) {
  const path = key ? `/api/sppt/datasets/${module}/${key}` : `/api/sppt/datasets/${module}`;
  return apiRequest<{ data: unknown }>(path);
}

export async function listPermohonan(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: Permohonan[]; meta: Record<string, unknown> }>(`/api/sppt/permohonan${query(params)}`);
}

export async function getPermohonanSummary() {
  return apiRequest<{ data: Record<string, number> }>("/api/sppt/permohonan/summary");
}

export async function getPermohonan(id: number) {
  return apiRequest<{ data: Permohonan }>(`/api/sppt/permohonan/${id}`);
}

export async function createPermohonan(input: PermohonanInput) {
  return apiRequest<{ data: Permohonan }>("/api/sppt/permohonan", { method: "POST", body: JSON.stringify(input) });
}

export async function updatePermohonan(id: number, input: Partial<PermohonanInput>) {
  return apiRequest<{ data: Permohonan }>(`/api/sppt/permohonan/${id}`, { method: "PUT", body: JSON.stringify(input) });
}

export async function deletePermohonan(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/sppt/permohonan/${id}`, { method: "DELETE" });
}

export async function listUsahawan(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: Usahawan[]; meta: Record<string, unknown> }>(`/api/sppt/usahawan${query(params)}`);
}

export async function getUsahawanSummary() {
  return apiRequest<{ data: Record<string, number> }>("/api/sppt/usahawan/summary");
}

export async function createUsahawan(input: UsahawanInput) {
  return apiRequest<{ data: Usahawan }>("/api/sppt/usahawan", { method: "POST", body: JSON.stringify(input) });
}

export async function listAkaun(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: AkaunPembiayaan[]; meta: Record<string, unknown> }>(`/api/sppt/akaun${query(params)}`);
}

export async function listPengeluaranDana(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: unknown[]; meta: Record<string, unknown> }>(`/api/sppt/pengeluaran-dana${query(params)}`);
}

export async function listJaminan(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: unknown[]; meta: Record<string, unknown> }>(`/api/sppt/jaminan${query(params)}`);
}

export async function listKutipan(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: unknown[]; meta: Record<string, unknown> }>(`/api/sppt/kutipan${query(params)}`);
}
