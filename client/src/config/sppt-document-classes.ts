/** Spec 1.7.5 — permohonan document class options (keep in sync with config/sppt.php). */
export const SPPT_DOCUMENT_CLASSES = [
  { value: "ic_pemohon_depan", label: "IC Pemohon (Depan)" },
  { value: "ic_pemohon_belakang", label: "IC Pemohon (Belakang)" },
  { value: "ic_pemohon_combined", label: "IC Pemohon (Depan & Belakang)" },
  { value: "ic_pasangan_depan", label: "IC Pasangan (Depan)" },
  { value: "ic_pasangan_belakang", label: "IC Pasangan (Belakang)" },
  { value: "ic_pasangan_combined", label: "IC Pasangan (Depan & Belakang)" },
  { value: "ssm_form_9", label: "SSM Form 9" },
  { value: "lesen_pbt", label: "Lesen / Permit daripada PBT" },
  { value: "penyata_bank", label: "Penyata Bank" },
  { value: "plan_perniagaan", label: "Plan Perniagaan" },
  { value: "lain_lain", label: "Lain-Lain" },
] as const;

export type SpptDocumentClass = (typeof SPPT_DOCUMENT_CLASSES)[number]["value"];

const LEGACY_DOCUMENT_CLASS_MAP: Record<string, SpptDocumentClass> = {
  ic_pemohon: "ic_pemohon_combined",
  ic_pasangan: "ic_pasangan_combined",
};

export function normalizeDocumentClass(documentClass?: string | null): SpptDocumentClass | undefined {
  if (!documentClass) {
    return undefined;
  }

  if (documentClass in LEGACY_DOCUMENT_CLASS_MAP) {
    return LEGACY_DOCUMENT_CLASS_MAP[documentClass];
  }

  const match = SPPT_DOCUMENT_CLASSES.find((item) => item.value === documentClass);
  return match?.value;
}

export function documentClassLabel(
  documentClass?: string | null,
  documentClassOther?: string | null,
): string {
  if (!documentClass) {
    return "Belum diklasifikasi";
  }

  if (documentClass === "lain_lain" && documentClassOther?.trim()) {
    return documentClassOther.trim();
  }

  const normalized = normalizeDocumentClass(documentClass) ?? documentClass;
  const match = SPPT_DOCUMENT_CLASSES.find((item) => item.value === normalized);
  if (match) {
    return match.label;
  }

  if (documentClass === "ic_pemohon") {
    return "IC Pemohon";
  }
  if (documentClass === "ic_pasangan") {
    return "IC Pasangan";
  }

  return documentClass;
}
