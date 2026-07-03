<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { computed, ref } from "vue";
import { useRouter } from "vue-router";
import { ArrowLeft, ArrowRight, FileText, Paperclip, Save, Trash2, Upload } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptStepper from "@/components/sppt/SpptStepper.vue";
import {
  AGAMA_OPTIONS,
  AGENSI_KURSUS_OPTIONS,
  INSTITUSI_PEMBIAYAAN_OPTIONS,
  JANTINA_OPTIONS,
  KEEKERAPAN_BAYARAN_OPTIONS,
  NEGERI_OPTIONS,
  PEMILIKAN_PERNIAGAAN_OPTIONS,
  PERKESO_PAKEJ,
  SEKTOR_PERNIAGAAN_OPTIONS,
  STATUS_KEDIAMAN_OPTIONS,
  STATUS_PERNIAGAAN_OPTIONS,
  STATUS_PREMIS_OPTIONS,
  TAKAFUL_KEMALANGAN_PAKEJ,
  TARAF_PENDIDIKAN_OPTIONS,
  TARAF_PERKAHWINAN_OPTIONS,
} from "@/config/sppt-options";

const { t, tp } = useI18n();

const router = useRouter();
const saving = ref(false);
const saved = ref(false);
const isDragOver = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

const attachments = ref<{ file: File; id: string }[]>([]);

const BULAN_OPTIONS = [
  "Januari", "Februari", "Mac", "April", "Mei", "Jun",
  "Julai", "Ogos", "September", "Oktober", "November", "Disember",
];

function formatSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function onFileSelect(e: Event) {
  const input = e.target as HTMLInputElement;
  const files = input.files ? Array.from(input.files) : [];
  files.forEach((file) => {
    attachments.value.push({ file, id: `${Date.now()}-${Math.random().toString(36).slice(2)}` });
  });
  input.value = "";
}

function removeAttachment(id: string) {
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
  files.forEach((file) => {
    attachments.value.push({ file, id: `${Date.now()}-${Math.random().toString(36).slice(2)}` });
  });
}

function triggerFileInput() {
  fileInputRef.value?.click();
}

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

function goNext() {
  if (currentStep.value < totalSteps - 1) {
    currentStep.value += 1;
    validationErrors.value = [];
  }
}

function goPrev() {
  if (currentStep.value > 0) {
    currentStep.value -= 1;
    validationErrors.value = [];
  }
}

function goToStep(index: number) {
  if (index >= 0 && index < totalSteps) {
    currentStep.value = index;
    validationErrors.value = [];
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
  noUsahawan: "U-005",
  nama: "Ali bin Hassan",
  noIcBaru: "900512-10-1234",
  noIcLama: "",
  jantina: "L",
  agama: "islam",
  tarikhLahirHari: "12",
  tarikhLahirBulan: "5",
  tarikhLahirTahun: "1990",
  bangsa: "Melayu",
  kaum: "",
  umur: "34",
  tarafPerkahwinan: "Berkahwin",
  bilanganTanggungan: "3",
  oku: false,
  diberhentikanPandemik: false,
  asnafBerdaftar: false,
  tarafPendidikan: "SPM",

  // Alamat Kediaman
  alamat: "No. 12, Jalan Merdeka, 50000 Kuala Lumpur",
  poskod: "50000",
  negeri: "Wilayah Persekutuan Kuala Lumpur",
  noTelefonRumah: "",
  noTelefonBimbit: "012-3456789",
  email: "ali.hassan@email.com",
  facebook: "",
  instagram: "",
  statusKediaman: "Sendiri",

  // Pekerjaan Sekarang
  pekerjaanSekarang: "Usahawan",
  pendapatan: "3500",
  pendapatanBulan: "1",
  namaMajikan: "",
  alamatMajikan: "",
  noTelefonMajikan: "",

  // Maklumat Pasangan (jika berkenaan)
  namaPasangan: "Siti binti Hassan",
  noIcPasangan: "920315-08-5678",
  noPassportPasangan: "",
  pekerjaanPasangan: "Suri rumah",
  alamatMajikanPasangan: "",
  poskodMajikanPasangan: "",
  noTelefonMajikanPasangan: "",
  noTelefonBimbitPasangan: "",
  pendapatanPasangan: "0",
  pendapatanPasanganBulan: "1",

  // Pembiayaan
  jumlahPermohonan: "50000",
  tempohPembiayaan: "60",
  kekerapanBayaran: "Bulanan",
  tujuan: "Modal pusingan dan pembelian stok",

  // F: Maklumat Perniagaan
  namaPerniagaan: "Kedai Runcit Ali",
  noSsm: "",
  tempohBerniaga: "3",
  alamatPremis: "",
  poskodPremis: "",
  anggaranPendapatan: "5000",
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
  masaBerniagaDari: "pagi",
  masaBerniagaHingga: "petang",
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
  if (!f.noIcBaru?.trim() && !f.noIcLama?.trim()) pemohonMsgs.push("No. Kad Pengenalan (Baru atau Lama) diperlukan");
  if (!f.jantina) pemohonMsgs.push("Jantina diperlukan");
  if (!f.agama) pemohonMsgs.push("Agama diperlukan");
  if (!f.tarafPerkahwinan?.trim()) pemohonMsgs.push("Taraf Perkahwinan diperlukan");
  if (!f.tarafPendidikan?.trim()) pemohonMsgs.push("Taraf Pendidikan diperlukan");
  if (!f.bangsa?.trim()) pemohonMsgs.push("Bangsa diperlukan");
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
  if (!f.pekerjaanSekarang?.trim()) pekerjaanMsgs.push("Pekerjaan Sekarang diperlukan");
  if (!f.pendapatan?.trim() || Number(f.pendapatan) < 0) pekerjaanMsgs.push("Pendapatan diperlukan");
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

  validationErrors.value = errors.sort((a, b) => a.stepIndex - b.stepIndex);
  return errors.length === 0;
}

function simpan() {
  if (!validateForm()) {
    currentStep.value = validationErrors.value[0]?.stepIndex ?? currentStep.value;
    return;
  }
  saving.value = true;
  setTimeout(() => {
    saving.value = false;
    saved.value = true;
  }, 800);
}

const stepsWithErrors = computed(() =>
  validationErrors.value.map((e) => e.stepIndex),
);

function batal() {
  router.push("/admin/permohonan");
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
        title="Daftar Permohonan Baru"
        :breadcrumb="[
          { label: 'Permohonan', to: '/admin/permohonan' },
          { label: 'Pendaftaran Permohonan', to: '/admin/permohonan' },
          { label: 'Daftar Baru' },
        ]"
      />

      <form class="space-y-6" @submit.prevent="simpan">
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <SpptStepper
            :steps="[...STEPS]"
            :current-step="currentStep"
            :steps-with-errors="stepsWithErrors"
            @step-click="goToStep"
          />
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
                <label :class="labelClass">Nama Bank Operasi Perniagaan</label>
                <input v-model="form.namaBank" type="text" :class="inputClass" placeholder="Contoh: Maybank" />
              </div>
              <div>
                <label :class="labelClass">No. Akaun Bank</label>
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
                  <label :class="labelClass">Nama Pemohon</label>
                  <input v-model="form.nama" type="text" :class="inputClass" placeholder="Nama penuh" />
                </div>
              </div>

              <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">No. Kad Pengenalan (Baru)</label>
                  <input v-model="form.noIcBaru" type="text" :class="inputClass" placeholder="YYMMDD-NN-GGGG" />
                </div>
                <div>
                  <label :class="labelClass">No. Kad Pengenalan (Lama)</label>
                  <input v-model="form.noIcLama" type="text" :class="inputClass" placeholder="Jika ada" />
                </div>
              </div>

              <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                  <label :class="labelClass">Jantina</label>
                  <select v-model="form.jantina" :class="inputClass">
                    <option v-for="j in JANTINA_OPTIONS" :key="j.value" :value="j.value">{{ j.label }}</option>
                  </select>
                </div>
                <div>
                  <label :class="labelClass">Agama</label>
                  <select v-model="form.agama" :class="inputClass">
                    <option v-for="a in AGAMA_OPTIONS" :key="a.value" :value="a.value">{{ a.label }}</option>
                  </select>
                </div>
                <div>
                  <label :class="labelClass">Taraf Perkahwinan</label>
                  <select v-model="form.tarafPerkahwinan" :class="inputClass">
                    <option v-for="t in TARAF_PERKAHWINAN_OPTIONS" :key="t" :value="t">{{ t }}</option>
                  </select>
                </div>
                <div>
                  <label :class="labelClass">Bilangan Tanggungan</label>
                  <input v-model="form.bilanganTanggungan" type="text" :class="inputClass" placeholder="0" />
                </div>
              </div>

              <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                  <label :class="labelClass">Tarikh Lahir - Hari</label>
                  <input v-model="form.tarikhLahirHari" type="text" :class="inputClass" placeholder="01-31" maxlength="2" />
                </div>
                <div>
                  <label :class="labelClass">Bulan</label>
                  <select v-model="form.tarikhLahirBulan" :class="inputClass">
                    <option v-for="(b, i) in BULAN_OPTIONS" :key="b" :value="String(i + 1)">{{ b }}</option>
                  </select>
                </div>
                <div>
                  <label :class="labelClass">Tahun</label>
                  <input v-model="form.tarikhLahirTahun" type="text" :class="inputClass" placeholder="1990" maxlength="4" />
                </div>
                <div>
                  <label :class="labelClass">Umur (semasa memohon)</label>
                  <input v-model="form.umur" type="text" :class="inputClass" placeholder="Tahun" />
                </div>
              </div>

              <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Bangsa</label>
                  <input v-model="form.bangsa" type="text" :class="inputClass" placeholder="Melayu" />
                </div>
                <div>
                  <label :class="labelClass">Kaum (jika lain, sila nyatakan)</label>
                  <input v-model="form.kaum" type="text" :class="inputClass" placeholder="Opsional" />
                </div>
              </div>

              <div class="mt-4">
                <label :class="labelClass">Taraf Pendidikan</label>
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
              <label :class="labelClass">Alamat Kediaman</label>
              <textarea v-model="form.alamat" rows="2" :class="inputClass" placeholder="Alamat penuh" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label :class="labelClass">Poskod</label>
                <input v-model="form.poskod" type="text" :class="inputClass" placeholder="50000" />
              </div>
              <div>
                <label :class="labelClass">Negeri</label>
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
                <label :class="labelClass">No. Telefon Bimbit</label>
                <input v-model="form.noTelefonBimbit" type="text" :class="inputClass" placeholder="01X-XXXXXXX" />
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label :class="labelClass">E-mel</label>
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
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Pekerjaan Sekarang</label>
                <input v-model="form.pekerjaanSekarang" type="text" :class="inputClass" placeholder="Contoh: Usahawan" />
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label :class="labelClass">Pendapatan (RM)</label>
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
                <label :class="labelClass">Nama Majikan (jika bekerja)</label>
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
                  <label :class="labelClass">Nama Suami / Isteri</label>
                  <input v-model="form.namaPasangan" type="text" :class="inputClass" placeholder="Nama pasangan" />
                </div>
                <div>
                  <label :class="labelClass">No. Kad Pengenalan</label>
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
                <label :class="labelClass">Nama Perniagaan</label>
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
                <input v-model="form.tarikhDaftar" type="text" :class="inputClass" placeholder="DD/MM/YYYY" />
              </div>
              <div>
                <label :class="labelClass">Tarikh Tamat Lesen</label>
                <input v-model="form.tarikhTamatLesen" type="text" :class="inputClass" placeholder="DD/MM/YYYY" />
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
                <select v-model="form.masaBerniagaDari" :class="inputClass">
                  <option value="pagi">Pagi</option>
                  <option value="petang">Petang</option>
                  <option value="malam">Malam</option>
                </select>
              </div>
              <div>
                <label :class="labelClass">Hingga</label>
                <select v-model="form.masaBerniagaHingga" :class="inputClass">
                  <option value="pagi">Pagi</option>
                  <option value="petang">Petang</option>
                  <option value="malam">Malam</option>
                </select>
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
                <label :class="labelClass">Jumlah Permohonan (RM)</label>
                <input v-model="form.jumlahPermohonan" type="text" :class="inputClass" placeholder="50000" />
              </div>
              <div>
                <label :class="labelClass">Tempoh Pembiayaan (bulan)</label>
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
              <label :class="labelClass">Tujuan Pembiayaan</label>
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
                <label :class="labelClass">Nama</label>
                <input v-model="form.sokonganNama" type="text" :class="inputClass" placeholder="Nama" />
              </div>
              <div>
                <label :class="labelClass">Nombor Kad Pengenalan</label>
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
                <input v-model="form.sokonganTarikhPerbincangan" type="text" :class="inputClass" placeholder="DD/MM/YYYY" />
              </div>
            </div>
            <div>
              <label :class="labelClass">Jumlah Pembiayaan Yang Disokong (RM)</label>
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
                <span class="font-medium">Takaful Pembiayaan Berkelompok (wajib)</span>
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
                <span class="font-medium">Saya membenarkan TEKUN Nasional dan pegawainya untuk:</span>
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
            <h2 class="text-sm font-semibold text-slate-700">Dokumen Sokongan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Lampirkan dokumen seperti Salinan IC, Salinan SSM, Rancangan Perniagaan, Penyata Bank, dll.</p>
          </div>
          <div class="p-4">
            <input
              ref="fileInputRef"
              type="file"
              multiple
              accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
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
              <p class="mt-2 text-xs text-slate-500">PDF, DOC, DOCX, JPG, PNG (maks. 10MB setiap fail)</p>
            </div>
            <div v-if="attachments.length > 0" class="mt-4 space-y-2">
              <p class="text-xs font-medium text-slate-600">{{ attachments.length }} fail dilampirkan</p>
              <div
                v-for="item in attachments"
                :key="item.id"
                class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2"
              >
                <div class="flex min-w-0 items-center gap-3">
                  <FileText class="h-5 w-5 shrink-0 text-slate-400" />
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-900">{{ item.file.name }}</p>
                    <p class="text-xs text-slate-500">{{ formatSize(item.file.size) }}</p>
                  </div>
                </div>
                <button
                  type="button"
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                  @click="removeAttachment(item.id)"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </article>

        <div v-if="saved" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
          Permohonan berjaya didaftarkan. (Dummy – tiada sambungan ke jadual.)
        </div>

        <div
          v-if="validationErrors.length > 0"
          class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3"
        >
          <p class="text-sm font-semibold text-rose-800">Sila lengkapkan maklumat wajib:</p>
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
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
              @click="goNext"
            >
              Seterusnya
              <ArrowRight class="h-4 w-4" />
            </button>
            <button
              v-if="currentStep === totalSteps - 1"
              type="submit"
              :disabled="saving"
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-60"
            >
              <Save class="h-4 w-4" />
              {{ saving ? "Menyimpan..." : "Simpan" }}
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
