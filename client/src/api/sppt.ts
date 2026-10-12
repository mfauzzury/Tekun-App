import { API_BASE_URL } from "@/env";
import { apiRequest, ensureCsrfCookie } from "./client";
import type {
  AiRiskScoringInput,
  AiRiskScoringResult,
  AiCreditScoringInput,
  AiCreditScoringResult,
  PembiayaanFormulaComparison,
  PembiayaanFormulaDefaults,
  PembiayaanFormulaInput,
  PermohonanPenilaianInput,
  PermohonanPenilaianSaveResult,
  AkaunPembiayaan,
  BatchBayaran,
  JadualAnsuranRow,
  PermohonanPenjadualan,
  PermohonanPenjadualanInput,
  TawidhConfig,
  KoleksiConfig,
  NplAgingBucket,
  NotisKutipan,
  TransaksiBayaran,
  TransaksiBayaranInput,
  DocumentClassification,
  Permohonan,
  PermohonanAttachment,
  PermohonanFormOcrResult,
  DocumentVerification,
  PermohonanInput,
  SpptCawangan,
  SpptCawanganInput,
  SpptDashboardSummary,
  SpptHardRulesConfig,
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

export async function listSpptCawangan(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: SpptCawangan[]; meta: Record<string, unknown> }>(`/api/sppt/cawangan${query(params)}`);
}

export async function getSpptCawangan(id: number) {
  return apiRequest<{ data: SpptCawangan }>(`/api/sppt/cawangan/${id}`);
}

export async function createSpptCawangan(input: SpptCawanganInput) {
  return apiRequest<{ data: SpptCawangan }>("/api/sppt/cawangan", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function updateSpptCawangan(id: number, input: Partial<SpptCawanganInput>) {
  return apiRequest<{ data: SpptCawangan }>(`/api/sppt/cawangan/${id}`, {
    method: "PUT",
    body: JSON.stringify(input),
  });
}

export async function deleteSpptCawangan(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/sppt/cawangan/${id}`, {
    method: "DELETE",
  });
}

export async function listSpptCawanganNegeriOptions() {
  return apiRequest<{ data: string[] }>("/api/sppt/cawangan/negeri-options");
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

export async function updateSpptHardRulesSetup(key: string, hardRules: SpptHardRulesConfig) {
  return apiRequest<{ data: SpptSetupCategory }>(`/api/sppt/setup/${key}`, {
    method: "PUT",
    body: JSON.stringify(hardRules),
  });
}

export async function fetchSpptDataset(module: string, key?: string) {
  const path = key ? `/api/sppt/datasets/${module}/${key}` : `/api/sppt/datasets/${module}`;
  return apiRequest<{ data: unknown }>(path);
}

export type SpptModuleDatasets = Record<string, unknown>;

/** Fetch all datasets for a module in one request (avoids N+1 on single-threaded PHP dev server). */
export async function fetchSpptModuleDatasets(module: string): Promise<SpptModuleDatasets> {
  const res = await fetchSpptDataset(module);
  return (res.data ?? {}) as SpptModuleDatasets;
}

/** Read one dataset from bulk module payload; `datasetKey` is the backend snake_case key. */
export function pickSpptModuleDataset<T>(
  datasets: SpptModuleDatasets,
  datasetKey: string,
  fallback: T,
): T {
  const camelKey = datasetKey.replace(/_([a-z])/g, (_, char: string) => char.toUpperCase());
  const value = datasets[camelKey];
  return (value === undefined ? fallback : value) as T;
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

export async function runPermohonanAiRiskScoring(input: AiRiskScoringInput) {
  return apiRequest<{ data: AiRiskScoringResult }>("/api/sppt/permohonan/risk-scoring/score", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function scorePermohonanRisk(permohonanId: number) {
  return apiRequest<{ data: AiRiskScoringResult }>(`/api/sppt/permohonan/${permohonanId}/risk-scoring`, {
    method: "POST",
  });
}

export async function runPermohonanAiCreditScoring(input: AiCreditScoringInput) {
  return apiRequest<{ data: AiCreditScoringResult }>("/api/sppt/permohonan/credit-scoring/score", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function scorePermohonanCredit(permohonanId: number) {
  await ensureCsrfCookie();
  return apiRequest<{ data: AiCreditScoringResult }>(`/api/sppt/permohonan/${permohonanId}/credit-scoring`, {
    method: "POST",
  });
}

export async function getPembiayaanFormulaDefaults() {
  return apiRequest<{ data: PembiayaanFormulaDefaults }>("/api/sppt/pembiayaan-formula/defaults");
}

export async function calculatePembiayaanFormula(input: PembiayaanFormulaInput) {
  return apiRequest<{ data: PembiayaanFormulaComparison }>("/api/sppt/pembiayaan-formula/calculate", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function savePermohonanPenilaian(permohonanId: number, input: PermohonanPenilaianInput) {
  await ensureCsrfCookie();
  return apiRequest<{ data: PermohonanPenilaianSaveResult }>(`/api/sppt/permohonan/${permohonanId}/penilaian`, {
    method: "POST",
    body: JSON.stringify(input),
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

export function permohonanOfferLetterUrl(permohonanId: number) {
  return `${API_BASE_URL}/api/sppt/permohonan/${permohonanId}/surat-tawaran`;
}

export async function openPermohonanOfferLetter(permohonanId: number) {
  await ensureCsrfCookie();
  const response = await fetch(permohonanOfferLetterUrl(permohonanId), {
    credentials: "include",
  });

  if (!response.ok) {
    let message = "Gagal menjana surat tawaran.";
    try {
      const payload = await response.json();
      message = (payload?.error?.message as string) || message;
    } catch {
      // non-JSON error body
    }
    throw new Error(message);
  }

  const blob = await response.blob();
  const blobUrl = URL.createObjectURL(blob);
  window.open(blobUrl, "_blank", "noopener,noreferrer");
}

export function isApprovedPermohonanStatus(status: string): boolean {
  return ["Diluluskan", "Lulus", "Berjaya"].includes(status);
}

export async function deletePermohonan(id: number) {
  return apiRequest<{ data: { success: boolean } }>(`/api/sppt/permohonan/${id}`, { method: "DELETE" });
}

export async function processPermohonanWorkflow(
  id: number,
  input: { stage: "semakan" | "sokongan" | "kelulusan"; keputusan: "lulus" | "tolak"; catatan?: string },
) {
  return apiRequest<{ data: Permohonan }>(`/api/sppt/permohonan/${id}/workflow`, {
    method: "POST",
    body: JSON.stringify(input),
  });
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

export async function getAkaunJadual(akaunDbId: number) {
  return apiRequest<{ data: JadualAnsuranRow[] }>(`/api/sppt/akaun/${akaunDbId}/jadual`);
}

export async function listPenjadualan(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: PermohonanPenjadualan[]; meta: Record<string, unknown> }>(`/api/sppt/penjadualan${query(params)}`);
}

export async function createPenjadualan(input: PermohonanPenjadualanInput) {
  return apiRequest<{ data: PermohonanPenjadualan }>("/api/sppt/penjadualan", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function approvePenjadualan(id: number, tindakan: "lulus" | "tolak", catatanPegawai?: string) {
  return apiRequest<{ data: PermohonanPenjadualan }>(`/api/sppt/penjadualan/${id}/approve`, {
    method: "POST",
    body: JSON.stringify({ tindakan, catatanPegawai }),
  });
}

export async function listBayaran(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: TransaksiBayaran[]; meta: Record<string, unknown> }>(`/api/sppt/bayaran${query(params)}`);
}

export async function createBayaran(input: TransaksiBayaranInput) {
  return apiRequest<{ data: TransaksiBayaran }>("/api/sppt/bayaran", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function createBatchBayaran(saluran: string, rows: Array<{ noAkaun: string; jumlah: number; noRujukanLuar?: string }>) {
  return apiRequest<{ data: BatchBayaran }>("/api/sppt/bayaran/batch", {
    method: "POST",
    body: JSON.stringify({ saluran, rows }),
  });
}

export async function listBatchBayaran(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: BatchBayaran[]; meta: Record<string, unknown> }>(`/api/sppt/bayaran/batches${query(params)}`);
}

export async function fetchTawidhConfig() {
  return apiRequest<{ data: TawidhConfig }>("/api/sppt/tawidh/config");
}

export async function recalculateTawidhAll() {
  return apiRequest<{ data: { akaunDikira: number } }>("/api/sppt/tawidh/recalculate", { method: "POST" });
}

export async function listTunggakanAkaun(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: AkaunPembiayaan[]; meta: Record<string, unknown> }>(`/api/sppt/tawidh/tunggakan${query(params)}`);
}

export async function fetchKoleksiConfig() {
  return apiRequest<{ data: KoleksiConfig }>("/api/sppt/koleksi/config");
}

export async function listKlasifikasiAkaun(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: AkaunPembiayaan[]; meta: Record<string, unknown> }>(`/api/sppt/koleksi/klasifikasi${query(params)}`);
}

export async function fetchKlasifikasiSummary() {
  return apiRequest<{ data: NplAgingBucket[] }>("/api/sppt/koleksi/klasifikasi/summary");
}

export async function recalculateKlasifikasiNpl() {
  return apiRequest<{ data: { akaunDikemaskini: number } }>("/api/sppt/koleksi/klasifikasi/recalculate", { method: "POST" });
}

export async function listNotisKutipan(params: Record<string, string | number | undefined> = {}) {
  return apiRequest<{ data: NotisKutipan[]; meta: Record<string, unknown> }>(`/api/sppt/koleksi/notis${query(params)}`);
}

export async function processDunning() {
  return apiRequest<{ data: { notisDijana: number; notisDihantar: number; gagal: number } }>("/api/sppt/koleksi/notis/process", { method: "POST" });
}

export function notisKutipanPdfUrl(dbId: number) {
  return `${API_BASE_URL}/api/sppt/koleksi/notis/${dbId}/pdf`;
}
