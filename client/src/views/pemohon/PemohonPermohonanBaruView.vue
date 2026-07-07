<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ArrowLeft, ArrowRight, CheckCircle2, FileText, Loader2, Save, Send, ShieldCheck, Upload, X, XCircle } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import SpptStepper from "@/components/sppt/SpptStepper.vue";
import SpptDateInput from "@/components/sppt/SpptDateInput.vue";
import SpptTimeInput from "@/components/sppt/SpptTimeInput.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { PERMOHONAN_BARU_STEPS, PRODUK_OPTIONS } from "@/data/portal-dummy";
import {
  createPemohonPermohonan,
  updatePemohonPermohonan,
  uploadPemohonPermohonanDocument,
  classifyPemohonPermohonanDocument,
  verifyPemohonPermohonanDocument,
  deletePemohonPermohonanDocument,
} from "@/api/pemohonPermohonan";
import type { PermohonanAttachment } from "@/types";
import { parseMalaysianIcBirthDate } from "@/utils/malaysianIc";
import { ageFromIsoDate, birthPartsFromIso, isoFromBirthParts } from "@/utils/spptDate";
import { SPPT_DOCUMENT_CLASSES, documentClassLabel } from "@/config/sppt-document-classes";
import { SPPT_DOCUMENT_MAX_BYTES, SPPT_DOCUMENT_MAX_LABEL } from "@/config/sppt-upload";
import {
  AGAMA_OPTIONS,
  AGENSI_KURSUS_OPTIONS,
  BANGSA_OPTIONS,
  INSTITUSI_PEMBIAYAAN_OPTIONS,
  JANTINA_OPTIONS,
  KEEKERAPAN_BAYARAN_OPTIONS,
  NEGERI_OPTIONS,
  PEMILIKAN_PERNIAGAAN_OPTIONS,
  PERKESO_PAKEJ,
  SEKTOR_PEKERJAAN_OPTIONS,
  STATUS_JAWATAN_OPTIONS,
  STATUS_KEDIAMAN_OPTIONS,
  STATUS_PEKERJAAN_OPTIONS,
  STATUS_PREMIS_OPTIONS,
  TAKAFUL_KEMALANGAN_PAKEJ,
  TARAF_PENDIDIKAN_OPTIONS,
  TARAF_PERKAHWINAN_OPTIONS,
} from "@/config/sppt-options";

const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const inputClass = "w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200";

const currentStep = ref(0);
const saving = ref(false);
const draftSaving = ref(false);
const submitting = ref(false);

const backendId = ref<number | null>(null);
const accessToken = ref<string | null>(null);

const form = reactive({
  // Asas (untouched — existing pemohon step 1)
  kategoriPembiayaan: "",
  jumlahPermohonan: 20000,
  tujuanPembiayaan: "",

  // Pemohon
  nama: pemohon.profil.nama,
  noIcBaru: pemohon.profil.noKp,
  noIcLama: "",
  jantina: "",
  agama: "",
  tarikhLahirHari: "",
  tarikhLahirBulan: "",
  tarikhLahirTahun: "",
  umur: "",
  bangsa: "",
  kaum: "",
  tarafPerkahwinan: "",
  bilanganTanggungan: "",
  oku: false,
  diberhentikanPandemik: false,
  asnafBerdaftar: false,
  tarafPendidikan: "",

  // Alamat
  alamat: pemohon.profil.alamat,
  poskod: "",
  negeri: "",
  noTelefonRumah: "",
  noTelefonBimbit: pemohon.profil.telefon,
  email: pemohon.profil.email,
  facebook: "",
  instagram: "",
  statusKediaman: "",

  // Pekerjaan
  statusPekerjaan: "" as "" | "bekerja" | "tidak_bekerja",
  sektorPekerjaan: "",
  jawatan: "",
  statusJawatan: "",
  pendapatan: "",
  namaMajikan: "",
  alamatMajikan: "",
  noTelefonMajikan: "",

  // Pasangan
  namaPasangan: "",
  noIcPasangan: "",
  noPassportPasangan: "",
  pekerjaanPasangan: "",
  alamatMajikanPasangan: "",
  poskodMajikanPasangan: "",
  noTelefonMajikanPasangan: "",
  noTelefonBimbitPasangan: "",
  pendapatanPasangan: "",

  // Perniagaan
  namaPerniagaan: pemohon.profil.perniagaan,
  noSsm: pemohon.profil.noSsm,
  tempohBerniaga: "",
  alamatPremis: "",
  poskodPremis: "",
  anggaranPendapatan: "",
  noTelPremis: "",
  noTelBimbitPremis: "",
  statusPremis: "Sewa",
  pemilikanPerniagaan: "Pemilikan Tunggal",
  modalBerbayar: "",
  tarikhDaftar: "",
  tarikhTamatLesen: "",
  keahlianPersatuan: false,
  keahlianPersatuanNyata: "",
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

  // Pembiayaan (extra fields beyond Asas)
  tempohPembiayaan: "",
  kekerapanBayaran: "Bulanan",

  // Sokongan
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

  // Takaful
  takafulPembiayaan: true,
  takafulKemalangan: false,
  takafulKemalanganPakej: "",
  perkeso: false,
  perkesoPakej: "",

  // Wasiat
  wasiat: false,
  wasiatJumlah: "",

  // Kebenaran
  kebenaranKredit: false,
});

const adaPasangan = ref(false);

function onTarafPerkahwinanChange() {
  const taraf = form.tarafPerkahwinan.toLowerCase();
  if (["berkahwin", "duda", "janda"].some((s) => taraf.includes(s))) {
    adaPasangan.value = true;
  } else {
    adaPasangan.value = false;
  }
}

function onNoIcBaruInput() {
  const parsed = parseMalaysianIcBirthDate(form.noIcBaru);
  if (!parsed) return;
  form.tarikhLahirHari = String(parsed.day);
  form.tarikhLahirBulan = String(parsed.month);
  form.tarikhLahirTahun = String(parsed.year);
  form.umur = String(parsed.age);
}

const tarikhLahir = computed({
  get: () => isoFromBirthParts(form.tarikhLahirHari, form.tarikhLahirBulan, form.tarikhLahirTahun),
  set: (iso: string) => {
    const parts = birthPartsFromIso(iso);
    if (!parts) {
      form.tarikhLahirHari = "";
      form.tarikhLahirBulan = "";
      form.tarikhLahirTahun = "";
      form.umur = "";
      return;
    }
    form.tarikhLahirHari = parts.day;
    form.tarikhLahirBulan = parts.month;
    form.tarikhLahirTahun = parts.year;
    const age = ageFromIsoDate(iso);
    if (age !== null) form.umur = String(age);
  },
});

const takafulKemalanganPakejSuggestion = computed(() => {
  const jumlah = form.jumlahPermohonan;
  return TAKAFUL_KEMALANGAN_PAKEJ.find((p) => jumlah >= p.min && jumlah <= p.max)?.value ?? "";
});

const sokonganRequired = computed(() => form.kategoriPembiayaan === "teman_tekun");

// ---- Dokumen ----
const attachments = ref<(PermohonanAttachment & { verifying?: boolean; classifying?: boolean })[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const uploading = ref(false);

const icVerificationSummary = computed(() => {
  const verified = attachments.value.filter((item) => item.verification?.status === "verified");
  return {
    hasApplicantFront: verified.some(
      (item) => (item.verification?.documentType === "ic_front" || item.verification?.documentType === "ic_combined") && item.verification?.subject !== "spouse",
    ),
    hasApplicantBack: verified.some(
      (item) => (item.verification?.documentType === "ic_back" || item.verification?.documentType === "ic_combined") && item.verification?.subject !== "spouse",
    ),
    hasSpouseFront: verified.some(
      (item) => (item.verification?.documentType === "ic_front" || item.verification?.documentType === "ic_combined") && item.verification?.subject === "spouse",
    ),
    hasSpouseBack: verified.some(
      (item) => (item.verification?.documentType === "ic_back" || item.verification?.documentType === "ic_combined") && item.verification?.subject === "spouse",
    ),
  };
});

const spouseIcRequired = computed(() => adaPasangan.value && Boolean(form.noIcPasangan.trim()));

async function ensureBackendRecord(): Promise<{ id: number; token: string } | null> {
  if (backendId.value && accessToken.value) {
    return { id: backendId.value, token: accessToken.value };
  }
  try {
    const res = await createPemohonPermohonan({ status: "Draf", nama: form.nama || "Draf" });
    backendId.value = res.data.id;
    accessToken.value = res.data.pemohonAccessToken;
    persistDraft();
    return { id: res.data.id, token: res.data.pemohonAccessToken };
  } catch (e) {
    toast.error("Gagal menyediakan permohonan", e instanceof Error ? e.message : undefined);
    return null;
  }
}

function buildDetails(): Record<string, unknown> {
  return { ...form, adaPasangan: adaPasangan.value, tujuan: form.tujuanPembiayaan };
}

function persistDraft() {
  pemohon.saveDraft({
    form: { ...form },
    adaPasangan: adaPasangan.value,
    backendId: backendId.value,
    accessToken: accessToken.value,
    attachments: attachments.value,
    currentStep: currentStep.value,
  });
}

onMounted(async () => {
  const draft = pemohon.loadDraft() as Record<string, unknown> | null;
  if (draft?.form) {
    Object.assign(form, draft.form as Record<string, unknown>);
    adaPasangan.value = Boolean(draft.adaPasangan);
    backendId.value = (draft.backendId as number | null) ?? null;
    accessToken.value = (draft.accessToken as string | null) ?? null;
    attachments.value = (draft.attachments as PermohonanAttachment[]) ?? [];
    currentStep.value = (draft.currentStep as number) ?? 0;
    toast.info("Draf dimuat", "Permohonan draf anda telah dipulihkan.");
  }
});

async function onFilesSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = input.files ? Array.from(input.files) : [];
  input.value = "";
  if (!files.length) return;

  const record = await ensureBackendRecord();
  if (!record) return;

  for (const file of files) {
    if (file.size > SPPT_DOCUMENT_MAX_BYTES) {
      toast.error(`Fail "${file.name}" melebihi had ${SPPT_DOCUMENT_MAX_LABEL}.`);
      continue;
    }

    uploading.value = true;
    try {
      let suggestedClass: string | undefined;
      try {
        const classifyRes = await classifyPemohonPermohonanDocument(
          file,
          form.noIcBaru,
          form.nama,
          spouseIcRequired.value ? form.noIcPasangan : undefined,
          spouseIcRequired.value ? form.namaPasangan : undefined,
        );
        suggestedClass = classifyRes.data.classification.suggestedClass;
      } catch {
        // Classification is a convenience suggestion — proceed without it if it fails.
      }

      const uploadRes = await uploadPemohonPermohonanDocument(record.id, record.token, file, suggestedClass);
      const attachment: PermohonanAttachment & { verifying?: boolean } = { ...uploadRes.data };
      attachments.value.push(attachment);

      const ext = file.name.split(".").pop()?.toLowerCase() ?? "";
      if (["jpg", "jpeg", "png"].includes(ext)) {
        const index = attachments.value.length - 1;
        attachments.value[index].verifying = true;
        try {
          const verifyRes = await verifyPemohonPermohonanDocument(
            file,
            form.noIcBaru,
            form.nama,
            spouseIcRequired.value ? form.noIcPasangan : undefined,
            spouseIcRequired.value ? form.namaPasangan : undefined,
          );
          attachments.value[index] = { ...attachments.value[index], verification: verifyRes.data.verification, verifying: false };
        } catch {
          attachments.value[index].verifying = false;
        }
      }

      persistDraft();
    } catch (e) {
      toast.error(`Gagal memuat naik "${file.name}"`, e instanceof Error ? e.message : undefined);
    } finally {
      uploading.value = false;
    }
  }
}

function triggerFileInput() {
  fileInputRef.value?.click();
}

async function removeAttachment(attachmentId: string) {
  if (!backendId.value || !accessToken.value) return;
  try {
    await deletePemohonPermohonanDocument(backendId.value, accessToken.value, attachmentId);
    attachments.value = attachments.value.filter((item) => item.id !== attachmentId);
    persistDraft();
  } catch (e) {
    toast.error("Gagal memadam dokumen", e instanceof Error ? e.message : undefined);
  }
}

function documentClassOptionLabel(value: string) {
  return documentClassLabel(value);
}

async function saveDraft() {
  draftSaving.value = true;
  try {
    const record = await ensureBackendRecord();
    if (!record) return;
    await updatePemohonPermohonan(record.id, record.token, {
      status: "Draf",
      nama: form.nama || "Draf",
      kategoriPembiayaan: PRODUK_OPTIONS.find((p) => p.value === form.kategoriPembiayaan)?.label,
      jumlahPermohonan: form.jumlahPermohonan,
      pemohonEmail: form.email || undefined,
      pemohonTelefon: form.noTelefonBimbit || undefined,
      details: buildDetails(),
    });
    persistDraft();
    toast.success("Draf Disimpan", "Anda boleh menyambung permohonan ini kemudian.");
  } catch (e) {
    toast.error("Gagal menyimpan draf", e instanceof Error ? e.message : undefined);
  } finally {
    draftSaving.value = false;
  }
}

function next() {
  if (currentStep.value < PERMOHONAN_BARU_STEPS.length - 1) {
    currentStep.value += 1;
    persistDraft();
  }
}

function back() {
  if (currentStep.value > 0) {
    currentStep.value -= 1;
    persistDraft();
  }
}

async function submit() {
  if (!form.nama.trim() || !form.noIcBaru.trim()) {
    toast.error("Sila lengkapkan maklumat pemohon (nama & no. kad pengenalan) sebelum menghantar.");
    currentStep.value = 1;
    return;
  }
  if (!form.kebenaranKredit) {
    toast.error("Sila bersetuju dengan Kebenaran Penzahiran Maklumat Kredit sebelum menghantar.");
    currentStep.value = PERMOHONAN_BARU_STEPS.findIndex((s) => s.id === "kebenaran");
    return;
  }
  if (attachments.value.length === 0) {
    toast.error("Sila lampirkan sekurang-kurangnya satu dokumen sokongan.");
    currentStep.value = PERMOHONAN_BARU_STEPS.length - 1;
    return;
  }

  submitting.value = true;
  try {
    const record = await ensureBackendRecord();
    if (!record) return;

    const res = await updatePemohonPermohonan(record.id, record.token, {
      status: "Dalam Semakan",
      nama: form.nama,
      kategoriPembiayaan: PRODUK_OPTIONS.find((p) => p.value === form.kategoriPembiayaan)?.label,
      jumlahPermohonan: form.jumlahPermohonan,
      pemohonEmail: form.email || undefined,
      pemohonTelefon: form.noTelefonBimbit || undefined,
      details: buildDetails(),
    });

    const produkLabel = PRODUK_OPTIONS.find((p) => p.value === form.kategoriPembiayaan)?.label || "Pembiayaan";
    pemohon.createPermohonan({ produk: produkLabel, jumlah: form.jumlahPermohonan });
    pemohon.clearDraft();
    toast.success("Permohonan Dihantar", `Rujukan permohonan anda: ${res.data.noRujukan}`);
    router.push({ name: "pemohon-permohonan" });
  } catch (e) {
    toast.error("Gagal menghantar permohonan", e instanceof Error ? e.message : undefined);
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="pemohon-hero rounded-b-2xl p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-white">Borang Permohonan Pembiayaan</h1>
        <p class="mt-1 text-sm text-blue-100">Isi maklumat berikut untuk memohon pembiayaan baharu.</p>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-6 md:hidden">
          <div class="mb-2 flex items-center justify-between text-sm">
            <span class="font-medium text-slate-500">Langkah {{ currentStep + 1 }} / {{ PERMOHONAN_BARU_STEPS.length }}</span>
            <span class="font-semibold text-blue-700">{{ PERMOHONAN_BARU_STEPS[currentStep].label }}</span>
          </div>
          <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div
              class="h-full rounded-full bg-blue-600 transition-all"
              :style="{ width: `${((currentStep + 1) / PERMOHONAN_BARU_STEPS.length) * 100}%` }"
            />
          </div>
        </div>

        <div class="mb-6 hidden md:block">
          <SpptStepper :steps="[...PERMOHONAN_BARU_STEPS]" :current-step="currentStep" @step-click="(i) => (currentStep = i)" />
        </div>

        <div class="w-full">
          <!-- 0: Asas -->
          <div v-if="currentStep === 0" class="space-y-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Kategori Pembiayaan</label>
              <select v-model="form.kategoriPembiayaan" :class="inputClass">
                <option value="" disabled>Pilih kategori</option>
                <option v-for="p in PRODUK_OPTIONS" :key="p.value" :value="p.value">{{ p.label }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Jumlah Permohonan (RM)</label>
              <input v-model.number="form.jumlahPermohonan" type="number" min="1000" :class="inputClass" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Tujuan Pembiayaan</label>
              <textarea v-model="form.tujuanPembiayaan" rows="2" :class="inputClass" placeholder="cth: Modal pusingan perniagaan" />
            </div>
          </div>

          <!-- 1: Pemohon -->
          <div v-else-if="currentStep === 1" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Nama Penuh *</label>
                <input v-model="form.nama" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">No. Kad Pengenalan (Baru) *</label>
                <input v-model="form.noIcBaru" type="text" :class="inputClass" placeholder="YYMMDD-NN-GGGG" @input="onNoIcBaruInput" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">No. Kad Pengenalan (Lama)</label>
                <input v-model="form.noIcLama" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Jantina *</label>
                <select v-model="form.jantina" :class="inputClass">
                  <option value="" disabled>Pilih</option>
                  <option v-for="opt in JANTINA_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Agama *</label>
                <select v-model="form.agama" :class="inputClass">
                  <option value="" disabled>Pilih</option>
                  <option v-for="opt in AGAMA_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Tarikh Lahir</label>
                <SpptDateInput v-model="tarikhLahir" :input-class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Umur</label>
                <input :value="form.umur" type="text" readonly :class="[inputClass, 'bg-slate-50 text-slate-500']" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Bangsa *</label>
                <select v-model="form.bangsa" :class="inputClass">
                  <option value="" disabled>Pilih</option>
                  <option v-for="opt in BANGSA_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
              <div v-if="form.bangsa === 'Lain-lain'">
                <label class="mb-1 block text-sm font-medium text-slate-700">Nyatakan Bangsa *</label>
                <input v-model="form.kaum" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Taraf Perkahwinan *</label>
                <select v-model="form.tarafPerkahwinan" :class="inputClass" @change="onTarafPerkahwinanChange">
                  <option value="" disabled>Pilih</option>
                  <option v-for="opt in TARAF_PERKAHWINAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Bilangan Tanggungan</label>
                <input v-model="form.bilanganTanggungan" type="number" min="0" :class="inputClass" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Taraf Pendidikan *</label>
                <select v-model="form.tarafPendidikan" :class="inputClass">
                  <option value="" disabled>Pilih</option>
                  <option v-for="opt in TARAF_PENDIDIKAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
            </div>
            <div class="space-y-2 pt-1">
              <label class="flex items-center gap-2 text-sm text-slate-700">
                <input v-model="form.oku" type="checkbox" class="rounded border-slate-300" /> Orang Kurang Upaya (OKU)
              </label>
              <label class="flex items-center gap-2 text-sm text-slate-700">
                <input v-model="form.diberhentikanPandemik" type="checkbox" class="rounded border-slate-300" /> Diberhentikan kerja akibat pandemik
              </label>
              <label class="flex items-center gap-2 text-sm text-slate-700">
                <input v-model="form.asnafBerdaftar" type="checkbox" class="rounded border-slate-300" /> Asnaf berdaftar
              </label>
            </div>
          </div>

          <!-- 2: Alamat -->
          <div v-else-if="currentStep === 2" class="space-y-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Alamat Kediaman *</label>
              <textarea v-model="form.alamat" rows="2" :class="inputClass" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Poskod *</label>
                <input v-model="form.poskod" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Negeri *</label>
                <select v-model="form.negeri" :class="inputClass">
                  <option value="" disabled>Pilih</option>
                  <option v-for="opt in NEGERI_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">No. Telefon Rumah</label>
                <input v-model="form.noTelefonRumah" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">No. Telefon Bimbit *</label>
                <input v-model="form.noTelefonBimbit" type="text" :class="inputClass" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Emel *</label>
                <input v-model="form.email" type="email" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Facebook</label>
                <input v-model="form.facebook" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Instagram</label>
                <input v-model="form.instagram" type="text" :class="inputClass" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Status Kediaman</label>
                <select v-model="form.statusKediaman" :class="inputClass">
                  <option value="">Pilih</option>
                  <option v-for="opt in STATUS_KEDIAMAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- 3: Pekerjaan -->
          <div v-else-if="currentStep === 3" class="space-y-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Status Pekerjaan *</label>
              <select v-model="form.statusPekerjaan" :class="inputClass">
                <option value="" disabled>Pilih</option>
                <option v-for="opt in STATUS_PEKERJAAN_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>
            <template v-if="form.statusPekerjaan === 'bekerja'">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Sektor Pekerjaan *</label>
                  <select v-model="form.sektorPekerjaan" :class="inputClass">
                    <option value="" disabled>Pilih</option>
                    <option v-for="opt in SEKTOR_PEKERJAAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                  </select>
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Jawatan</label>
                  <input v-model="form.jawatan" type="text" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Status Jawatan</label>
                  <select v-model="form.statusJawatan" :class="inputClass">
                    <option value="">Pilih</option>
                    <option v-for="opt in STATUS_JAWATAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                  </select>
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Pendapatan Bulanan (RM) *</label>
                  <input v-model="form.pendapatan" type="number" min="0" :class="inputClass" />
                </div>
                <div class="col-span-2">
                  <label class="mb-1 block text-sm font-medium text-slate-700">Nama Majikan</label>
                  <input v-model="form.namaMajikan" type="text" :class="inputClass" />
                </div>
                <div class="col-span-2">
                  <label class="mb-1 block text-sm font-medium text-slate-700">Alamat Majikan</label>
                  <textarea v-model="form.alamatMajikan" rows="2" :class="inputClass" />
                </div>
                <div class="col-span-2">
                  <label class="mb-1 block text-sm font-medium text-slate-700">No. Telefon Majikan</label>
                  <input v-model="form.noTelefonMajikan" type="text" :class="inputClass" />
                </div>
              </div>
            </template>
          </div>

          <!-- 4: Pasangan -->
          <div v-else-if="currentStep === 4" class="space-y-4">
            <label class="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
              <input v-model="adaPasangan" type="checkbox" class="mt-0.5 rounded border-slate-300" />
              Saya mempunyai pasangan dan ingin mengisi maklumat pasangan
            </label>
            <template v-if="adaPasangan">
              <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                  <label class="mb-1 block text-sm font-medium text-slate-700">Nama Pasangan *</label>
                  <input v-model="form.namaPasangan" type="text" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">No. Kad Pengenalan Pasangan *</label>
                  <input v-model="form.noIcPasangan" type="text" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">No. Pasport Pasangan</label>
                  <input v-model="form.noPassportPasangan" type="text" :class="inputClass" />
                </div>
                <div class="col-span-2">
                  <label class="mb-1 block text-sm font-medium text-slate-700">Pekerjaan Pasangan</label>
                  <input v-model="form.pekerjaanPasangan" type="text" :class="inputClass" />
                </div>
                <div class="col-span-2">
                  <label class="mb-1 block text-sm font-medium text-slate-700">Alamat Majikan Pasangan</label>
                  <textarea v-model="form.alamatMajikanPasangan" rows="2" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Poskod Majikan Pasangan</label>
                  <input v-model="form.poskodMajikanPasangan" type="text" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">No. Telefon Majikan Pasangan</label>
                  <input v-model="form.noTelefonMajikanPasangan" type="text" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">No. Telefon Bimbit Pasangan</label>
                  <input v-model="form.noTelefonBimbitPasangan" type="text" :class="inputClass" />
                </div>
                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-700">Pendapatan Pasangan (RM)</label>
                  <input v-model="form.pendapatanPasangan" type="number" min="0" :class="inputClass" />
                </div>
              </div>
            </template>
          </div>

          <!-- 5: Perniagaan -->
          <div v-else-if="currentStep === 5" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Nama Perniagaan *</label>
                <input v-model="form.namaPerniagaan" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">No. Pendaftaran SSM</label>
                <input v-model="form.noSsm" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Tempoh Berniaga (tahun)</label>
                <input v-model="form.tempohBerniaga" type="text" :class="inputClass" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Alamat Premis Perniagaan</label>
                <textarea v-model="form.alamatPremis" rows="2" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Poskod Premis</label>
                <input v-model="form.poskodPremis" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Anggaran Pendapatan Bulanan (RM)</label>
                <input v-model="form.anggaranPendapatan" type="number" min="0" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">No. Tel Premis</label>
                <input v-model="form.noTelPremis" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">No. Tel Bimbit Premis</label>
                <input v-model="form.noTelBimbitPremis" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Status Premis</label>
                <select v-model="form.statusPremis" :class="inputClass">
                  <option v-for="opt in STATUS_PREMIS_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Pemilikan Perniagaan</label>
                <select v-model="form.pemilikanPerniagaan" :class="inputClass">
                  <option v-for="opt in PEMILIKAN_PERNIAGAAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
              <div v-if="form.pemilikanPerniagaan === 'Sendirian Berhad'" class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Modal Berbayar (RM)</label>
                <input v-model="form.modalBerbayar" type="number" min="0" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Tarikh Daftar Perniagaan</label>
                <SpptDateInput v-model="form.tarikhDaftar" :input-class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Tarikh Tamat Lesen</label>
                <SpptDateInput v-model="form.tarikhTamatLesen" :input-class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Masa Berniaga Dari</label>
                <SpptTimeInput v-model="form.masaBerniagaDari" :input-class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Masa Berniaga Hingga</label>
                <SpptTimeInput v-model="form.masaBerniagaHingga" :input-class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Nilai Aset Perniagaan (RM)</label>
                <input v-model="form.nilaiAset" type="number" min="0" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Sumber Modal</label>
                <input v-model="form.sumberModal" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Agensi Kursus Dihadiri</label>
                <select v-model="form.kursusAgensi" :class="inputClass">
                  <option value="">Pilih</option>
                  <option v-for="opt in AGENSI_KURSUS_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
              <div v-if="form.kursusAgensi === 'Lain-lain'">
                <label class="mb-1 block text-sm font-medium text-slate-700">Nyatakan Kursus</label>
                <input v-model="form.kursusLain" type="text" :class="inputClass" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Perniagaan Terdahulu (jika ada)</label>
                <input v-model="form.perniagaanTerdahulu" type="text" :class="inputClass" />
              </div>
            </div>

            <label class="flex items-start gap-2 text-sm text-slate-700">
              <input v-model="form.keahlianPersatuan" type="checkbox" class="mt-0.5 rounded border-slate-300" />
              Ahli persatuan / koperasi perniagaan
            </label>
            <input
              v-if="form.keahlianPersatuan"
              v-model="form.keahlianPersatuanNyata"
              type="text"
              :class="inputClass"
              placeholder="Nyatakan nama persatuan"
            />

            <label class="flex items-start gap-2 text-sm text-slate-700">
              <input v-model="form.pengiktirafan" type="checkbox" class="mt-0.5 rounded border-slate-300" />
              Perniagaan pernah menerima pengiktirafan / anugerah
            </label>
            <input
              v-if="form.pengiktirafan"
              v-model="form.pengiktirafanNyata"
              type="text"
              :class="inputClass"
              placeholder="Nyatakan pengiktirafan"
            />

            <label class="flex items-start gap-2 text-sm text-slate-700">
              <input v-model="form.pembiayaanSediaAda" type="checkbox" class="mt-0.5 rounded border-slate-300" />
              Mempunyai pembiayaan/pinjaman perniagaan sedia ada
            </label>
            <div v-if="form.pembiayaanSediaAda" class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Institusi Pembiayaan</label>
                <select v-model="form.institusiPembiayaan" :class="inputClass">
                  <option value="">Pilih</option>
                  <option v-for="opt in INSTITUSI_PEMBIAYAAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Jumlah Pembiayaan Sedia Ada (RM)</label>
                <input v-model="form.jumlahPembiayaanSediaAda" type="number" min="0" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Baki Pembiayaan (RM)</label>
                <input v-model="form.bakiPembiayaan" type="number" min="0" :class="inputClass" />
              </div>
            </div>
          </div>

          <!-- 6: Pembiayaan -->
          <div v-else-if="currentStep === 6" class="space-y-4">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p class="text-sm text-slate-500">Ringkasan Permohonan</p>
              <p class="mt-1 text-lg font-semibold text-slate-900">
                {{ PRODUK_OPTIONS.find((p) => p.value === form.kategoriPembiayaan)?.label || "-" }} — RM {{ form.jumlahPermohonan.toLocaleString("ms-MY") }}
              </p>
              <p v-if="form.tujuanPembiayaan" class="mt-1 text-sm text-slate-600">{{ form.tujuanPembiayaan }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Tempoh Pembiayaan (bulan) *</label>
                <input v-model="form.tempohPembiayaan" type="number" min="1" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kekerapan Bayaran</label>
                <select v-model="form.kekerapanBayaran" :class="inputClass">
                  <option v-for="opt in KEEKERAPAN_BAYARAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- 7: Sokongan -->
          <div v-else-if="currentStep === 7" class="space-y-4">
            <p class="text-sm text-slate-500">
              Maklumat sokongan kumpulan
              <span v-if="sokonganRequired" class="font-medium text-blue-700">(diperlukan untuk Teman TEKUN)</span>
              <span v-else>(hanya diperlukan untuk produk Teman TEKUN)</span>
            </p>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Nama Penyokong {{ sokonganRequired ? "*" : "" }}</label>
                <input v-model="form.sokonganNama" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">No. KP Penyokong {{ sokonganRequired ? "*" : "" }}</label>
                <input v-model="form.sokonganNoKp" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Tempoh Perkenalan</label>
                <input v-model="form.sokonganTempohPerkenalan" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Tarikh Perbincangan</label>
                <SpptDateInput v-model="form.sokonganTarikhPerbincangan" :input-class="inputClass" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Jumlah Pembiayaan Disokong (RM) {{ sokonganRequired ? "*" : "" }}</label>
                <input v-model="form.sokonganJumlah" type="number" min="0" :class="inputClass" />
              </div>
            </div>

            <p class="pt-2 text-sm font-medium text-slate-700">Perakuan</p>
            <div class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Jawatan</label>
                <input v-model="form.perakuanJawatan" type="text" :class="inputClass" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Nama</label>
                <input v-model="form.perakuanNama" type="text" :class="inputClass" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
                <textarea v-model="form.perakuanAlamat" rows="2" :class="inputClass" />
              </div>
              <div class="col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">No. Telefon</label>
                <input v-model="form.perakuanNoTel" type="text" :class="inputClass" />
              </div>
            </div>

            <p class="pt-2 text-sm font-medium text-slate-700">Perujuk 1</p>
            <div class="grid grid-cols-2 gap-3">
              <input v-model="form.perujuk1Nama" type="text" placeholder="Nama" :class="inputClass" />
              <input v-model="form.perujuk1NoKp" type="text" placeholder="No. KP" :class="inputClass" />
              <input v-model="form.perujuk1Hubungan" type="text" placeholder="Hubungan" :class="inputClass" />
              <input v-model="form.perujuk1NoTel" type="text" placeholder="No. Telefon" :class="inputClass" />
              <textarea v-model="form.perujuk1Alamat" rows="2" placeholder="Alamat" :class="[inputClass, 'col-span-2']" />
            </div>

            <p class="pt-2 text-sm font-medium text-slate-700">Perujuk 2</p>
            <div class="grid grid-cols-2 gap-3">
              <input v-model="form.perujuk2Nama" type="text" placeholder="Nama" :class="inputClass" />
              <input v-model="form.perujuk2NoKp" type="text" placeholder="No. KP" :class="inputClass" />
              <input v-model="form.perujuk2Hubungan" type="text" placeholder="Hubungan" :class="inputClass" />
              <input v-model="form.perujuk2NoTel" type="text" placeholder="No. Telefon" :class="inputClass" />
              <textarea v-model="form.perujuk2Alamat" rows="2" placeholder="Alamat" :class="[inputClass, 'col-span-2']" />
            </div>
          </div>

          <!-- 8: Takaful -->
          <div v-else-if="currentStep === 8" class="space-y-4">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
              <label class="flex items-start gap-2 text-sm text-blue-900">
                <input v-model="form.takafulPembiayaan" type="checkbox" disabled class="mt-0.5 rounded border-slate-300" />
                Takaful Pembiayaan Berkelompok (wajib bagi semua permohonan)
              </label>
            </div>

            <label class="flex items-start gap-2 text-sm text-slate-700">
              <input v-model="form.takafulKemalangan" type="checkbox" class="mt-0.5 rounded border-slate-300" />
              Sertai Takaful Kemalangan Diri
            </label>
            <div v-if="form.takafulKemalangan">
              <label class="mb-1 block text-sm font-medium text-slate-700">Pakej Takaful Kemalangan</label>
              <select v-model="form.takafulKemalanganPakej" :class="inputClass">
                <option value="">Pilih ({{ TAKAFUL_KEMALANGAN_PAKEJ.find((p) => p.value === takafulKemalanganPakejSuggestion)?.label ?? "cadangan berdasarkan jumlah" }})</option>
                <option v-for="opt in TAKAFUL_KEMALANGAN_PAKEJ" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>

            <label class="flex items-start gap-2 text-sm text-slate-700">
              <input v-model="form.perkeso" type="checkbox" class="mt-0.5 rounded border-slate-300" />
              Sertai PERKESO Pekerjaan Sendiri
            </label>
            <div v-if="form.perkeso">
              <label class="mb-1 block text-sm font-medium text-slate-700">Pakej PERKESO</label>
              <select v-model="form.perkesoPakej" :class="inputClass">
                <option value="">Pilih</option>
                <option v-for="opt in PERKESO_PAKEJ" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
            </div>
          </div>

          <!-- 9: Wasiat -->
          <div v-else-if="currentStep === 9" class="space-y-4">
            <label class="flex items-start gap-2 text-sm text-slate-700">
              <input v-model="form.wasiat" type="checkbox" class="mt-0.5 rounded border-slate-300" />
              Saya ingin mendaftar Wasiat bersama permohonan ini
            </label>
            <div v-if="form.wasiat">
              <label class="mb-1 block text-sm font-medium text-slate-700">Jumlah Wasiat (RM)</label>
              <input v-model="form.wasiatJumlah" type="number" min="0" :class="inputClass" />
            </div>
          </div>

          <!-- 10: Kebenaran -->
          <div v-else-if="currentStep === 10" class="space-y-4">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-relaxed text-slate-600">
              Saya dengan ini membenarkan TEKUN Nasional untuk mendapatkan, menyemak, dan berkongsi maklumat kredit
              saya daripada institusi/agensi pelaporan kredit yang berkaitan bagi tujuan penilaian permohonan
              pembiayaan ini.
            </div>
            <label class="flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm font-medium text-blue-900">
              <input v-model="form.kebenaranKredit" type="checkbox" class="mt-0.5 rounded border-slate-300" />
              Saya bersetuju dengan Kebenaran Penzahiran Maklumat Kredit di atas *
            </label>
          </div>

          <!-- 11: Dokumen -->
          <div v-else class="space-y-3">
            <p class="text-sm text-slate-500">
              Muat naik dokumen sokongan (Kad Pengenalan depan &amp; belakang, Lesen Perniagaan / SSM, Penyata Bank, dll).
              Format PDF/JPG/PNG, maks {{ SPPT_DOCUMENT_MAX_LABEL }} setiap fail.
            </p>

            <div v-if="spouseIcRequired" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
              Pasangan didaftarkan — sila sertakan juga salinan MyKad depan &amp; belakang pasangan.
            </div>

            <label
              class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-lg border border-dashed border-slate-300 py-6 text-sm font-medium text-slate-500 transition-colors hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600"
              @click="triggerFileInput"
            >
              <Upload class="h-5 w-5" />
              Klik untuk pilih fail
              <input ref="fileInputRef" type="file" accept=".pdf,image/*" multiple class="hidden" @change="onFilesSelected" />
            </label>
            <p v-if="uploading" class="flex items-center gap-1.5 text-xs text-blue-600">
              <Loader2 class="h-3.5 w-3.5 animate-spin" /> Memproses dokumen...
            </p>

            <div v-if="attachments.length" class="space-y-2">
              <div
                v-for="item in attachments"
                :key="item.id"
                class="rounded-lg border border-slate-200 p-3"
              >
                <div class="flex min-w-0 items-center gap-2">
                  <FileText class="h-4 w-4 shrink-0 text-slate-400" />
                  <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700" :title="item.name">{{ item.name }}</span>
                  <button type="button" class="shrink-0 text-slate-400 transition-colors hover:text-rose-600" @click="removeAttachment(item.id)">
                    <X class="h-3.5 w-3.5" />
                  </button>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-2">
                  <select
                    v-if="item.documentClass"
                    :value="item.documentClass"
                    class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700"
                    disabled
                  >
                    <option :value="item.documentClass">{{ documentClassOptionLabel(item.documentClass) }}</option>
                  </select>
                  <span v-else class="rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-500">
                    {{ SPPT_DOCUMENT_CLASSES.find((c) => c.value === "lain_lain")?.label }}
                  </span>

                  <span v-if="item.verifying" class="flex items-center gap-1 text-xs text-blue-600">
                    <Loader2 class="h-3 w-3 animate-spin" /> Mengesahkan...
                  </span>
                  <span
                    v-else-if="item.verification?.status === 'verified'"
                    class="flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700"
                  >
                    <ShieldCheck class="h-3 w-3" /> Disahkan
                  </span>
                  <span
                    v-else-if="item.verification?.status === 'failed'"
                    class="flex items-center gap-1 rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700"
                  >
                    <XCircle class="h-3 w-3" /> {{ item.verification.message }}
                  </span>
                </div>
              </div>
            </div>

            <div v-if="spouseIcRequired" class="mt-2 grid grid-cols-2 gap-2 text-xs">
              <p :class="icVerificationSummary.hasApplicantFront ? 'text-emerald-600' : 'text-slate-400'">
                <CheckCircle2 class="mr-1 inline h-3 w-3" />IC Pemohon Depan
              </p>
              <p :class="icVerificationSummary.hasApplicantBack ? 'text-emerald-600' : 'text-slate-400'">
                <CheckCircle2 class="mr-1 inline h-3 w-3" />IC Pemohon Belakang
              </p>
              <p :class="icVerificationSummary.hasSpouseFront ? 'text-emerald-600' : 'text-slate-400'">
                <CheckCircle2 class="mr-1 inline h-3 w-3" />IC Pasangan Depan
              </p>
              <p :class="icVerificationSummary.hasSpouseBack ? 'text-emerald-600' : 'text-slate-400'">
                <CheckCircle2 class="mr-1 inline h-3 w-3" />IC Pasangan Belakang
              </p>
            </div>
          </div>

          <div class="mt-6 border-t border-slate-100 pt-4">
            <div class="space-y-2 sm:hidden">
              <button
                type="button"
                class="flex w-full items-center justify-center gap-1.5 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 disabled:opacity-50"
                :disabled="draftSaving"
                @click="saveDraft"
              >
                <Save class="h-4 w-4" />
                Simpan Draf
              </button>

              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="flex items-center justify-center gap-1.5 rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 disabled:opacity-40"
                  :disabled="currentStep === 0"
                  @click="back"
                >
                  <ArrowLeft class="h-4 w-4" />
                  Kembali
                </button>

                <button
                  v-if="currentStep < PERMOHONAN_BARU_STEPS.length - 1"
                  type="button"
                  class="flex items-center justify-center gap-1.5 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                  @click="next"
                >
                  Seterusnya
                  <ArrowRight class="h-4 w-4" />
                </button>
                <button
                  v-else
                  type="button"
                  class="flex items-center justify-center gap-1.5 rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:opacity-50"
                  :disabled="submitting"
                  @click="submit"
                >
                  <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                  <Send v-else class="h-4 w-4" />
                  Hantar
                </button>
              </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:justify-between">
              <button
                type="button"
                class="flex items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 disabled:opacity-40"
                :disabled="currentStep === 0"
                @click="back"
              >
                <ArrowLeft class="h-4 w-4" />
                Kembali
              </button>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="flex items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 disabled:opacity-50"
                  :disabled="draftSaving"
                  @click="saveDraft"
                >
                  <Loader2 v-if="draftSaving" class="h-4 w-4 animate-spin" />
                  <Save v-else class="h-4 w-4" />
                  Simpan Draf
                </button>

                <button
                  v-if="currentStep < PERMOHONAN_BARU_STEPS.length - 1"
                  type="button"
                  class="flex items-center gap-1.5 rounded-md bg-blue-600 px-4 py-1.5 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                  @click="next"
                >
                  Seterusnya
                  <ArrowRight class="h-4 w-4" />
                </button>
                <button
                  v-else
                  type="button"
                  class="flex items-center gap-1.5 rounded-md bg-green-600 px-4 py-1.5 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:opacity-50"
                  :disabled="submitting"
                  @click="submit"
                >
                  <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                  <Send v-else class="h-4 w-4" />
                  Hantar Permohonan
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
