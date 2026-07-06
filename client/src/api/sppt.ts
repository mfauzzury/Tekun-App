import { API_BASE_URL } from "@/env";
import { apiRequest, ensureCsrfCookie } from "./client";
import type {
  AkaunPembiayaan,
  DocumentClassification,
  Permohonan,
  PermohonanAttachment,
  PermohonanFormOcrResult,
  DocumentVerification,
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

export async function uploadPermohonanDocument(
  id: number,
  file: File,
  documentClass?: string,
  documentClassOther?: string,
) {
  await ensureCsrfCookie();
  const formData = new FormData();
  formData.append("file", file, file.name);
  if (documentClass) {
    formData.append("documentClass", documentClass);
  }
  if (documentClass === "lain_lain" && documentClassOther?.trim()) {
    formData.append("documentClassOther", documentClassOther.trim());
  }
  return apiRequest<{ data: PermohonanAttachment }>(`/api/sppt/permohonan/${id}/dokumen`, {
    method: "POST",
    body: formData,
  });
}

export async function classifyPermohonanDocument(
  file: File,
  applicantIc?: string,
  applicantName?: string,
  spouseIc?: string,
  spouseName?: string,
) {
  const formData = new FormData();
  formData.append("file", file);
  if (applicantIc?.trim()) {
    formData.append("applicantIc", applicantIc.trim());
  }
  if (applicantName?.trim()) {
    formData.append("applicantName", applicantName.trim());
  }
  if (spouseIc?.trim()) {
    formData.append("spouseIc", spouseIc.trim());
  }
  if (spouseName?.trim()) {
    formData.append("spouseName", spouseName.trim());
  }
  return apiRequest<{ data: { classification: DocumentClassification; name: string } }>(
    "/api/sppt/permohonan/dokumen/classify",
    { method: "POST", body: formData },
  );
}

export async function updatePermohonanDocumentClass(
  permohonanId: number,
  attachmentId: string,
  documentClass: string,
  documentClassOther?: string,
) {
  return apiRequest<{ data: PermohonanAttachment }>(
    `/api/sppt/permohonan/${permohonanId}/dokumen/${attachmentId}`,
    {
      method: "PATCH",
      body: JSON.stringify({
        documentClass,
        documentClassOther: documentClass === "lain_lain" ? documentClassOther?.trim() : undefined,
      }),
    },
  );
}

export async function extractPermohonanFormOcr(file: File) {
  await ensureCsrfCookie();
  const formData = new FormData();
  formData.append("file", file, file.name);
  return apiRequest<{ data: PermohonanFormOcrResult }>("/api/sppt/permohonan/ocr/extract", {
    method: "POST",
    body: formData,
  });
}

export async function verifyPermohonanDocument(
  file: File,
  applicantIc?: string,
  applicantName?: string,
  spouseIc?: string,
  spouseName?: string,
) {
  const formData = new FormData();
  formData.append("file", file);
  if (applicantIc?.trim()) {
    formData.append("applicantIc", applicantIc.trim());
  }
  if (applicantName?.trim()) {
    formData.append("applicantName", applicantName.trim());
  }
  if (spouseIc?.trim()) {
    formData.append("spouseIc", spouseIc.trim());
  }
  if (spouseName?.trim()) {
    formData.append("spouseName", spouseName.trim());
  }
  return apiRequest<{ data: { verification: DocumentVerification; name: string } }>(
    "/api/sppt/permohonan/dokumen/verify",
    { method: "POST", body: formData },
  );
}

export async function deletePermohonanDocument(id: number, attachmentId: string) {
  return apiRequest<{ data: { success: boolean } }>(`/api/sppt/permohonan/${id}/dokumen/${attachmentId}`, {
    method: "DELETE",
  });
}

export function permohonanDocumentViewUrl(permohonanId: number, attachmentId: string) {
  return `${API_BASE_URL}/api/sppt/permohonan/${permohonanId}/dokumen/${attachmentId}`;
}

export async function openPermohonanDocument(permohonanId: number, attachmentId: string) {
  await ensureCsrfCookie();
  const response = await fetch(permohonanDocumentViewUrl(permohonanId, attachmentId), {
    credentials: "include",
  });

  if (!response.ok) {
    throw new Error("Failed to open document");
  }

  const blob = await response.blob();
  const blobUrl = URL.createObjectURL(blob);
  window.open(blobUrl, "_blank", "noopener,noreferrer");
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

export async function getAkaunSummary() {
  return apiRequest<{ data: Record<string, number> }>("/api/sppt/akaun/summary");
}

export async function listPengeluaranDana(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: unknown[]; meta: Record<string, unknown> }>(`/api/sppt/pengeluaran-dana${query(params)}`);
}

export async function getPengeluaranDanaSummary() {
  return apiRequest<{ data: Record<string, number> }>("/api/sppt/pengeluaran-dana/summary");
}

export async function listJaminan(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: unknown[]; meta: Record<string, unknown> }>(`/api/sppt/jaminan${query(params)}`);
}

export async function getJaminanSummary() {
  return apiRequest<{ data: Record<string, number> }>("/api/sppt/jaminan/summary");
}

export async function listKutipan(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: unknown[]; meta: Record<string, unknown> }>(`/api/sppt/kutipan${query(params)}`);
}
