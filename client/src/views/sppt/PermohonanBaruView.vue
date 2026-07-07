<script setup lang="ts">
import { evaluateHardRulesForPermohonanForm } from "@/composables/useHardRuleCheck";
import { useI18n } from "@/composables/useI18n";
import { useToast } from "@/composables/useToast";
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft, ArrowRight, FileText, Info, Paperclip, Save, Scan, Trash2, Upload } from "lucide-vue-next";

import { createPermohonan, classifyPermohonanDocument, deletePermohonanDocument, extractPermohonanFormOcr, getPermohonan, openPermohonanDocument, updatePermohonan, updatePermohonanDocumentClass, uploadPermohonanDocument, verifyPermohonanDocument } from "@/api/sppt";
import AdminLayout from "@/layouts/AdminLayout.vue";
import { useAuthStore } from "@/stores/auth";
import SpptDateInput from "@/components/sppt/SpptDateInput.vue";
import SpptTimeInput from "@/components/sppt/SpptTimeInput.vue";
import SpptDocumentClassModal from "@/components/sppt/SpptDocumentClassModal.vue";
import SpptFormLabel from "@/components/sppt/SpptFormLabel.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptStepper from "@/components/sppt/SpptStepper.vue";
import type { DocumentVerification, PermohonanAttachment, PermohonanFormOcrResult, PermohonanInput } from "@/types";
import { parseMalaysianIcBirthDate } from "@/utils/malaysianIc";
import { ageFromIsoDate, birthPartsFromIso, isoFromBirthParts, toHtmlDateValue } from "@/utils/spptDate";
import { toHtmlTimeValue } from "@/utils/spptTime";
import { uppercaseFormFields, uppercaseTextInput, matchSelectOption } from "@/utils/uppercaseText";
import {
  SPPT_DOCUMENT_MAX_BYTES,
  SPPT_DOCUMENT_MAX_LABEL,
} from "@/config/sppt-upload";
import {
  documentClassLabel,
  normalizeDocumentClass,
  SPPT_DOCUMENT_CLASSES,
  type SpptDocumentClass,
} from "@/config/sppt-document-classes";
import {
  AGAMA_OPTIONS,
  BANGSA_OPTIONS,
  BANK_OPERASI_OPTIONS,
  AGENSI_KURSUS_OPTIONS,
  INSTITUSI_PEMBIAYAAN_OPTIONS,
  JANTINA_OPTIONS,
  KEEKERAPAN_BAYARAN_OPTIONS,
  NEGERI_OPTIONS,
  PEMILIKAN_PERNIAGAAN_OPTIONS,
  PERKESO_PAKEJ,
  SEKTOR_PEKERJAAN_OPTIONS,
  SEKTOR_PERNIAGAAN_OPTIONS,
  STATUS_JAWATAN_OPTIONS,
  STATUS_KEDIAMAN_OPTIONS,
  STATUS_PEKERJAAN_OPTIONS,
  STATUS_PERNIAGAAN_OPTIONS,
  STATUS_PREMIS_OPTIONS,
  TAKAFUL_KEMALANGAN_PAKEJ,
  TARAF_PENDIDIKAN_OPTIONS,
  TARAF_PERKAHWINAN_OPTIONS,
} from "@/config/sppt-options";

const { t, tp } = useI18n();
const toast = useToast();
const authStore = useAuthStore();

const officerOffice = computed(() => {
  const branch = authStore.user?.cawangan;
  if (!branch) return null;
  return {
    negeri: branch.negeri ?? "—",
    cawangan: branch.name,
  };
});

const router = useRouter();
const route = useRoute();
const saving = ref(false);
const draftSaving = ref(false);
const hardRuleChecking = ref(false);
const loading = ref(false);
const saved = ref(false);
const draftId = ref<number | null>(null);
const noRujukan = ref("");
const isDragOver = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

const attachments = ref<(PermohonanAttachment & { file?: File })[]>([]);
const icSubmitVerifying = ref(false);

const classModalOpen = ref(false);
const classModalClassifying = ref(false);
const classModalFile = ref<File | null>(null);
const classModalSuggestedClass = ref<SpptDocumentClass>("lain_lain");
const classModalConfidence = ref(0);
const classModalMessage = ref("");
const classModalSelectedClass = ref<SpptDocumentClass>("lain_lain");
const classModalOtherLabel = ref("");
const pendingClassificationFiles = ref<File[]>([]);

const ocrProcessing = ref(false);
const ocrResult = ref<PermohonanFormOcrResult | null>(null);
const ocrFileName = ref("");
const ocrInputRef = ref<HTMLInputElement | null>(null);

const OCR_FIELD_LABELS: Record<string, string> = {
  kategoriPembiayaan: "Kategori Pembiayaan",
  nama: "Nama Pemohon",
  noIcBaru: "No. KP (Baru)",
  noIcLama: "No. KP (Lama)",
  jantina: "Jantina",
  agama: "Agama",
  tarikhLahirHari: "Tarikh Lahir (Hari)",
  tarikhLahirBulan: "Tarikh Lahir (Bulan)",
  tarikhLahirTahun: "Tarikh Lahir (Tahun)",
  bangsa: "Bangsa",
  tarafPerkahwinan: "Taraf Perkahwinan",
  statusKediaman: "Status Kediaman",
  alamat: "Alamat Kediaman",
  poskod: "Poskod",
  negeri: "Negeri",
  noTelefonBimbit: "No. Telefon Bimbit",
  email: "E-mel",
  namaPerniagaan: "Nama Perniagaan",
  jumlahPermohonan: "Jumlah Pembiayaan",
  tempohPembiayaan: "Tempoh Pembiayaan",
  namaPasangan: "Nama Pasangan",
  noIcPasangan: "No. KP Pasangan",
};

const ocrPreviewFields = computed(() => {
  if (!ocrResult.value?.fields) return [];
  return Object.entries(ocrResult.value.fields)
    .slice(0, 12)
    .map(([key, value]) => ({
      key,
      label: OCR_FIELD_LABELS[key] ?? key.replace(/([A-Z])/g, " $1").replace(/^./, (c) => c.toUpperCase()),
      value: typeof value === "boolean" ? (value ? "Ya" : "Tidak") : String(value),
      confidence: ocrResult.value?.fieldConfidence?.[key],
    }));
});

async function onOcrFileSelect(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  input.value = "";
  if (!file) return;

  if (file.size > SPPT_DOCUMENT_MAX_BYTES) {
    toast.error(`Fail melebihi had ${SPPT_DOCUMENT_MAX_LABEL}.`);
    return;
  }

  const ext = file.name.split(".").pop()?.toLowerCase() ?? "";
  if (!["pdf", "jpg", "jpeg", "png"].includes(ext)) {
    toast.error("Format tidak disokong. Sila gunakan PDF, JPG, atau PNG.");
    return;
  }

  ocrProcessing.value = true;
  ocrResult.value = null;
  ocrFileName.value = file.name;

  try {
    const res = await extractPermohonanFormOcr(file);
    ocrResult.value = res.data;
    if (res.data.populatedCount === 0) {
      toast.error(res.data.message);
    } else {
      toast.success(res.data.message);
    }
  } catch (err) {
    const message = err instanceof Error ? err.message : "AI-OCR gagal.";
    toast.error(message);
    ocrResult.value = null;
  } finally {
    ocrProcessing.value = false;
  }
}

function applyOcrToForm(overwriteExisting = false) {
  if (!ocrResult.value?.fields) return;

  let applied = 0;
  for (const [key, value] of Object.entries(ocrResult.value.fields)) {
    if (!(key in form.value) || value === undefined || value === null) {
      continue;
    }

    const current = (form.value as Record<string, unknown>)[key];
    const isEmpty = current === "" || current === null || current === undefined;
    if (!overwriteExisting && !isEmpty) {
      continue;
    }

    (form.value as Record<string, unknown>)[key] = value;
    applied += 1;
  }

  normalizeDropdownFields();
  normalizeDateFields();
  normalizeTimeFields();

  if (form.value.noIcBaru) {
    const birth = parseMalaysianIcBirthDate(form.value.noIcBaru);
    if (birth) {
      form.value.tarikhLahirHari = String(birth.day);
      form.value.tarikhLahirBulan = String(birth.month);
      form.value.tarikhLahirTahun = String(birth.year);
      form.value.umur = String(
        ageFromIsoDate(isoFromBirthParts(String(birth.day), String(birth.month), String(birth.year)))
          ?? form.value.umur,
      );
    }
  }

  const taraf = form.value.tarafPerkahwinan?.toLowerCase() ?? "";
  adaPasangan.value = ["berkahwin", "duda", "janda"].some((s) => taraf.includes(s))
    || Boolean(form.value.namaPasangan?.trim() || form.value.noIcPasangan?.trim());

  toast.success(`AI-OCR: ${applied} medan telah diisi. Sila semak semua langkah borang.`);
  ocrResult.value = null;
}

const icVerificationSummary = computed(() => {
  const verified = attachments.value.filter((item) => item.verification?.status === "verified");
  const backs = verified.filter((item) => item.verification?.documentType === "ic_back");
  const combined = verified.filter((item) => item.verification?.documentType === "ic_combined");
  return {
    hasApplicantFront: verified.some(
      (item) => (item.verification?.documentType === "ic_front" || item.verification?.documentType === "ic_combined")
        && item.verification?.subject !== "spouse",
    ),
    hasSpouseFront: verified.some(
      (item) => (item.verification?.documentType === "ic_front" || item.verification?.documentType === "ic_combined")
        && item.verification?.subject === "spouse",
    ),
    backCount: backs.length + combined.length,
  };
});

function spouseIcRequired(): boolean {
  return adaPasangan.value && Boolean(form.value.noIcPasangan?.trim());
}

function normalizeVerification(verification: DocumentVerification): DocumentVerification {
  if (verification.status === "failed" && verification.documentType === "other") {
    return { ...verification, status: "skipped", message: "Dokumen sokongan." };
  }
  return verification;
}

async function verifyAttachmentForSubmit(itemId: string, file: File) {
  const index = attachments.value.findIndex((item) => item.id === itemId);
  if (index < 0) return;

  try {
    const res = await verifyPermohonanDocument(
      file,
      form.value.noIcBaru,
      form.value.nama,
      spouseIcRequired() ? form.value.noIcPasangan : undefined,
      spouseIcRequired() ? form.value.namaPasangan : undefined,
    );
    const currentIndex = attachments.value.findIndex((item) => item.id === itemId);
    if (currentIndex >= 0) {
      attachments.value[currentIndex] = {
        ...attachments.value[currentIndex],
        verification: normalizeVerification(res.data.verification),
      };
    }
  } catch {
    // Non-IC or unreadable images are ignored until submit coverage check.
  }
}

async function verifyLocalAttachmentsForSubmit(): Promise<boolean> {
  const localImages = attachments.value.filter((item) => item.file?.type.startsWith("image/"));
  if (localImages.length === 0) {
    return true;
  }

  icSubmitVerifying.value = true;
  try {
    for (const item of localImages) {
      await verifyAttachmentForSubmit(item.id, item.file!);
    }

    const identityMismatch = attachments.value.some(
      (item) => item.verification?.documentType === "ic_front" && item.verification?.identityMatched === false,
    );
    if (identityMismatch) {
      validationErrors.value = [{
        stepIndex: 11,
        stepLabel: "Dokumen",
        messages: ["MyKad depan tidak sepadan dengan nama atau No. Kad Pengenalan pemohon."],
      }];
      return false;
    }

    const hasUnverifiedUploaded = attachments.value.some((item) => item.url && !item.file && !item.verification);
    if (hasUnverifiedUploaded) {
      return true;
    }

    if (!icVerificationSummary.value.hasApplicantFront || icVerificationSummary.value.backCount < 1) {
      validationErrors.value = [{
        stepIndex: 11,
        stepLabel: "Dokumen",
        messages: ["Sila lampirkan salinan MyKad depan dan belakang pemohon dalam dokumen sokongan."],
      }];
      return false;
    }

    if (
      spouseIcRequired()
      && (!icVerificationSummary.value.hasSpouseFront || icVerificationSummary.value.backCount < 2)
    ) {
      validationErrors.value = [{
        stepIndex: 11,
        stepLabel: "Dokumen",
        messages: ["Sila lampirkan salinan MyKad depan dan belakang pasangan dalam dokumen sokongan."],
      }];
      return false;
    }

    return true;
  } finally {
    icSubmitVerifying.value = false;
  }
}

function addAttachmentFiles(files: File[]) {
  const validFiles: File[] = [];
  for (const file of files) {
    if (file.size <= 0) {
      toast.error(`${file.name} kosong atau tidak sah.`);
      continue;
    }
    if (!isAllowedPermohonanDocumentFile(file)) {
      toast.error(`${file.name}: format tidak disokong. Sila gunakan JPG, JPEG, PNG, PDF, DOC, atau DOCX.`);
      continue;
    }
    if (file.size > SPPT_DOCUMENT_MAX_BYTES) {
      toast.error(`${file.name} melebihi had ${SPPT_DOCUMENT_MAX_LABEL}.`);
      continue;
    }
    validFiles.push(file);
  }

  if (validFiles.length === 0) {
    return;
  }

  pendingClassificationFiles.value.push(...validFiles);
  void processClassificationQueue();
}

async function processClassificationQueue() {
  if (classModalOpen.value || pendingClassificationFiles.value.length === 0) {
    return;
  }

  const file = pendingClassificationFiles.value.shift();
  if (!file) {
    return;
  }

  classModalFile.value = file;
  classModalOpen.value = true;
  classModalClassifying.value = true;
  classModalSuggestedClass.value = "lain_lain";
  classModalSelectedClass.value = "lain_lain";
  classModalOtherLabel.value = "";
  classModalConfidence.value = 0;
  classModalMessage.value = "";

  try {
    const res = await classifyPermohonanDocument(
      file,
      form.value.noIcBaru,
      form.value.nama,
      spouseIcRequired() ? form.value.noIcPasangan : undefined,
      spouseIcRequired() ? form.value.namaPasangan : undefined,
    );
    const suggested = res.data.classification.suggestedClass as SpptDocumentClass;
    const normalized = normalizeDocumentClass(suggested) ?? "lain_lain";
    classModalSuggestedClass.value = normalized;
    classModalSelectedClass.value = normalized;
    classModalConfidence.value = res.data.classification.confidence;
    classModalMessage.value = res.data.classification.message;
  } catch {
    classModalSuggestedClass.value = "lain_lain";
    classModalSelectedClass.value = "lain_lain";
    classModalConfidence.value = 0;
    classModalMessage.value = "Klasifikasi AI gagal. Sila pilih jenis dokumen secara manual.";
    toast.error("Klasifikasi AI gagal. Sila pilih jenis dokumen.");
  } finally {
    classModalClassifying.value = false;
  }
}

function cancelClassModal() {
  classModalOpen.value = false;
  classModalFile.value = null;
  void processClassificationQueue();
}

async function confirmClassModal() {
  const file = classModalFile.value;
  if (!file || classModalClassifying.value) {
    return;
  }

  if (classModalSelectedClass.value === "lain_lain" && !classModalOtherLabel.value.trim()) {
    toast.error("Sila nyatakan jenis dokumen untuk Lain-Lain.");
    return;
  }

  const entry: PermohonanAttachment & { file?: File } = {
    id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
    name: file.name,
    size: file.size,
    url: "",
    file,
    documentClass: classModalSelectedClass.value,
    documentClassLabel: documentClassLabel(classModalSelectedClass.value, classModalOtherLabel.value),
    documentClassOther: classModalSelectedClass.value === "lain_lain"
      ? classModalOtherLabel.value.trim()
      : undefined,
  };
  attachments.value.push(entry);

  classModalOpen.value = false;
  classModalFile.value = null;

  if (draftId.value) {
    await uploadPendingAttachment(entry, draftId.value);
  }

  void processClassificationQueue();
}

async function updateAttachmentClass(item: PermohonanAttachment & { file?: File }) {
  if (!item.documentClass) {
    return;
  }

  if (item.documentClass === "lain_lain" && !item.documentClassOther?.trim()) {
    toast.error("Sila nyatakan jenis dokumen untuk Lain-Lain.");
    return;
  }

  item.documentClassLabel = documentClassLabel(item.documentClass, item.documentClassOther);

  if (item.url && draftId.value) {
    try {
      const res = await updatePermohonanDocumentClass(
        draftId.value,
        item.id,
        item.documentClass,
        item.documentClassOther,
      );
      const index = attachments.value.findIndex((a) => a.id === item.id);
      if (index >= 0) {
        attachments.value[index] = { ...attachments.value[index], ...res.data };
      }
    } catch {
      toast.error("Gagal mengemaskini jenis dokumen.");
    }
  }
}

function onAttachmentClassChange(item: PermohonanAttachment & { file?: File }) {
  if (item.documentClass !== "lain_lain") {
    item.documentClassOther = undefined;
  }
  void updateAttachmentClass(item);
}

const PERMOHONAN_DOC_EXT = /\.(pdf|doc|docx|jpe?g|png)$/i;
const PERMOHONAN_DOC_MIME = new Set([
  "application/pdf",
  "application/msword",
  "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  "image/jpeg",
  "image/jpg",
  "image/png",
  "image/pjpeg",
]);

function isAllowedPermohonanDocumentFile(file: File): boolean {
  if (PERMOHONAN_DOC_EXT.test(file.name)) {
    return true;
  }

  if (file.type && PERMOHONAN_DOC_MIME.has(file.type)) {
    return true;
  }

  return false;
}

async function uploadPendingAttachment(
  item: PermohonanAttachment & { file?: File },
  permohonanId: number | null = draftId.value,
): Promise<string | null> {
  if (!item.file || !permohonanId) {
    return null;
  }

  try {
    const res = await uploadPermohonanDocument(
      permohonanId,
      item.file,
      item.documentClass,
      item.documentClassOther,
    );
    const index = attachments.value.findIndex((a) => a.id === item.id);
    if (index >= 0) {
      attachments.value[index] = { ...res.data, documentClass: item.documentClass, documentClassOther: item.documentClassOther, documentClassLabel: item.documentClassLabel };
    }
    return null;
  } catch (err) {
    const apiErr = err as Error & { details?: Record<string, string[] | string> | null };
    const fileErrors = apiErr.details?.file;
    const detailMessage = Array.isArray(fileErrors) && fileErrors.length > 0
      ? fileErrors.join(", ")
      : err instanceof Error ? err.message : "Muat naik gagal";
    if (detailMessage) {
      toast.error(`Muat naik fail gagal: ${item.name}: ${detailMessage}`);
    }
    return detailMessage;
  }
}

function formatSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function onFileSelect(e: Event) {
  const input = e.target as HTMLInputElement;
  const files = input.files ? Array.from(input.files) : [];
  addAttachmentFiles(files);
  input.value = "";
}

async function removeAttachment(id: string) {
  const item = attachments.value.find((a) => a.id === id);
  if (!item) return;

  if (item.url && draftId.value) {
    try {
      await deletePermohonanDocument(draftId.value, id);
    } catch {
      toast.error("Gagal memadam lampiran.");
      return;
    }
  }

  attachments.value = attachments.value.filter((a) => a.id !== id);
}

function onDragOver(e: DragEvent) {
  e.preventDefault();
  isDragOver.value = true;
}

function onDragLeave(e: DragEvent) {
  e.preventDefault();
  isDragOver.value = false;
}

function onDrop(e: DragEvent) {
  e.preventDefault();
  isDragOver.value = false;
  const files = e.dataTransfer?.files ? Array.from(e.dataTransfer.files) : [];
  addAttachmentFiles(files);
}

function triggerFileInput() {
  fileInputRef.value?.click();
}

function onNoIcBaruInput() {
  const parsed = parseMalaysianIcBirthDate(form.value.noIcBaru);
  if (!parsed) return;

  form.value.tarikhLahirHari = String(parsed.day);
  form.value.tarikhLahirBulan = String(parsed.month);
  form.value.tarikhLahirTahun = String(parsed.year);
  form.value.umur = String(parsed.age);
}

const tarikhLahir = computed({
  get: () =>
    isoFromBirthParts(
      form.value.tarikhLahirHari,
      form.value.tarikhLahirBulan,
      form.value.tarikhLahirTahun,
    ),
  set: (iso: string) => {
    const parts = birthPartsFromIso(iso);
    if (!parts) {
      form.value.tarikhLahirHari = "";
      form.value.tarikhLahirBulan = "";
      form.value.tarikhLahirTahun = "";
      form.value.umur = "";
      return;
    }

    form.value.tarikhLahirHari = parts.day;
    form.value.tarikhLahirBulan = parts.month;
    form.value.tarikhLahirTahun = parts.year;

    const age = ageFromIsoDate(iso);
    if (age !== null) {
      form.value.umur = String(age);
    }
  },
});

const adaPasangan = ref(true);

const KATEGORI_PEMBIAYAAN_OPTIONS = [
  "TEKUN Niaga",
  "TEMAN TEKUN",
  "Kontrak",
  "SPUMI",
  "BPU",
  "TEKUN Corp",
  "Lain-lain",
] as const;

/** Rujukan: FAQ TEKUN Nasional – dokumen permohonan pembiayaan */
const DOKUMEN_SOKONGAN_GUIDE = [
  "Salinan Kad Pengenalan pemohon dan pasangan (jika berkenaan) — depan dan belakang",
  "Lesen / Permit daripada PBT atau Daftar Perniagaan (SSM)",
  "Atau pengesahan menjalankan perniagaan daripada Pengerusi Majlis Pembangunan Usahawan Dan Koperasi Parlimen (MPUKP), Jawatankuasa Pembangunan dan Keselamatan Kampung (JPKK), Jawatankuasa Pembangunan dan Keselamatan Kampung Persekutuan (JPKKP), Penghulu / Ketua Kaum (Sabah & Sarawak), atau kupon / permit perniagaan oleh Jawatankuasa Penganjur bagi perniagaan pasar malam, pasar tani atau pasar tamu",
  "Gambar perniagaan pemohon yang menunjukkan aktiviti perniagaan yang sedang dijalankan",
  "Salinan 3 bulan transaksi terkini Penyata Bank akaun Simpanan / Semasa yang mengandungi nama / syarikat pemohon, nama bank dan nombor akaun bank",
  "Dokumen tambahan (jika berkenaan)",
] as const;

const STEPS = [
  { id: "asas", label: "Asas" },
  { id: "pemohon", label: "Pemohon" },
  { id: "alamat", label: "Alamat" },
  { id: "pekerjaan", label: "Pekerjaan" },
  { id: "pasangan", label: "Pasangan" },
  { id: "perniagaan", label: "Perniagaan" },
  { id: "pembiayaan", label: "Pembiayaan" },
  { id: "sokongan", label: "Sokongan" },
  { id: "takaful", label: "Takaful" },
  { id: "wasiat", label: "Wasiat" },
  { id: "kebenaran", label: "Kebenaran" },
  { id: "dokumen", label: "Dokumen" },
] as const;

const currentStep = ref(0);
const totalSteps = STEPS.length;
const validationErrors = ref<{ stepIndex: number; stepLabel: string; messages: string[] }[]>([]);

type ApiValidationError = Error & {
  details?: Record<string, string[] | string> | null;
};

function collectApiValidationMessages(details: Record<string, unknown> | null | undefined): string[] {
  if (!details) {
    return [];
  }

  const messages: string[] = [];
  const nested = details.details;
  if (nested && typeof nested === "object" && !Array.isArray(nested)) {
    for (const value of Object.values(nested as Record<string, unknown>)) {
      if (Array.isArray(value)) {
        messages.push(...value.map(String));
      } else if (value) {
        messages.push(String(value));
      }
    }
  }

  for (const [key, value] of Object.entries(details)) {
    if (key === "details") {
      continue;
    }
    const normalizedKey = key.toLowerCase();
    if (
      normalizedKey.includes("noic")
      || normalizedKey.includes("notelefon")
      || normalizedKey.includes("email")
    ) {
      if (Array.isArray(value)) {
        messages.push(...value.map(String));
      } else if (value) {
        messages.push(String(value));
      }
    }
  }

  return messages;
}

function applyDuplicateValidationErrors(err: ApiValidationError): boolean {
  const messages = collectApiValidationMessages(err.details as Record<string, unknown> | null | undefined);
  if (messages.length === 0) {
    return false;
  }

  const stepErrors: { stepIndex: number; stepLabel: string; messages: string[] }[] = [];
  const pemohonMsgs = messages.filter((message) => message.includes("Kad Pengenalan"));
  const contactMsgs = messages.filter((message) => message.includes("telefon") || message.includes("E-mel"));

  if (pemohonMsgs.length > 0) {
    stepErrors.push({ stepIndex: 1, stepLabel: "Pemohon", messages: pemohonMsgs });
  }
  if (contactMsgs.length > 0) {
    stepErrors.push({ stepIndex: 2, stepLabel: "Alamat", messages: contactMsgs });
  }
  if (stepErrors.length === 0) {
    stepErrors.push({ stepIndex: 1, stepLabel: "Pemohon", messages });
  }

  validationErrors.value = stepErrors;
  currentStep.value = stepErrors[0]?.stepIndex ?? currentStep.value;
  toast.error(messages.join(" "));
  return true;
}

function applyApiValidationErrors(err: ApiValidationError): boolean {
  if (applyDuplicateValidationErrors(err)) {
    return true;
  }

  const messages = collectApiValidationMessages(err.details as Record<string, unknown> | null | undefined);
  if (messages.length > 0) {
    validationErrors.value = [{
      stepIndex: currentStep.value,
      stepLabel: STEPS[currentStep.value]?.label ?? "Borang",
      messages,
    }];
    toast.error(messages.join(" "));
    return true;
  }

  if (err.message && err.message !== "Request failed" && err.message !== "Validation failed") {
    toast.error(err.message);
    return true;
  }

  return false;
}

async function applyHardRuleGate(): Promise<boolean> {
  hardRuleChecking.value = true;
  try {
    const result = await evaluateHardRulesForPermohonanForm(form.value);
    if (result && !result.eligible) {
      validationErrors.value = [{
        stepIndex: currentStep.value,
        stepLabel: "Saringan Auto-Kelayakan",
        messages: result.reasons,
      }];
      toast.error("Syarat mandatori tidak dipenuhi. Permohonan tidak boleh diteruskan (auto-reject).");
      return false;
    }
    return true;
  } finally {
    hardRuleChecking.value = false;
  }
}

async function goNext() {
  if (currentStep.value >= totalSteps - 1 || draftSaving.value || hardRuleChecking.value) {
    return;
  }

  if (!(await applyHardRuleGate())) {
    return;
  }

  currentStep.value += 1;
  validationErrors.value = [];

  const draftSaved = await saveDraft({ silent: true });
  if (!draftSaved) {
    if (validationErrors.value.length > 0) {
      currentStep.value = validationErrors.value[0]?.stepIndex ?? currentStep.value;
    } else {
      currentStep.value -= 1;
    }
  }
}

function goPrev() {
  if (currentStep.value > 0) {
    currentStep.value -= 1;
    validationErrors.value = [];
  }
}

async function goToStep(index: number) {
  if (index < 0 || index >= totalSteps || index === currentStep.value || draftSaving.value || hardRuleChecking.value) {
    return;
  }

  if (index > currentStep.value && !(await applyHardRuleGate())) {
    return;
  }

  const previousStep = currentStep.value;
  currentStep.value = index;
  validationErrors.value = [];

  const draftSaved = await saveDraft({ silent: true });
  if (!draftSaved) {
    if (validationErrors.value.length > 0) {
      currentStep.value = validationErrors.value[0]?.stepIndex ?? currentStep.value;
    } else {
      currentStep.value = previousStep;
    }
  }
}

const form = ref({
  // Maklumat Asas (BPP-BORANG-01)
  kategoriPembiayaan: "TEKUN Niaga",
  statusPerniagaan: "sedang_berniaga",
  sektorPerniagaan: "Peruncitan",
  kaedahPerniagaan: "offline" as "online" | "offline",
  namaBank: "",
  noAkaunBank: "",

  // Maklumat Pemohon
  noUsahawan: "",
  nama: "",
  noIcBaru: "",
  noIcLama: "",
  jantina: "L",
  agama: "",
  tarikhLahirHari: "",
  tarikhLahirBulan: "1",
  tarikhLahirTahun: "",
  bangsa: "",
  kaum: "",
  umur: "",
  tarafPerkahwinan: "",
  bilanganTanggungan: "",
  oku: false,
  diberhentikanPandemik: false,
  asnafBerdaftar: false,
  tarafPendidikan: "",

  // Alamat Kediaman
  alamat: "",
  poskod: "",
  negeri: "",
  noTelefonRumah: "",
  noTelefonBimbit: "",
  email: "",
  facebook: "",
  instagram: "",
  statusKediaman: "",

  // Pekerjaan Sekarang
  statusPekerjaan: "tidak_bekerja" as "bekerja" | "tidak_bekerja",
  sektorPekerjaan: "",
  jawatan: "",
  statusJawatan: "",
  pekerjaanSekarang: "",
  pendapatan: "",
  pendapatanBulan: "1",
  namaMajikan: "",
  alamatMajikan: "",
  noTelefonMajikan: "",

  // Maklumat Pasangan (jika berkenaan)
  namaPasangan: "",
  noIcPasangan: "",
  noPassportPasangan: "",
  pekerjaanPasangan: "",
  alamatMajikanPasangan: "",
  poskodMajikanPasangan: "",
  noTelefonMajikanPasangan: "",
  noTelefonBimbitPasangan: "",
  pendapatanPasangan: "",
  pendapatanPasanganBulan: "1",

  // Pembiayaan
  jumlahPermohonan: "",
  tempohPembiayaan: "",
  kekerapanBayaran: "Bulanan",
  tujuan: "",

  // F: Maklumat Perniagaan
  namaPerniagaan: "",
  noSsm: "",
  tempohBerniaga: "",
  alamatPremis: "",
  poskodPremis: "",
  anggaranPendapatan: "",
  noTelPremis: "",
  noTelBimbitPremis: "",
  statusPremis: "Sewa",
  pemilikanPerniagaan: "Pemilikan Tunggal",
  modalBerbayar: "",
  pemegangSaham: false,
  tarikhDaftar: "",
  tarikhTamatLesen: "",
  keahlianPersatuan: false,
  keahlianPersatuanNyata: "",
  pengesahanNama: "",
  pengesahanAlamat: "",
  pengesahanNoTel: "",
  masaBerniagaDari: "08:00",
  masaBerniagaHingga: "18:00",
  pengiktirafan: false,
  pengiktirafanNyata: "",
  nilaiAset: "",
  sumberModal: "",
  kursusAgensi: "",
  kursusLain: "",
  perniagaanTerdahulu: "",
  pembiayaanSediaAda: false,
  institusiPembiayaan: "",
  jumlahPembiayaanSediaAda: "",
  bakiPembiayaan: "",

  // G: Sokongan Kumpulan (TEMAN TEKUN)
  sokonganNama: "",
  sokonganNoKp: "",
  sokonganTempohPerkenalan: "",
  sokonganTarikhPerbincangan: "",
  sokonganJumlah: "",
  perakuanJawatan: "",
  perakuanNama: "",
  perakuanAlamat: "",
  perakuanNoTel: "",
  perujuk1Nama: "",
  perujuk1NoKp: "",
  perujuk1Alamat: "",
  perujuk1Hubungan: "",
  perujuk1NoTel: "",
  perujuk2Nama: "",
  perujuk2NoKp: "",
  perujuk2Alamat: "",
  perujuk2Hubungan: "",
  perujuk2NoTel: "",

  // H: Takaful & PERKESO
  takafulPembiayaan: true,
  takafulKemalangan: false,
  takafulKemalanganPakej: "",
  perkeso: false,
  perkesoPakej: "",
  perkesoSektor: "",
  perkesoKelas: "",

  // I: Wasiat
  wasiat: false,
  wasiatJumlah: "",
  wasiatNamaSyarikat: "",

  // J: Kebenaran Penzahiran Maklumat Kredit
  kebenaranKredit: false,
});

function normalizeStatusPekerjaan(value: unknown): "bekerja" | "tidak_bekerja" | "" {
  const normalized = String(value ?? "").trim().toLowerCase();
  if (normalized === "bekerja") return "bekerja";
  if (normalized === "tidak_bekerja") return "tidak_bekerja";
  return "";
}

function normalizeDropdownFields() {
  const f = form.value;
  f.sektorPekerjaan = matchSelectOption(f.sektorPekerjaan, SEKTOR_PEKERJAAN_OPTIONS);
  f.statusJawatan = matchSelectOption(f.statusJawatan, STATUS_JAWATAN_OPTIONS);
  f.sektorPerniagaan = matchSelectOption(f.sektorPerniagaan, SEKTOR_PERNIAGAAN_OPTIONS);
  f.bangsa = matchSelectOption(f.bangsa, BANGSA_OPTIONS);
  f.tarafPerkahwinan = matchSelectOption(f.tarafPerkahwinan, TARAF_PERKAHWINAN_OPTIONS);
  f.tarafPendidikan = matchSelectOption(f.tarafPendidikan, TARAF_PENDIDIKAN_OPTIONS);
  f.negeri = matchSelectOption(f.negeri, NEGERI_OPTIONS);
  f.statusKediaman = matchSelectOption(f.statusKediaman, STATUS_KEDIAMAN_OPTIONS);
  f.statusPremis = matchSelectOption(f.statusPremis, STATUS_PREMIS_OPTIONS);
  f.pemilikanPerniagaan = matchSelectOption(f.pemilikanPerniagaan, PEMILIKAN_PERNIAGAAN_OPTIONS);
  f.kursusAgensi = matchSelectOption(f.kursusAgensi, AGENSI_KURSUS_OPTIONS);
  f.institusiPembiayaan = matchSelectOption(f.institusiPembiayaan, INSTITUSI_PEMBIAYAAN_OPTIONS);
  f.kekerapanBayaran = matchSelectOption(f.kekerapanBayaran, KEEKERAPAN_BAYARAN_OPTIONS);
  f.namaBank = matchSelectOption(f.namaBank, BANK_OPERASI_OPTIONS);
}

function normalizeDateFields() {
  const f = form.value;
  f.tarikhDaftar = toHtmlDateValue(f.tarikhDaftar);
  f.tarikhTamatLesen = toHtmlDateValue(f.tarikhTamatLesen);
  f.sokonganTarikhPerbincangan = toHtmlDateValue(f.sokonganTarikhPerbincangan);
}

function normalizeTimeFields() {
  const f = form.value;
  f.masaBerniagaDari = toHtmlTimeValue(f.masaBerniagaDari);
  f.masaBerniagaHingga = toHtmlTimeValue(f.masaBerniagaHingga);
}

function validateForm(): boolean {
  const f = form.value;
  const errors: { stepIndex: number; stepLabel: string; messages: string[] }[] = [];

  // Step 0: Asas
  const asasMsgs: string[] = [];
  if (!f.namaBank?.trim()) asasMsgs.push("Nama Bank Operasi Perniagaan diperlukan");
  if (!f.noAkaunBank?.trim()) asasMsgs.push("No. Akaun Bank diperlukan");
  if (asasMsgs.length) errors.push({ stepIndex: 0, stepLabel: "Asas", messages: asasMsgs });

  // Step 1: Pemohon
  const pemohonMsgs: string[] = [];
  if (!f.nama?.trim()) pemohonMsgs.push("Nama Pemohon diperlukan");
  if (!f.noIcBaru?.trim()) pemohonMsgs.push("No. Kad Pengenalan (Baru) diperlukan");
  if (!f.jantina) pemohonMsgs.push("Jantina diperlukan");
  if (!f.agama) pemohonMsgs.push("Agama diperlukan");
  if (!f.tarafPerkahwinan?.trim()) pemohonMsgs.push("Taraf Perkahwinan diperlukan");
  if (!f.tarafPendidikan?.trim()) pemohonMsgs.push("Taraf Pendidikan diperlukan");
  if (!f.bangsa?.trim()) pemohonMsgs.push("Bangsa diperlukan");
  if (f.bangsa === "Lain-lain" && !f.kaum?.trim()) pemohonMsgs.push("Bangsa Lain diperlukan");
  if (pemohonMsgs.length) errors.push({ stepIndex: 1, stepLabel: "Pemohon", messages: pemohonMsgs });

  // Step 2: Alamat
  const alamatMsgs: string[] = [];
  if (!f.alamat?.trim()) alamatMsgs.push("Alamat Kediaman diperlukan");
  if (!f.poskod?.trim()) alamatMsgs.push("Poskod diperlukan");
  if (!f.negeri?.trim()) alamatMsgs.push("Negeri diperlukan");
  if (!f.noTelefonBimbit?.trim()) alamatMsgs.push("No. Telefon Bimbit diperlukan");
  if (!f.email?.trim()) alamatMsgs.push("E-mel diperlukan");
  if (alamatMsgs.length) errors.push({ stepIndex: 2, stepLabel: "Alamat", messages: alamatMsgs });

  // Step 3: Pekerjaan
  const pekerjaanMsgs: string[] = [];
  const statusPekerjaan = normalizeStatusPekerjaan(f.statusPekerjaan);
  if (!statusPekerjaan) pekerjaanMsgs.push("Status Pekerjaan diperlukan");
  if (statusPekerjaan === "bekerja") {
    if (!f.sektorPekerjaan?.trim()) pekerjaanMsgs.push("Sektor Pekerjaan diperlukan");
    if (!f.pendapatan?.trim() || Number(f.pendapatan) < 0) pekerjaanMsgs.push("Pendapatan diperlukan");
  }
  if (pekerjaanMsgs.length) errors.push({ stepIndex: 3, stepLabel: "Pekerjaan", messages: pekerjaanMsgs });

  // Step 4: Pasangan (jika ada)
  if (adaPasangan.value) {
    const pasanganMsgs: string[] = [];
    if (!f.namaPasangan?.trim()) pasanganMsgs.push("Nama Pasangan diperlukan");
    if (!f.noIcPasangan?.trim()) pasanganMsgs.push("No. Kad Pengenalan Pasangan diperlukan");
    if (pasanganMsgs.length) errors.push({ stepIndex: 4, stepLabel: "Pasangan", messages: pasanganMsgs });
  }

  // Step 5: Perniagaan (F)
  const perniagaanMsgs: string[] = [];
  if (!f.namaPerniagaan?.trim()) perniagaanMsgs.push("Nama Perniagaan diperlukan");
  if (perniagaanMsgs.length) errors.push({ stepIndex: 5, stepLabel: "Perniagaan", messages: perniagaanMsgs });

  // Step 6: Pembiayaan
  const pembiayaanMsgs: string[] = [];
  if (!f.jumlahPermohonan?.trim() || Number(f.jumlahPermohonan) <= 0) pembiayaanMsgs.push("Jumlah Permohonan diperlukan");
  if (!f.tempohPembiayaan?.trim() || Number(f.tempohPembiayaan) <= 0) pembiayaanMsgs.push("Tempoh Pembiayaan diperlukan");
  if (!f.tujuan?.trim()) pembiayaanMsgs.push("Tujuan Pembiayaan diperlukan");
  if (pembiayaanMsgs.length) errors.push({ stepIndex: 6, stepLabel: "Pembiayaan", messages: pembiayaanMsgs });

  // Step 7: Sokongan (G) - required if TEMAN TEKUN
  if (f.kategoriPembiayaan === "TEMAN TEKUN") {
    const sokonganMsgs: string[] = [];
    if (!f.sokonganNama?.trim()) sokonganMsgs.push("Nama Sokongan diperlukan");
    if (!f.sokonganNoKp?.trim()) sokonganMsgs.push("No. KP Sokongan diperlukan");
    if (!f.sokonganJumlah?.trim()) sokonganMsgs.push("Jumlah Pembiayaan Disokong diperlukan");
    if (sokonganMsgs.length) errors.push({ stepIndex: 7, stepLabel: "Sokongan", messages: sokonganMsgs });
  }

  // Step 8: Takaful (H)
  const takafulMsgs: string[] = [];
  if (!f.takafulPembiayaan) takafulMsgs.push("Takaful Pembiayaan Berkelompok adalah wajib");
  if (takafulMsgs.length) errors.push({ stepIndex: 8, stepLabel: "Takaful", messages: takafulMsgs });

  // Step 9: Wasiat (I) - optional

  // Step 10: Kebenaran (J)
  const kebenaranMsgs: string[] = [];
  if (!f.kebenaranKredit) kebenaranMsgs.push("Kebenaran Penzahiran Maklumat Kredit diperlukan");
  if (kebenaranMsgs.length) errors.push({ stepIndex: 10, stepLabel: "Kebenaran", messages: kebenaranMsgs });

  // Step 11: Dokumen Sokongan
  const dokumenMsgs: string[] = [];
  if (attachments.value.length === 0) {
    dokumenMsgs.push("Sila lampirkan dokumen sokongan");
  } else {
    const missingClass = attachments.value.some((item) => !item.documentClass);
    if (missingClass) {
      dokumenMsgs.push("Sila sahkan jenis dokumen untuk setiap lampiran");
    }
    const missingOther = attachments.value.some(
      (item) => item.documentClass === "lain_lain" && !item.documentClassOther?.trim(),
    );
    if (missingOther) {
      dokumenMsgs.push("Sila nyatakan jenis dokumen untuk pilihan Lain-Lain");
    }
  }
  if (dokumenMsgs.length) errors.push({ stepIndex: 11, stepLabel: "Dokumen", messages: dokumenMsgs });

  validationErrors.value = errors.sort((a, b) => a.stepIndex - b.stepIndex);
  return errors.length === 0;
}

function buildPayload(status: "Draf" | "Dalam Proses"): PermohonanInput {
  const normalizedForm = uppercaseFormFields(form.value);
  form.value = normalizedForm;
  normalizeDropdownFields();
  normalizeDateFields();
  const f = form.value;
  const jumlah = f.jumlahPermohonan ? Number(f.jumlahPermohonan) : undefined;
  const statusPekerjaan = normalizeStatusPekerjaan(f.statusPekerjaan);
  f.statusPekerjaan = statusPekerjaan || f.statusPekerjaan;

  if (statusPekerjaan === "bekerja") {
    f.pekerjaanSekarang = f.jawatan?.trim() || f.pekerjaanSekarang?.trim() || "";
  } else if (statusPekerjaan === "tidak_bekerja") {
    f.statusPekerjaan = "tidak_bekerja";
    f.pekerjaanSekarang = "Tidak Bekerja";
    f.sektorPekerjaan = "";
    f.jawatan = "";
    f.statusJawatan = "";
    f.pendapatan = "";
    f.pendapatanBulan = "1";
    f.namaMajikan = "";
    f.alamatMajikan = "";
    f.noTelefonMajikan = "";
  }

  return {
    nama: f.nama?.trim() || "Draf",
    kategoriPembiayaan: f.kategoriPembiayaan,
    status,
    jumlahPermohonan: jumlah,
    details: {
      ...f,
      adaPasangan: adaPasangan.value,
      currentStep: currentStep.value,
    },
  };
}

async function syncPendingAttachments(permohonanId: number): Promise<string[]> {
  const pending = attachments.value.filter((item) => item.file);
  const failures: string[] = [];
  if (pending.length === 0) {
    return failures;
  }

  for (const item of pending) {
    const detailMessage = await uploadPendingAttachment(item, permohonanId);
    if (detailMessage) {
      failures.push(`${item.name}: ${detailMessage}`);
    }
  }

  return failures;
}

function restoreAttachmentsFromDetails(details: Record<string, unknown>) {
  const pendingLocal = attachments.value.filter((item) => item.file && !item.url);
  const saved = details.attachments;
  let savedAttachments: (PermohonanAttachment & { file?: File })[] = [];

  if (Array.isArray(saved) && saved.length > 0) {
    savedAttachments = saved
      .filter((item): item is PermohonanAttachment => typeof item === "object" && item !== null && "name" in item)
      .map((item) => {
        const raw = item as PermohonanAttachment & { document_class?: string; document_class_other?: string; document_class_label?: string };
        const documentClassValue = raw.documentClass ?? raw.document_class;
        const documentClassOtherValue = raw.documentClassOther ?? raw.document_class_other;
        const normalizedClass = normalizeDocumentClass(documentClassValue ? String(documentClassValue) : undefined);

        return {
          id: String(item.id ?? ""),
          name: String(item.name ?? ""),
          size: Number(item.size ?? 0),
          url: String(item.url ?? ""),
          mimeType: item.mimeType ? String(item.mimeType) : undefined,
          documentClass: normalizedClass ?? "lain_lain",
          documentClassLabel: raw.documentClassLabel ?? raw.document_class_label
            ? String(raw.documentClassLabel ?? raw.document_class_label)
            : documentClassLabel(normalizedClass ?? "lain_lain", documentClassOtherValue ? String(documentClassOtherValue) : undefined),
          documentClassOther: documentClassOtherValue ? String(documentClassOtherValue) : undefined,
          verification: item.verification,
        };
      });
  }

  const savedIds = new Set(savedAttachments.map((item) => item.id));
  attachments.value = [
    ...savedAttachments,
    ...pendingLocal.filter((item) => !savedIds.has(item.id)),
  ];
}

async function syncAttachmentClasses(permohonanId: number): Promise<void> {
  for (const item of attachments.value) {
    if (!item.url || !item.documentClass) {
      continue;
    }

    try {
      const res = await updatePermohonanDocumentClass(
        permohonanId,
        item.id,
        item.documentClass,
        item.documentClassOther,
      );
      const index = attachments.value.findIndex((a) => a.id === item.id);
      if (index >= 0) {
        attachments.value[index] = { ...attachments.value[index], ...res.data };
      }
    } catch {
      // Best-effort; upload/save should still proceed.
    }
  }
}

async function persistPermohonan(status: "Draf" | "Dalam Proses") {
  let data;
  let uploadFailures: string[] = [];

  if (draftId.value) {
    uploadFailures = await syncPendingAttachments(draftId.value);
    await syncAttachmentClasses(draftId.value);
    const res = await updatePermohonan(draftId.value, buildPayload(status));
    data = res.data;
  } else {
    const createPayload = buildPayload(status === "Dalam Proses" ? "Draf" : status);
    const res = await createPermohonan(createPayload);
    draftId.value = res.data.id;
    noRujukan.value = res.data.noRujukan ?? "";
    router.replace({ name: "permohonan-baru", params: { id: String(res.data.id) } });
    data = res.data;
    uploadFailures = await syncPendingAttachments(data.id);
    await syncAttachmentClasses(data.id);
    if (status === "Dalam Proses") {
      if (uploadFailures.length > 0) {
        const err = new Error(uploadFailures.join("; ")) as Error & { uploadFailures?: string[] };
        err.uploadFailures = uploadFailures;
        throw err;
      }
      const submitRes = await updatePermohonan(draftId.value, buildPayload(status));
      data = submitRes.data;
    }
  }

  const refreshed = await getPermohonan(data.id);
  restoreAttachmentsFromDetails((refreshed.data.details ?? {}) as Record<string, unknown>);

  if (uploadFailures.length > 0) {
    const err = new Error(uploadFailures.join("; ")) as Error & { uploadFailures?: string[] };
    err.uploadFailures = uploadFailures;
    throw err;
  }

  return refreshed.data;
}

async function saveDraft(options?: { silent?: boolean }): Promise<boolean> {
  draftSaving.value = true;
  saved.value = false;
  validationErrors.value = [];
  try {
    const data = await persistPermohonan("Draf");
    noRujukan.value = data.noRujukan ?? noRujukan.value;
    if (!options?.silent) {
      toast.success("Draf permohonan berjaya disimpan.");
    }
    return true;
  } catch (err) {
    const apiErr = err as ApiValidationError;
    if (applyApiValidationErrors(apiErr)) {
      return false;
    }
    const uploadFailures = (err as Error & { uploadFailures?: string[] }).uploadFailures;
    if (uploadFailures?.length) {
      toast.error(`Muat naik fail gagal: ${uploadFailures.join("; ")}`);
    } else {
      toast.error("Gagal menyimpan draf permohonan.");
    }
    return false;
  } finally {
    draftSaving.value = false;
  }
}

async function simpanDraf() {
  await saveDraft();
}

async function simpan() {
  const draftSaved = await saveDraft({ silent: true });
  if (!draftSaved) {
    return;
  }
  if (!(await applyHardRuleGate())) {
    return;
  }
  if (!validateForm()) {
    currentStep.value = validationErrors.value[0]?.stepIndex ?? currentStep.value;
    return;
  }
  if (!(await verifyLocalAttachmentsForSubmit())) {
    currentStep.value = 11;
    return;
  }
  saving.value = true;
  saved.value = false;
  try {
    const data = await persistPermohonan("Dalam Proses");
    saved.value = true;
    toast.success(`Permohonan ${data.noRujukan ?? ""} berjaya dihantar.`);
    setTimeout(() => router.push("/admin/permohonan"), 1500);
  } catch (err) {
    const apiErr = err as ApiValidationError;
    if (applyApiValidationErrors(apiErr)) {
      return;
    }
    const attachmentErrors = apiErr.details?.attachments;
    if (Array.isArray(attachmentErrors) && attachmentErrors.length > 0) {
      validationErrors.value = [{
        stepIndex: 11,
        stepLabel: "Dokumen",
        messages: attachmentErrors.map(String),
      }];
      currentStep.value = 11;
    }
    toast.error("Gagal menghantar permohonan. Sila semak maklumat dan cuba lagi.");
  } finally {
    saving.value = false;
  }
}

async function loadDraft(id: number) {
  loading.value = true;
  try {
    const res = await getPermohonan(id);
    const row = res.data;
    if (row.status !== "Draf") {
      toast.error("Permohonan ini bukan draf dan tidak boleh diedit di sini.");
      router.push(`/admin/permohonan/${id}`);
      return;
    }

    draftId.value = row.id;
    noRujukan.value = row.noRujukan ?? "";

    const details = (row.details ?? {}) as Record<string, unknown>;
    const { adaPasangan: savedPasangan, currentStep: savedStep, attachmentNames, attachments: _savedAttachments, ...formFields } = details;

    if (savedPasangan !== undefined) {
      adaPasangan.value = Boolean(savedPasangan);
    }
    if (typeof savedStep === "number") {
      currentStep.value = savedStep;
    }

    for (const [key, value] of Object.entries(formFields)) {
      if (key in form.value && value !== undefined && value !== null) {
        (form.value as Record<string, unknown>)[key] = value;
      }
    }

    if (row.kategoriPembiayaan) {
      form.value.kategoriPembiayaan = row.kategoriPembiayaan;
    }
    if (row.nama) {
      form.value.nama = row.nama;
    }
    if (row.jumlahPermohonan) {
      form.value.jumlahPermohonan = String(row.jumlahPermohonan);
    }

    normalizeDropdownFields();
    normalizeDateFields();
    normalizeTimeFields();
    const normalizedStatus = normalizeStatusPekerjaan(form.value.statusPekerjaan);
    if (normalizedStatus) {
      form.value.statusPekerjaan = normalizedStatus;
    }
    migrateLegacyPekerjaanFields(formFields);
    restoreAttachmentsFromDetails(details);
  } catch {
    toast.error("Gagal memuatkan draf permohonan.");
    router.push("/admin/permohonan");
  } finally {
    loading.value = false;
  }
}

function migrateLegacyPekerjaanFields(savedDetails: Record<string, unknown>) {
  if ("statusPekerjaan" in savedDetails) {
    return;
  }

  const f = form.value;
  const pekerjaan = f.pekerjaanSekarang?.trim().toLowerCase() ?? "";
  if (!pekerjaan || pekerjaan === "tidak bekerja") {
    f.statusPekerjaan = "tidak_bekerja";
    return;
  }

  f.statusPekerjaan = "bekerja";
  if (!f.jawatan?.trim()) {
    f.jawatan = f.pekerjaanSekarang;
  }
}

watch(
  () => form.value.bangsa,
  (bangsa) => {
    if (bangsa !== "Lain-lain") {
      form.value.kaum = "";
    }
  },
);

onMounted(() => {
  const id = route.params.id;
  if (id && typeof id === "string") {
    loadDraft(Number(id));
  }
});

const stepsWithErrors = computed(() =>
  validationErrors.value.map((e) => e.stepIndex),
);

function batal() {
  router.push("/admin/permohonan");
}

async function openAttachment(item: PermohonanAttachment) {
  if (!draftId.value || !item.url) {
    return;
  }

  try {
    await openPermohonanDocument(draftId.value, item.id);
  } catch {
    toast.error("Gagal membuka lampiran.");
  }
}

const inputClass =
  "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200";
const labelClass = "mb-1 block text-xs font-medium text-slate-600";
const readonlyClass =
  "w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-500";
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-4xl space-y-4">
      <SpptPageHeader
        :title="draftId ? 'Kemaskini Draf Permohonan' : 'Daftar Permohonan Baru'"
        :breadcrumb="[
          { label: 'Permohonan', to: '/admin/permohonan' },
          { label: 'Pendaftaran Permohonan', to: '/admin/permohonan' },
          { label: draftId ? 'Kemaskini Draf' : 'Daftar Baru' },
        ]"
      />

      <div
        v-if="officerOffice"
        class="flex flex-wrap items-center gap-x-6 gap-y-1 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
      >
        <span class="font-medium text-slate-900">Pejabat Pegawai:</span>
        <span><span class="text-slate-500">Negeri:</span> {{ officerOffice.negeri }}</span>
        <span><span class="text-slate-500">Cawangan:</span> {{ officerOffice.cawangan }}</span>
      </div>
      <p v-else class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Akaun anda belum ditetapkan dengan cawangan. Negeri dan cawangan permohonan tidak akan direkod sehingga tetapan pengguna dikemaskini.
      </p>

      <!-- Spec 1.7.2: AI-OCR auto-populate from filled BPP-BORANG-01 -->
      <article class="rounded-lg border border-blue-200 bg-gradient-to-br from-blue-50/80 to-white shadow-sm">
        <div class="border-b border-blue-100 px-4 py-3">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-blue-900">
            <Scan class="h-4 w-4" />
            AI-OCR Borang Permohonan (Spec 1.7.2)
          </h2>
          <p class="mt-1 text-xs text-blue-800/80">
            Muat naik borang BPP-BORANG-01 yang telah diisi (PDF atau imej) untuk auto-populate medan borang di bawah.
          </p>
        </div>
        <div class="space-y-3 p-4">
          <label
            class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-blue-300 bg-white px-4 py-5 text-sm text-blue-800 transition-colors hover:border-blue-400 hover:bg-blue-50/50"
            :class="{ 'pointer-events-none opacity-60': ocrProcessing }"
          >
            <Upload class="h-5 w-5 shrink-0" />
            <span v-if="ocrProcessing">Memproses AI-OCR… (1–5 minit untuk borang penuh, sila tunggu)</span>
            <span v-else>Klik untuk muat naik borang permohonan (PDF / JPG / PNG)</span>
            <input
              ref="ocrInputRef"
              type="file"
              class="hidden"
              accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
              :disabled="ocrProcessing"
              @change="onOcrFileSelect"
            />
          </label>

          <div
            v-if="ocrResult && ocrResult.populatedCount > 0"
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm text-emerald-900"
          >
            <p class="font-medium">
              {{ ocrResult.message }}
            </p>
            <p v-if="ocrFileName" class="mt-1 text-xs text-emerald-700">
              Fail: {{ ocrFileName }}
            </p>
            <ul class="mt-2 grid gap-1 text-xs sm:grid-cols-2">
              <li v-for="item in ocrPreviewFields" :key="item.key" class="truncate">
                <span class="text-emerald-700">{{ item.label }}:</span>
                {{ item.value }}
                <span v-if="item.confidence" class="text-emerald-600">({{ item.confidence }}%)</span>
              </li>
            </ul>
            <p v-if="ocrResult.populatedCount > 12" class="mt-1 text-xs text-emerald-700">
              + {{ ocrResult.populatedCount - 12 }} medan lagi diekstrak
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
              <button
                type="button"
                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                @click="applyOcrToForm(false)"
              >
                Isi Medan Kosong
              </button>
              <button
                type="button"
                class="rounded-lg border border-emerald-600 bg-white px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100"
                @click="applyOcrToForm(true)"
              >
                Ganti Semua Medan
              </button>
              <button
                type="button"
                class="rounded-lg px-3 py-1.5 text-xs text-emerald-700 hover:underline"
                @click="ocrResult = null"
              >
                Batal
              </button>
            </div>
          </div>
        </div>
      </article>

      <div v-if="loading" class="rounded-lg border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500 shadow-sm">
        Memuatkan draf permohonan...
      </div>

      <form v-else class="space-y-6" @submit.prevent="simpan" @input="uppercaseTextInput">
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <SpptStepper
            :steps="[...STEPS]"
            :current-step="currentStep"
            :steps-with-errors="stepsWithErrors"
            :disabled="draftSaving || saving"
            @step-click="goToStep"
          />
          <p class="mt-3 text-xs text-slate-500">
            Ruangan bertanda <span class="text-red-500">*</span> wajib diisi semasa menghantar permohonan (bukan draf).
          </p>
        </div>

        <!-- BPP-BORANG-01: MAKLUMAT ASAS -->
        <article v-show="currentStep === 0" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Maklumat Asas</h2>
            <p class="mt-0.5 text-xs text-slate-500">Status perniagaan, sektor dan maklumat bank operasi.</p>
          </div>
          <div class="space-y-4 p-4">
            <div>
              <label :class="labelClass">Status Perniagaan</label>
              <div class="flex flex-wrap gap-4 pt-1">
                <label v-for="opt in STATUS_PERNIAGAAN_OPTIONS" :key="opt.value" class="flex cursor-pointer items-center gap-2">
                  <input
                    v-model="form.statusPerniagaan"
                    type="radio"
                    :value="opt.value"
                    class="h-4 w-4 border-slate-300 text-slate-600 focus:ring-slate-500"
                  />
                  <span class="text-sm text-slate-700">{{ opt.label }}</span>
                </label>
              </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Sektor Perniagaan</label>
                <select v-model="form.sektorPerniagaan" :class="inputClass">
                  <option v-for="s in SEKTOR_PERNIAGAAN_OPTIONS" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
              <div>
                <label :class="labelClass">Kaedah Perniagaan</label>
                <div class="flex gap-4 pt-2">
                  <label class="flex cursor-pointer items-center gap-2">
                    <input
                      v-model="form.kaedahPerniagaan"
                      type="radio"
                      value="online"
                      class="h-4 w-4 border-slate-300 text-slate-600 focus:ring-slate-500"
                    />
                    <span class="text-sm text-slate-700">Online</span>
                  </label>
                  <label class="flex cursor-pointer items-center gap-2">
                    <input
                      v-model="form.kaedahPerniagaan"
                      type="radio"
                      value="offline"
                      class="h-4 w-4 border-slate-300 text-slate-600 focus:ring-slate-500"
                    />
                    <span class="text-sm text-slate-700">Offline</span>
                  </label>
                </div>
              </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Kategori Pembiayaan</label>
                <select v-model="form.kategoriPembiayaan" :class="inputClass">
                  <option v-for="k in KATEGORI_PEMBIAYAAN_OPTIONS" :key="k" :value="k">{{ k }}</option>
                </select>
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <SpptFormLabel required>Nama Bank Operasi Perniagaan</SpptFormLabel>
                <select v-model="form.namaBank" :class="inputClass">
                  <option value="">-- Pilih --</option>
                  <option v-for="b in BANK_OPERASI_OPTIONS" :key="b" :value="b">{{ b }}</option>
                </select>
              </div>
              <div>
                <SpptFormLabel required>No. Akaun Bank</SpptFormLabel>
                <input v-model="form.noAkaunBank" type="text" :class="inputClass" placeholder="No. akaun" />
              </div>
            </div>
          </div>
        </article>

        <!-- BPP-BORANG-01: MAKLUMAT PEMOHON -->
        <article v-show="currentStep === 1" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Maklumat Pemohon</h2>
            <p class="mt-0.5 text-xs text-slate-500">Nama, No. KP, jantina, agama, tarikh lahir dan maklumat peribadi.</p>
          </div>
          <div class="space-y-4 p-4">
            <div class="rounded-lg bg-slate-50/60 p-4">
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass" class="text-slate-500">No. Usahawan</label>
                  <input v-model="form.noUsahawan" type="text" readonly :class="readonlyClass" placeholder="U-001" />
                </div>
                <div>
                  <SpptFormLabel required>Nama Pemohon</SpptFormLabel>
                  <input v-model="form.nama" type="text" :class="inputClass" placeholder="Nama penuh" />
                </div>
              </div>

              <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                  <SpptFormLabel required>No. Kad Pengenalan (Baru)</SpptFormLabel>
                  <input
                    v-model="form.noIcBaru"
                    type="text"
                    :class="inputClass"
                    placeholder="YYMMDD-NN-GGGG"
                    @input="onNoIcBaruInput"
                  />
                </div>
                <div>
                  <label :class="labelClass">No. Kad Pengenalan (Lama)</label>
                  <input v-model="form.noIcLama" type="text" :class="inputClass" placeholder="Jika ada" />
                </div>
              </div>

              <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                  <SpptFormLabel required>Jantina</SpptFormLabel>
                  <select v-model="form.jantina" :class="inputClass">
                    <option v-for="j in JANTINA_OPTIONS" :key="j.value" :value="j.value">{{ j.label }}</option>
                  </select>
                </div>
                <div>
                  <SpptFormLabel required>Agama</SpptFormLabel>
                  <select v-model="form.agama" :class="inputClass">
                    <option value="">Pilih</option>
                    <option v-for="a in AGAMA_OPTIONS" :key="a.value" :value="a.value">{{ a.label }}</option>
                  </select>
                </div>
                <div>
                  <SpptFormLabel required>Taraf Perkahwinan</SpptFormLabel>
                  <select v-model="form.tarafPerkahwinan" :class="inputClass">
                    <option v-for="t in TARAF_PERKAHWINAN_OPTIONS" :key="t" :value="t">{{ t }}</option>
                  </select>
                </div>
                <div>
                  <label :class="labelClass">Bilangan Tanggungan</label>
                  <input v-model="form.bilanganTanggungan" type="text" :class="inputClass" placeholder="0" />
                </div>
              </div>

              <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Tarikh Lahir</label>
                  <SpptDateInput v-model="tarikhLahir" :input-class="inputClass" />
                </div>
                <div>
                  <label :class="labelClass">Umur (semasa memohon)</label>
                  <input v-model="form.umur" type="text" readonly :class="readonlyClass" placeholder="Tahun" />
                </div>
              </div>

              <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div v-if="form.bangsa === 'Lain-lain'">
                  <SpptFormLabel required>Bangsa Lain</SpptFormLabel>
                  <input v-model="form.kaum" type="text" :class="inputClass" placeholder="Nyatakan bangsa" />
                </div>
                <div>
                  <SpptFormLabel required>Bangsa</SpptFormLabel>
                  <select v-model="form.bangsa" :class="inputClass">
                    <option value="">-- Pilih --</option>
                    <option v-for="b in BANGSA_OPTIONS" :key="b" :value="b">{{ b }}</option>
                  </select>
                </div>
              </div>

              <div class="mt-4">
                <SpptFormLabel required>Taraf Pendidikan</SpptFormLabel>
                <select v-model="form.tarafPendidikan" :class="inputClass">
                  <option v-for="p in TARAF_PENDIDIKAN_OPTIONS" :key="p" :value="p">{{ p }}</option>
                </select>
              </div>

              <div class="mt-4 flex flex-wrap gap-6">
                <label class="flex cursor-pointer items-center gap-2">
                  <input v-model="form.oku" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
                  <span class="text-sm text-slate-700">Orang Kelainan Upaya (OKU)</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                  <input v-model="form.diberhentikanPandemik" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
                  <span class="text-sm text-slate-700">Diberhentikan kerja semasa pandemik</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                  <input v-model="form.asnafBerdaftar" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
                  <span class="text-sm text-slate-700">Asnaf berdaftar (MAIN/Zakat)</span>
                </label>
              </div>
            </div>
          </div>
        </article>

        <!-- BPP-BORANG-01: ALAMAT KEDIAMAN -->
        <article v-show="currentStep === 2" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Alamat Kediaman</h2>
            <p class="mt-0.5 text-xs text-slate-500">Alamat penuh, poskod, telefon dan media sosial.</p>
          </div>
          <div class="space-y-4 p-4">
            <div>
              <SpptFormLabel required>Alamat Kediaman</SpptFormLabel>
              <textarea v-model="form.alamat" rows="2" :class="inputClass" placeholder="Alamat penuh" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <SpptFormLabel required>Poskod</SpptFormLabel>
                <input v-model="form.poskod" type="text" :class="inputClass" placeholder="50000" />
              </div>
              <div>
                <SpptFormLabel required>Negeri</SpptFormLabel>
                <select v-model="form.negeri" :class="inputClass">
                  <option value="">-- Pilih Negeri --</option>
                  <option v-for="n in NEGERI_OPTIONS" :key="n" :value="n">{{ n }}</option>
                </select>
              </div>
              <div>
                <label :class="labelClass">No. Telefon (Rumah)</label>
                <input v-model="form.noTelefonRumah" type="text" :class="inputClass" placeholder="03-XXXXXXX" />
              </div>
              <div>
                <SpptFormLabel required>No. Telefon Bimbit</SpptFormLabel>
                <input v-model="form.noTelefonBimbit" type="text" :class="inputClass" placeholder="01X-XXXXXXX" />
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <SpptFormLabel required>E-mel</SpptFormLabel>
                <input v-model="form.email" type="email" :class="inputClass" placeholder="email@contoh.com" />
              </div>
              <div>
                <label :class="labelClass">Facebook</label>
                <input v-model="form.facebook" type="text" :class="inputClass" placeholder="Opsional" />
              </div>
              <div>
                <label :class="labelClass">Instagram</label>
                <input v-model="form.instagram" type="text" :class="inputClass" placeholder="Opsional" />
              </div>
              <div>
                <label :class="labelClass">Status Kediaman</label>
                <select v-model="form.statusKediaman" :class="inputClass">
                  <option v-for="s in STATUS_KEDIAMAN_OPTIONS" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
            </div>
          </div>
        </article>

        <!-- BPP-BORANG-01: PEKERJAAN SEKARANG -->
        <article v-show="currentStep === 3" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Pekerjaan Sekarang</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pendapatan dan maklumat majikan (jika bekerja).</p>
          </div>
          <div class="space-y-4 p-4">
            <div>
              <SpptFormLabel required>Status Pekerjaan</SpptFormLabel>
              <div class="flex flex-wrap gap-4 rounded-lg border border-slate-200 p-4">
                <label
                  v-for="opt in STATUS_PEKERJAAN_OPTIONS"
                  :key="opt.value"
                  class="flex cursor-pointer items-center gap-2"
                >
                  <input
                    v-model="form.statusPekerjaan"
                    type="radio"
                    :value="opt.value"
                    class="h-4 w-4 border-slate-300 text-slate-600 focus:ring-slate-500"
                  />
                  <span class="text-sm text-slate-700">{{ opt.label }}</span>
                </label>
              </div>
            </div>

            <div
              v-if="form.statusPekerjaan === 'bekerja'"
              class="space-y-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4"
            >
              <h3 class="text-sm font-semibold text-slate-700">Butiran Pekerjaan</h3>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <SpptFormLabel required>Sektor Pekerjaan</SpptFormLabel>
                  <select v-model="form.sektorPekerjaan" :class="inputClass">
                    <option value="">Sila Pilih</option>
                    <option v-for="s in SEKTOR_PEKERJAAN_OPTIONS" :key="s" :value="s">{{ s }}</option>
                  </select>
                </div>
                <div>
                  <label :class="labelClass">Jawatan</label>
                  <input v-model="form.jawatan" type="text" :class="inputClass" placeholder="Contoh: Kerani, Pengurus" />
                </div>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Status Jawatan</label>
                  <select v-model="form.statusJawatan" :class="inputClass">
                    <option value="">Sila Pilih</option>
                    <option v-for="s in STATUS_JAWATAN_OPTIONS" :key="s" :value="s">{{ s }}</option>
                  </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <SpptFormLabel required>Pendapatan (RM)</SpptFormLabel>
                    <input v-model="form.pendapatan" type="text" :class="inputClass" placeholder="0" />
                  </div>
                  <div>
                    <label :class="labelClass">/ Bulan</label>
                    <input v-model="form.pendapatanBulan" type="text" :class="inputClass" placeholder="1" />
                  </div>
                </div>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Nama Majikan</label>
                  <input v-model="form.namaMajikan" type="text" :class="inputClass" placeholder="Opsional" />
                </div>
                <div>
                  <label :class="labelClass">No. Telefon Majikan</label>
                  <input v-model="form.noTelefonMajikan" type="text" :class="inputClass" placeholder="Opsional" />
                </div>
              </div>
              <div>
                <label :class="labelClass">Alamat Majikan</label>
                <input v-model="form.alamatMajikan" type="text" :class="inputClass" placeholder="Opsional" />
              </div>
            </div>
          </div>
        </article>

        <!-- BPP-BORANG-01: MAKLUMAT PASANGAN PEMOHON -->
        <article v-show="currentStep === 4" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Maklumat Pasangan Pemohon</h2>
            <p class="mt-0.5 text-xs text-slate-500">Jika berkenaan (berkahwin).</p>
          </div>
          <div class="space-y-4 p-4">
            <label class="flex cursor-pointer items-center gap-2">
              <input v-model="adaPasangan" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
              <span class="text-sm text-slate-700">Ada maklumat pasangan untuk diisi</span>
            </label>

            <div v-if="adaPasangan" class="space-y-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <SpptFormLabel :required="adaPasangan">Nama Suami / Isteri</SpptFormLabel>
                  <input v-model="form.namaPasangan" type="text" :class="inputClass" placeholder="Nama pasangan" />
                </div>
                <div>
                  <SpptFormLabel :required="adaPasangan">No. Kad Pengenalan</SpptFormLabel>
                  <input v-model="form.noIcPasangan" type="text" :class="inputClass" placeholder="YYMMDD-NN-GGGG" />
                </div>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">No. Passport (jika ada)</label>
                  <input v-model="form.noPassportPasangan" type="text" :class="inputClass" placeholder="Opsional" />
                </div>
                <div>
                  <label :class="labelClass">Pekerjaan</label>
                  <input v-model="form.pekerjaanPasangan" type="text" :class="inputClass" placeholder="jika berkenaan" />
                </div>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Alamat Majikan</label>
                  <input v-model="form.alamatMajikanPasangan" type="text" :class="inputClass" placeholder="Opsional" />
                </div>
                <div>
                  <label :class="labelClass">Poskod</label>
                  <input v-model="form.poskodMajikanPasangan" type="text" :class="inputClass" placeholder="Opsional" />
                </div>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">No. Telefon Majikan</label>
                  <input v-model="form.noTelefonMajikanPasangan" type="text" :class="inputClass" placeholder="Opsional" />
                </div>
                <div>
                  <label :class="labelClass">No. Telefon Bimbit</label>
                  <input v-model="form.noTelefonBimbitPasangan" type="text" :class="inputClass" placeholder="Opsional" />
                </div>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Pendapatan (RM)</label>
                  <input v-model="form.pendapatanPasangan" type="text" :class="inputClass" placeholder="0" />
                </div>
                <div>
                  <label :class="labelClass">/ Bulan</label>
                  <input v-model="form.pendapatanPasanganBulan" type="text" :class="inputClass" placeholder="1" />
                </div>
              </div>
            </div>
          </div>
        </article>

        <!-- F: MAKLUMAT PERNIAGAAN -->
        <article v-show="currentStep === 5" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">F. Maklumat Perniagaan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Nama perniagaan, SSM, premis, pemilikan, pembiayaan sedia ada.</p>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <SpptFormLabel required>Nama Perniagaan</SpptFormLabel>
                <input v-model="form.namaPerniagaan" type="text" :class="inputClass" placeholder="Nama perniagaan" />
              </div>
              <div>
                <label :class="labelClass">No. SSM (ROB/ROE)</label>
                <input v-model="form.noSsm" type="text" :class="inputClass" placeholder="No. pendaftaran" />
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Aktiviti / Tempoh Berniaga (tahun)</label>
                <input v-model="form.tempohBerniaga" type="text" :class="inputClass" placeholder="Contoh: 3" />
              </div>
              <div>
                <label :class="labelClass">Anggaran Pendapatan Kasar (RM/sebulan)</label>
                <input v-model="form.anggaranPendapatan" type="text" :class="inputClass" placeholder="0" />
              </div>
            </div>
            <div>
              <label :class="labelClass">Alamat Perniagaan / Premis</label>
              <textarea v-model="form.alamatPremis" rows="2" :class="inputClass" placeholder="Alamat premis" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label :class="labelClass">Poskod</label>
                <input v-model="form.poskodPremis" type="text" :class="inputClass" placeholder="50000" />
              </div>
              <div>
                <label :class="labelClass">No. Tel (Premis)</label>
                <input v-model="form.noTelPremis" type="text" :class="inputClass" placeholder="Opsional" />
              </div>
              <div>
                <label :class="labelClass">No. Telefon Bimbit</label>
                <input v-model="form.noTelBimbitPremis" type="text" :class="inputClass" placeholder="01X-XXXXXXX" />
              </div>
              <div>
                <label :class="labelClass">Status Premis</label>
                <select v-model="form.statusPremis" :class="inputClass">
                  <option v-for="s in STATUS_PREMIS_OPTIONS" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Pemilikan Perniagaan</label>
                <select v-model="form.pemilikanPerniagaan" :class="inputClass">
                  <option v-for="p in PEMILIKAN_PERNIAGAAN_OPTIONS" :key="p" :value="p">{{ p }}</option>
                </select>
              </div>
              <div v-if="form.pemilikanPerniagaan === 'Sendirian Berhad'">
                <label :class="labelClass">Modal Berbayar (RM)</label>
                <input v-model="form.modalBerbayar" type="text" :class="inputClass" placeholder="0" />
              </div>
            </div>
            <div class="flex flex-wrap gap-4">
              <label class="flex cursor-pointer items-center gap-2">
                <input v-model="form.pemegangSaham" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
                <span class="text-sm text-slate-700">Adakah pemohon pemegang saham?</span>
              </label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Tarikh Didaftarkan</label>
                <SpptDateInput v-model="form.tarikhDaftar" :input-class="inputClass" />
              </div>
              <div>
                <label :class="labelClass">Tarikh Tamat Lesen</label>
                <SpptDateInput v-model="form.tarikhTamatLesen" :input-class="inputClass" />
              </div>
            </div>
            <div>
              <label class="flex cursor-pointer items-center gap-2">
                <input v-model="form.keahlianPersatuan" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
                <span class="text-sm text-slate-700">Keahlian Persatuan (Penjaja/Peniaga)</span>
              </label>
              <input v-if="form.keahlianPersatuan" v-model="form.keahlianPersatuanNyata" type="text" :class="inputClass" placeholder="Sila nyatakan" class="mt-2" />
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <p class="mb-2 text-xs font-medium text-slate-600">Pengesahan Penjaja dan Peniaga Kecil</p>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Nama</label>
                  <input v-model="form.pengesahanNama" type="text" :class="inputClass" placeholder="Nama penuh" />
                </div>
                <div>
                  <label :class="labelClass">No. Telefon</label>
                  <input v-model="form.pengesahanNoTel" type="text" :class="inputClass" placeholder="01X-XXXXXXX" />
                </div>
              </div>
              <div class="mt-4">
                <label :class="labelClass">Alamat</label>
                <input v-model="form.pengesahanAlamat" type="text" :class="inputClass" placeholder="Alamat" />
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Masa Berniaga - Dari</label>
                <SpptTimeInput v-model="form.masaBerniagaDari" :input-class="inputClass" />
              </div>
              <div>
                <label :class="labelClass">Hingga</label>
                <SpptTimeInput v-model="form.masaBerniagaHingga" :input-class="inputClass" />
              </div>
            </div>
            <div>
              <label class="flex cursor-pointer items-center gap-2">
                <input v-model="form.pengiktirafan" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
                <span class="text-sm text-slate-700">Pengiktirafan (Halal, GMP, ISO, dll)</span>
              </label>
              <input v-if="form.pengiktirafan" v-model="form.pengiktirafanNyata" type="text" :class="inputClass" placeholder="Sila nyatakan" class="mt-2" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Nilai Aset Perniagaan Sedia Ada (RM)</label>
                <input v-model="form.nilaiAset" type="text" :class="inputClass" placeholder="0" />
              </div>
              <div>
                <label :class="labelClass">Sumber Modal Memulakan Perniagaan</label>
                <input v-model="form.sumberModal" type="text" :class="inputClass" placeholder="Opsional" />
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Kursus - Agensi Penganjur</label>
                <select v-model="form.kursusAgensi" :class="inputClass">
                  <option value="">-- Pilih --</option>
                  <option v-for="a in AGENSI_KURSUS_OPTIONS" :key="a" :value="a">{{ a }}</option>
                </select>
              </div>
              <div>
                <label :class="labelClass">Kursus Lain (jika ada)</label>
                <input v-model="form.kursusLain" type="text" :class="inputClass" placeholder="Opsional" />
              </div>
            </div>
            <div>
              <label :class="labelClass">Perniagaan Terdahulu (jika bertukar aktiviti)</label>
              <input v-model="form.perniagaanTerdahulu" type="text" :class="inputClass" placeholder="Opsional" />
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <p class="mb-2 text-xs font-medium text-slate-600">Maklumat Pembiayaan Perniagaan Sedia Ada</p>
              <div class="flex gap-4">
                <label class="flex cursor-pointer items-center gap-2">
                  <input v-model="form.pembiayaanSediaAda" type="radio" :value="true" class="h-4 w-4 border-slate-300 text-slate-600 focus:ring-slate-500" />
                  <span class="text-sm text-slate-700">ADA</span>
                </label>
                <label class="flex cursor-pointer items-center gap-2">
                  <input v-model="form.pembiayaanSediaAda" type="radio" :value="false" class="h-4 w-4 border-slate-300 text-slate-600 focus:ring-slate-500" />
                  <span class="text-sm text-slate-700">TIADA</span>
                </label>
              </div>
              <div v-if="form.pembiayaanSediaAda" class="mt-4 space-y-4">
                <div>
                  <label :class="labelClass">Institusi Pembiayaan</label>
                  <select v-model="form.institusiPembiayaan" :class="inputClass">
                    <option value="">-- Pilih --</option>
                    <option v-for="i in INSTITUSI_PEMBIAYAAN_OPTIONS" :key="i" :value="i">{{ i }}</option>
                  </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label :class="labelClass">Jumlah Pembiayaan (RM)</label>
                    <input v-model="form.jumlahPembiayaanSediaAda" type="text" :class="inputClass" placeholder="0" />
                  </div>
                  <div>
                    <label :class="labelClass">Baki Pembiayaan (RM)</label>
                    <input v-model="form.bakiPembiayaan" type="text" :class="inputClass" placeholder="0" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </article>

        <!-- KETERANGAN PEMBIAYAAN -->
        <article v-show="currentStep === 6" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Keterangan Mengenai Pembiayaan Yang Dipohon</h2>
            <p class="mt-0.5 text-xs text-slate-500">Jumlah, tempoh dan tujuan pembiayaan.</p>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <SpptFormLabel required>Jumlah Permohonan (RM)</SpptFormLabel>
                <input v-model="form.jumlahPermohonan" type="text" :class="inputClass" placeholder="50000" />
              </div>
              <div>
                <SpptFormLabel required>Tempoh Pembiayaan (bulan)</SpptFormLabel>
                <input v-model="form.tempohPembiayaan" type="text" :class="inputClass" placeholder="60" />
              </div>
            </div>
            <div>
              <label :class="labelClass">Kekerapan Bayaran</label>
              <select v-model="form.kekerapanBayaran" :class="inputClass">
                <option v-for="k in KEEKERAPAN_BAYARAN_OPTIONS" :key="k" :value="k">{{ k }}</option>
              </select>
            </div>
            <div>
              <SpptFormLabel required>Tujuan Pembiayaan</SpptFormLabel>
              <textarea v-model="form.tujuan" rows="3" :class="inputClass" placeholder="Nyatakan tujuan permohonan" />
            </div>
          </div>
        </article>

        <!-- G: SOKONGAN KUMPULAN (TEMAN TEKUN sahaja) -->
        <article v-show="currentStep === 7" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">G. Sokongan Kumpulan (TEMAN TEKUN sahaja)</h2>
            <p class="mt-0.5 text-xs text-slate-500">Maklumat kumpulan, perakuan dan perujuk.</p>
          </div>
          <div class="space-y-4 p-4">
            <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-3 text-xs text-amber-800">
              Bahagian ini hanya untuk permohonan kategori TEMAN TEKUN.
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <SpptFormLabel :required="form.kategoriPembiayaan === 'TEMAN TEKUN'">Nama</SpptFormLabel>
                <input v-model="form.sokonganNama" type="text" :class="inputClass" placeholder="Nama" />
              </div>
              <div>
                <SpptFormLabel :required="form.kategoriPembiayaan === 'TEMAN TEKUN'">Nombor Kad Pengenalan</SpptFormLabel>
                <input v-model="form.sokonganNoKp" type="text" :class="inputClass" placeholder="YYMMDD-NN-GGGG" />
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Tempoh Perkenalan dengan Kumpulan</label>
                <input v-model="form.sokonganTempohPerkenalan" type="text" :class="inputClass" placeholder="Contoh: 2 tahun" />
              </div>
              <div>
                <label :class="labelClass">Tarikh Perbincangan Kumpulan</label>
                <SpptDateInput v-model="form.sokonganTarikhPerbincangan" :input-class="inputClass" />
              </div>
            </div>
            <div>
              <SpptFormLabel :required="form.kategoriPembiayaan === 'TEMAN TEKUN'">Jumlah Pembiayaan Yang Disokong (RM)</SpptFormLabel>
              <input v-model="form.sokonganJumlah" type="text" :class="inputClass" placeholder="0" />
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <p class="mb-2 text-xs font-medium text-slate-600">Perakuan (Ketua PSAT / Timb. Ketua / Setiausaha / Bendahari)</p>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Jawatan</label>
                  <input v-model="form.perakuanJawatan" type="text" :class="inputClass" placeholder="Ketua PSAT" />
                </div>
                <div>
                  <label :class="labelClass">Nama</label>
                  <input v-model="form.perakuanNama" type="text" :class="inputClass" placeholder="Nama penuh" />
                </div>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Alamat</label>
                  <input v-model="form.perakuanAlamat" type="text" :class="inputClass" placeholder="Alamat" />
                </div>
                <div>
                  <label :class="labelClass">No. Telefon</label>
                  <input v-model="form.perakuanNoTel" type="text" :class="inputClass" placeholder="01X-XXXXXXX" />
                </div>
              </div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <p class="mb-2 text-xs font-medium text-slate-600">Perujuk 1 (Ahli keluarga 18 tahun ke atas)</p>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Nama</label>
                  <input v-model="form.perujuk1Nama" type="text" :class="inputClass" placeholder="Nama" />
                </div>
                <div>
                  <label :class="labelClass">No. Kad Pengenalan</label>
                  <input v-model="form.perujuk1NoKp" type="text" :class="inputClass" placeholder="YYMMDD-NN-GGGG" />
                </div>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Alamat</label>
                  <input v-model="form.perujuk1Alamat" type="text" :class="inputClass" placeholder="Alamat" />
                </div>
                <div>
                  <label :class="labelClass">Hubungan dengan Pemohon</label>
                  <input v-model="form.perujuk1Hubungan" type="text" :class="inputClass" placeholder="Contoh: Adik" />
                </div>
              </div>
              <div class="mt-4">
                <label :class="labelClass">No. Telefon</label>
                <input v-model="form.perujuk1NoTel" type="text" :class="inputClass" placeholder="01X-XXXXXXX" />
              </div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <p class="mb-2 text-xs font-medium text-slate-600">Perujuk 2</p>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Nama</label>
                  <input v-model="form.perujuk2Nama" type="text" :class="inputClass" placeholder="Nama" />
                </div>
                <div>
                  <label :class="labelClass">No. Kad Pengenalan</label>
                  <input v-model="form.perujuk2NoKp" type="text" :class="inputClass" placeholder="YYMMDD-NN-GGGG" />
                </div>
              </div>
              <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Alamat</label>
                  <input v-model="form.perujuk2Alamat" type="text" :class="inputClass" placeholder="Alamat" />
                </div>
                <div>
                  <label :class="labelClass">Hubungan dengan Pemohon</label>
                  <input v-model="form.perujuk2Hubungan" type="text" :class="inputClass" placeholder="Contoh: Ibu" />
                </div>
              </div>
              <div class="mt-4">
                <label :class="labelClass">No. Telefon</label>
                <input v-model="form.perujuk2NoTel" type="text" :class="inputClass" placeholder="01X-XXXXXXX" />
              </div>
            </div>
          </div>
        </article>

        <!-- H: PERLINDUNGAN TAKAFUL DAN PERKESO -->
        <article v-show="currentStep === 8" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">H. Perlindungan Takaful dan PERKESO</h2>
            <p class="mt-0.5 text-xs text-slate-500">Takaful Pembiayaan (wajib), Takaful Kemalangan (sukarela), Skim PERKESO.</p>
          </div>
          <div class="space-y-4 p-4">
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <input v-model="form.takafulPembiayaan" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
              <div class="text-sm text-slate-700">
                <span class="font-medium">Takaful Pembiayaan Berkelompok (wajib)<span class="ml-0.5 text-red-500" aria-hidden="true">*</span></span>
                <p class="mt-1 text-xs text-slate-500">Perlindungan ke atas baki pembiayaan sekiranya Kematian atau Hilang Upaya Menyeluruh dan Kekal. Sumbangan ditolak daripada jumlah pembiayaan diluluskan.</p>
              </div>
            </label>
            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <label class="flex cursor-pointer items-center gap-2">
                <input v-model="form.takafulKemalangan" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
                <span class="text-sm font-medium text-slate-700">Takaful Kemalangan Peribadi (sukarela, tahun pertama)</span>
              </label>
              <div v-if="form.takafulKemalangan" class="mt-3">
                <label :class="labelClass">Pakej (berdasarkan jumlah pembiayaan diluluskan)</label>
                <select v-model="form.takafulKemalanganPakej" :class="inputClass">
                  <option value="">-- Pilih Pakej --</option>
                  <option v-for="p in TAKAFUL_KEMALANGAN_PAKEJ" :key="p.value" :value="p.value">{{ p.label }}</option>
                </select>
              </div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <label class="flex cursor-pointer items-center gap-2">
                <input v-model="form.perkeso" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
                <span class="text-sm font-medium text-slate-700">Skim Keselamatan Sosial Pekerjaan Sendiri (PERKESO)</span>
              </label>
              <p class="mt-1 text-xs text-slate-500">Akta Keselamatan Sosial Pekerjaan Sendiri 2017. Caruman ditolak daripada pembiayaan diluluskan. Wajib (kecuali telah mencarum dan masih aktif).</p>
              <div v-if="form.perkeso" class="mt-3 space-y-2">
                <label :class="labelClass">Pakej Caruman</label>
                <select v-model="form.perkesoPakej" :class="inputClass">
                  <option value="">-- Pilih Pakej --</option>
                  <option v-for="p in PERKESO_PAKEJ" :key="p.value" :value="p.value">{{ p.label }}</option>
                </select>
                <div class="grid gap-4 sm:grid-cols-2">
                  <div>
                    <label :class="labelClass">Sektor (diisi TEKUN)</label>
                    <input v-model="form.perkesoSektor" type="text" :class="inputClass" placeholder="Opsional" />
                  </div>
                  <div>
                    <label :class="labelClass">Kelas (diisi TEKUN)</label>
                    <input v-model="form.perkesoKelas" type="text" :class="inputClass" placeholder="Opsional" />
                  </div>
                </div>
              </div>
            </div>
            <p class="text-xs text-slate-500">
              Nota: Skim Kontrak dikecualikan Takaful Kemalangan & PERKESO. TEMAN ULTRA dikecualikan PERKESO.
            </p>
          </div>
        </article>

        <!-- I: PENDAFTARAN WASIAT -->
        <article v-show="currentStep === 9" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">I. Pendaftaran Wasiat (Jika berkenaan)</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pendaftaran wasiat secara sukarela. Bayaran ditolak daripada pembiayaan diluluskan.</p>
          </div>
          <div class="space-y-4 p-4">
            <label class="flex cursor-pointer items-center gap-2">
              <input v-model="form.wasiat" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
              <span class="text-sm font-medium text-slate-700">Saya secara sukarela ingin memohon untuk mendaftar wasiat</span>
            </label>
            <div v-if="form.wasiat" class="space-y-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <div>
                <label :class="labelClass">Jumlah Bayaran Pendaftaran Wasiat (RM)</label>
                <input v-model="form.wasiatJumlah" type="text" :class="inputClass" placeholder="0" />
              </div>
              <div>
                <label :class="labelClass">Nama Syarikat Wasiat</label>
                <input v-model="form.wasiatNamaSyarikat" type="text" :class="inputClass" placeholder="Diisi oleh TEKUN" />
              </div>
            </div>
          </div>
        </article>

        <!-- J: KEBENARAN PENZAHIRAN MAKLUMAT KREDIT INDIVIDU -->
        <article v-show="currentStep === 10" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">J. Kebenaran Penzahiran Maklumat Kredit Individu</h2>
            <p class="mt-0.5 text-xs text-slate-500">Persetujuan pendedahan maklumat kredit kepada Experian dan CTOS.</p>
          </div>
          <div class="space-y-4 p-4">
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
              <input v-model="form.kebenaranKredit" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
              <div class="text-sm text-slate-700">
                <span class="font-medium">Saya membenarkan TEKUN Nasional dan pegawainya untuk:<span class="ml-0.5 text-red-500" aria-hidden="true">*</span></span>
                <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-slate-600">
                  <li>Menggunakan, mendedahkan maklumat akaun pembiayaan TEKUN untuk penilaian kredit atau bayaran</li>
                  <li>Penzahiran maklumat kredit kepada Experian dan/atau CTOS serta pelanggan mereka (Bank, institusi kewangan, agensi pelaporan kredit)</li>
                  <li>Memberi kebenaran kepada Experian dan CTOS bagi pendedahan maklumat kredit kepada TEKUN untuk tujuan pembukaan akaun, penilaian, pemarkahan, semakan, pemantauan, pemulihan hutang, dokumentasi undang-undang</li>
                </ul>
                <p class="mt-2 text-xs text-slate-500">Persetujuan kekal terpakai selagi pemohon mengekalkan akaun pembiayaan dengan organisasi.</p>
              </div>
            </label>
          </div>
        </article>

        <!-- DOKUMEN SOKONGAN -->
        <article v-show="currentStep === 11" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">
              Dokumen Sokongan
              <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
            </h2>
            <p class="mt-0.5 text-xs text-slate-500">
              Muat naik Borang Permohonan Pembiayaan (jika berasingan) dan dokumen sokongan mengikut keperluan TEKUN Nasional.
            </p>
          </div>
          <div class="space-y-4 p-4">
            <div class="rounded-lg border border-sky-200 bg-sky-50 p-4">
              <div class="flex items-start gap-2">
                <Info class="mt-0.5 h-4 w-4 shrink-0 text-sky-700" aria-hidden="true" />
                <div class="min-w-0 text-sm text-sky-900">
                  <p class="font-semibold">Apakah dokumen yang diperlukan?</p>
                  <p class="mt-1 text-xs leading-relaxed text-sky-800">
                    Pemohon perlu mengemukakan Borang Permohonan Pembiayaan dan dokumen tambahan (jika berkenaan) seperti berikut:
                  </p>
                  <ol class="mt-2 list-decimal space-y-1.5 pl-4 text-xs leading-relaxed text-sky-800">
                    <li v-for="(item, index) in DOKUMEN_SOKONGAN_GUIDE" :key="index">{{ item }}</li>
                  </ol>
                </div>
              </div>
            </div>

            <input
              ref="fileInputRef"
              type="file"
              multiple
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,image/jpeg,image/png,application/pdf"
              class="hidden"
              @change="onFileSelect"
            />
            <div
              :class="[
                'rounded-lg border-2 border-dashed p-6 text-center transition-colors',
                isDragOver ? 'border-slate-400 bg-slate-50' : 'border-slate-200 bg-slate-50/50 hover:bg-slate-50',
              ]"
              @dragover="onDragOver"
              @dragleave="onDragLeave"
              @drop="onDrop"
            >
              <Paperclip class="mx-auto h-10 w-10 text-slate-400" />
              <p class="mt-2 text-sm font-medium text-slate-600">Seret fail ke sini atau</p>
              <button
                type="button"
                class="mt-2 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
                @click="triggerFileInput"
              >
                <Upload class="h-4 w-4" />
                Pilih Fail
              </button>
              <p class="mt-2 text-xs text-slate-500">PDF, DOC, DOCX, JPG, PNG (maks. {{ SPPT_DOCUMENT_MAX_LABEL }} setiap fail)</p>
            </div>

            <div v-if="attachments.length > 0" class="space-y-2">
              <p class="text-xs font-medium text-slate-600">{{ attachments.length }} fail dilampirkan</p>
              <div
                v-for="item in attachments"
                :key="item.id"
                class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2"
              >
                <div class="flex min-w-0 flex-1 items-start gap-3">
                  <FileText class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" />
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-900">{{ item.name }}</p>
                    <p class="text-xs text-slate-500">{{ formatSize(item.size) }}</p>
                    <button
                      v-if="item.url"
                      type="button"
                      class="mt-1 text-xs text-slate-500 underline hover:text-slate-700"
                      @click="openAttachment(item)"
                    >
                      Lihat fail
                    </button>
                    <p v-else-if="!draftId" class="mt-1 text-xs text-amber-600">Belum dimuat naik — simpan draf untuk muat naik</p>
                    <p v-else class="mt-1 text-xs text-amber-600">Sedang menunggu muat naik — sila tunggu atau cuba pilih semula fail</p>
                  </div>
                </div>

                <div class="w-52 shrink-0 sm:w-60">
                  <label class="mb-1 block text-xs font-medium text-slate-600">Jenis Dokumen</label>
                  <select
                    v-model="item.documentClass"
                    class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-900 shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    @change="onAttachmentClassChange(item)"
                  >
                    <option v-for="opt in SPPT_DOCUMENT_CLASSES" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>
                  <div v-if="item.documentClass === 'lain_lain'" class="mt-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600">
                      Nyatakan jenis
                      <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="item.documentClassOther"
                      type="text"
                      class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs text-slate-900 shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                      placeholder="Contoh: Surat Pengesahan Majikan"
                      @blur="onAttachmentClassChange(item)"
                    />
                  </div>
                </div>

                <button
                  type="button"
                  class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                  @click="removeAttachment(item.id)"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </div>

            <SpptDocumentClassModal
              :open="classModalOpen"
              :file-name="classModalFile?.name ?? ''"
              :classifying="classModalClassifying"
              :suggested-class="classModalSuggestedClass"
              :confidence="classModalConfidence"
              :message="classModalMessage"
              v-model:selected-class="classModalSelectedClass"
              v-model:other-label="classModalOtherLabel"
              @confirm="confirmClassModal"
              @cancel="cancelClassModal"
            />
          </div>
        </article>

        <div v-if="noRujukan" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-600">
          No. Rujukan: <span class="font-mono font-medium text-slate-800">{{ noRujukan }}</span>
          <span v-if="draftId" class="ml-2 rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Draf</span>
        </div>

        <div v-if="saved" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
          Permohonan berjaya dihantar. Mengalihkan ke senarai permohonan...
        </div>

        <div
          v-if="validationErrors.length > 0"
          class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3"
        >
          <p class="text-sm font-semibold text-rose-800">
            {{ validationErrors.some((e) => e.stepLabel === "Saringan Auto-Kelayakan") ? "Saringan Auto-Kelayakan gagal (auto-reject):" : "Sila lengkapkan maklumat wajib:" }}
          </p>
          <ul class="mt-2 space-y-1">
            <li
              v-for="err in validationErrors"
              :key="err.stepIndex"
              class="flex items-start gap-2 text-sm text-rose-700"
            >
              <span class="shrink-0">•</span>
              <div>
                <button
                  type="button"
                  class="font-medium underline hover:no-underline"
                  @click="goToStep(err.stepIndex)"
                >
                  {{ err.stepLabel }}
                </button>
                : {{ err.messages.join("; ") }}
              </div>
            </li>
          </ul>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-3">
            <button
              type="button"
              :disabled="draftSaving || saving"
              class="flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 shadow-sm transition-colors hover:bg-amber-100 disabled:opacity-60"
              @click="simpanDraf"
            >
              <Save class="h-4 w-4" />
              {{ draftSaving ? "Menyimpan draf..." : "Simpan Draf" }}
            </button>
            <button
              v-if="currentStep > 0"
              type="button"
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
              @click="goPrev"
            >
              <ArrowLeft class="h-4 w-4" />
              Sebelumnya
            </button>
            <button
              v-if="currentStep < totalSteps - 1"
              type="button"
              :disabled="draftSaving || saving || hardRuleChecking"
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-60"
              @click="goNext"
            >
              {{ hardRuleChecking ? "Menyemak kelayakan..." : draftSaving ? "Menyimpan draf..." : "Seterusnya" }}
              <ArrowRight v-if="!draftSaving && !hardRuleChecking" class="h-4 w-4" />
            </button>
            <button
              v-if="currentStep === totalSteps - 1"
              type="submit"
              :disabled="saving || draftSaving || icSubmitVerifying"
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-60"
            >
              <Save class="h-4 w-4" />
              {{ icSubmitVerifying ? "Mengesahkan dokumen..." : saving ? "Menghantar..." : "Hantar Permohonan" }}
            </button>
          </div>
          <button
            type="button"
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
            @click="batal"
          >
            <ArrowLeft class="h-4 w-4" />
            Batal
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
