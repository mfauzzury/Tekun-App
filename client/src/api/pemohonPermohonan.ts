import { API_BASE_URL } from "@/env";
import { apiRequest, ensureCsrfCookie } from "./client";
import type {
  PemohonAiProgress,
  PemohonMyKadScan,
  PpDataAlternatif,
  PpDataAlternatifJenis,
  PpEkstrakResult,
  PpFaceResult,
  DocumentClassification,
  DocumentVerification,
  PemohonPermohonan,
  PemohonPermohonanInput,
  PermohonanAttachment,
} from "@/types";

export async function createPemohonPermohonan(input: PemohonPermohonanInput) {
  await ensureCsrfCookie();
  return apiRequest<{ data: PemohonPermohonan }>("/api/public/pemohon/permohonan", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function getPemohonPermohonan(id: number, accessToken: string) {
  return apiRequest<{ data: PemohonPermohonan }>(
    `/api/public/pemohon/permohonan/${id}?accessToken=${encodeURIComponent(accessToken)}`,
  );
}

export async function updatePemohonPermohonan(id: number, accessToken: string, input: Partial<PemohonPermohonanInput>) {
  await ensureCsrfCookie();
  return apiRequest<{ data: PemohonPermohonan }>(`/api/public/pemohon/permohonan/${id}`, {
    method: "PUT",
    body: JSON.stringify({ ...input, accessToken }),
  });
}

export async function uploadPemohonPermohonanDocument(
  id: number,
  accessToken: string,
  file: File,
  documentClass?: string,
  documentClassOther?: string,
) {
  await ensureCsrfCookie();
  const formData = new FormData();
  formData.append("accessToken", accessToken);
  formData.append("file", file, file.name);
  if (documentClass) {
    formData.append("documentClass", documentClass);
  }
  if (documentClass === "lain_lain" && documentClassOther?.trim()) {
    formData.append("documentClassOther", documentClassOther.trim());
  }
  return apiRequest<{ data: PermohonanAttachment }>(`/api/public/pemohon/permohonan/${id}/dokumen`, {
    method: "POST",
    body: formData,
  });
}

export async function classifyPemohonPermohonanDocument(
  file: File,
  applicantIc?: string,
  applicantName?: string,
  spouseIc?: string,
  spouseName?: string,
) {
  const formData = new FormData();
  formData.append("file", file);
  if (applicantIc?.trim()) formData.append("applicantIc", applicantIc.trim());
  if (applicantName?.trim()) formData.append("applicantName", applicantName.trim());
  if (spouseIc?.trim()) formData.append("spouseIc", spouseIc.trim());
  if (spouseName?.trim()) formData.append("spouseName", spouseName.trim());
  return apiRequest<{ data: { classification: DocumentClassification; name: string } }>(
    "/api/public/pemohon/permohonan/dokumen/classify",
    { method: "POST", body: formData },
  );
}

export async function verifyPemohonPermohonanDocument(
  file: File,
  applicantIc?: string,
  applicantName?: string,
  spouseIc?: string,
  spouseName?: string,
) {
  const formData = new FormData();
  formData.append("file", file);
  if (applicantIc?.trim()) formData.append("applicantIc", applicantIc.trim());
  if (applicantName?.trim()) formData.append("applicantName", applicantName.trim());
  if (spouseIc?.trim()) formData.append("spouseIc", spouseIc.trim());
  if (spouseName?.trim()) formData.append("spouseName", spouseName.trim());
  return apiRequest<{ data: { verification: DocumentVerification; name: string } }>(
    "/api/public/pemohon/permohonan/dokumen/verify",
    { method: "POST", body: formData },
  );
}

export async function updatePemohonPermohonanDocumentClass(
  id: number,
  accessToken: string,
  attachmentId: string,
  documentClass: string,
  documentClassOther?: string,
) {
  return apiRequest<{ data: PermohonanAttachment }>(
    `/api/public/pemohon/permohonan/${id}/dokumen/${attachmentId}`,
    {
      method: "PATCH",
      body: JSON.stringify({
        accessToken,
        documentClass,
        documentClassOther: documentClass === "lain_lain" ? documentClassOther?.trim() : undefined,
      }),
    },
  );
}

export async function deletePemohonPermohonanDocument(id: number, accessToken: string, attachmentId: string) {
  return apiRequest<{ data: { success: boolean } }>(`/api/public/pemohon/permohonan/${id}/dokumen/${attachmentId}`, {
    method: "DELETE",
    body: JSON.stringify({ accessToken }),
  });
}

/** Attachments are stored on the public disk, so their `url` is directly web-accessible. */
export function pemohonPermohonanDocumentViewUrl(attachmentUrl: string) {
  return `${API_BASE_URL}${attachmentUrl}`;
}

/** POC 1 — runs the AI fast-assessment pipeline after Hantar. Returns progress only (no scores). */
export async function startPemohonPenilaianPantas(id: number, accessToken: string) {
  await ensureCsrfCookie();
  return apiRequest<{ data: PemohonAiProgress }>(`/api/public/pemohon/permohonan/${id}/penilaian-pantas`, {
    method: "POST",
    body: JSON.stringify({ accessToken }),
  });
}

/** Runs one AI assessment step (in order) so the portal can show each step completing live. */
export async function runPemohonPenilaianLangkah(id: number, accessToken: string, kod: string) {
  return apiRequest<{ data: PemohonAiProgress }>(`/api/public/pemohon/permohonan/${id}/penilaian-pantas/langkah/${kod}`, {
    method: "POST",
    body: JSON.stringify({ accessToken }),
  });
}

export async function getPemohonPenilaianPantas(id: number, accessToken: string) {
  return apiRequest<{ data: PemohonAiProgress }>(
    `/api/public/pemohon/permohonan/${id}/penilaian-pantas?accessToken=${encodeURIComponent(accessToken)}`,
  );
}

/** e-KYC: AI OCR of the uploaded MyKad front (upload it first with class ic_pemohon_depan). */
export async function scanPemohonMyKad(id: number, accessToken: string) {
  await ensureCsrfCookie();
  return apiRequest<{ data: PemohonMyKadScan }>(`/api/public/pemohon/permohonan/${id}/ekyc/mykad`, {
    method: "POST",
    body: JSON.stringify({ accessToken }),
  });
}

/** e-KYC: liveness challenge frames (base64 JPEG, no data: prefix) + face match against the MyKad photo. */
export async function verifyPemohonWajah(
  id: number,
  accessToken: string,
  frames: { base64: string; mime: string; arahan: string }[],
) {
  await ensureCsrfCookie();
  return apiRequest<{ data: PpFaceResult }>(`/api/public/pemohon/permohonan/${id}/ekyc/wajah`, {
    method: "POST",
    body: JSON.stringify({ accessToken, frames }),
  });
}

export async function savePemohonDataAlternatif(id: number, accessToken: string, input: PpDataAlternatif) {
  await ensureCsrfCookie();
  return apiRequest<{ data: PpDataAlternatif }>(`/api/public/pemohon/permohonan/${id}/data-alternatif`, {
    method: "PUT",
    body: JSON.stringify({ ...input, accessToken }),
  });
}

/** AI reads an e-wallet / QR / utility / platform statement (image, PDF or CSV) into monthly figures. */
export async function extractPemohonPenyata(id: number, accessToken: string, jenis: PpDataAlternatifJenis, file: File) {
  await ensureCsrfCookie();
  const formData = new FormData();
  formData.append("accessToken", accessToken);
  formData.append("jenis", jenis);
  formData.append("file", file, file.name);
  return apiRequest<{ data: PpEkstrakResult }>(`/api/public/pemohon/permohonan/${id}/data-alternatif/ekstrak`, {
    method: "POST",
    body: formData,
  });
}
