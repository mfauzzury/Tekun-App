import { API_BASE_URL } from "@/env";
import { apiRequest, ensureCsrfCookie } from "./client";
import type {
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
