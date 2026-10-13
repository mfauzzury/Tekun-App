import { ref } from "vue";

import { createPemohonPermohonan } from "@/api/pemohonPermohonan";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import type { PemohonMyKadScan, PermohonanAttachment, PpDataAlternatif, PpFaceResult } from "@/types";

export type PemohonDraft = {
  form?: Record<string, unknown>;
  adaPasangan?: boolean;
  backendId?: number | null;
  accessToken?: string | null;
  attachments?: PermohonanAttachment[];
  currentStep?: number;
  ekycMyKad?: PemohonMyKadScan | null;
  ekycWajah?: PpFaceResult | null;
  dataAlternatif?: PpDataAlternatif | null;
};

/**
 * The applicant's single in-progress (Draf) application, shared by the application form and the
 * standalone e-KYC page. Backed by the pemohon store draft (localStorage) + a backend Draf record.
 */
export function usePemohonDraft() {
  const pemohon = usePemohonStore();
  const toast = useToast();

  const backendId = ref<number | null>(null);
  const accessToken = ref<string | null>(null);

  function read(): PemohonDraft | null {
    return (pemohon.loadDraft() as PemohonDraft | null) ?? null;
  }

  function restoreIds() {
    const draft = read();
    backendId.value = draft?.backendId ?? null;
    accessToken.value = draft?.accessToken ?? null;
  }

  function save(patch: Partial<PemohonDraft>) {
    pemohon.saveDraft({
      ...(read() ?? {}),
      ...patch,
      backendId: backendId.value,
      accessToken: accessToken.value,
    });
  }

  async function ensureBackendRecord(nama?: string): Promise<{ id: number; token: string } | null> {
    if (backendId.value && accessToken.value) {
      return { id: backendId.value, token: accessToken.value };
    }
    try {
      const res = await createPemohonPermohonan({ status: "Draf", nama: nama?.trim() || pemohon.profil.nama || "Draf" });
      backendId.value = res.data.id;
      accessToken.value = res.data.pemohonAccessToken;
      save({});
      return { id: res.data.id, token: res.data.pemohonAccessToken };
    } catch (e) {
      toast.error("Gagal menyediakan permohonan", e instanceof Error ? e.message : undefined);
      return null;
    }
  }

  restoreIds();

  return { backendId, accessToken, read, save, restoreIds, ensureBackendRecord };
}
