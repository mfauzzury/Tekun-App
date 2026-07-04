<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ArrowLeft, ArrowRight, Save, Send, Upload } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import SpptStepper from "@/components/sppt/SpptStepper.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { PERMOHONAN_BARU_STEPS, PRODUK_OPTIONS } from "@/data/portal-dummy";

const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const currentStep = ref(0);
const inputClass = "w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200";

const form = reactive({
  kategoriPembiayaan: "",
  jumlahPermohonan: 20000,
  nama: pemohon.profil.nama,
  noIc: pemohon.profil.noKp,
  jantina: "",
  umur: 30,
  alamat: pemohon.profil.alamat,
  poskod: "",
  negeri: "",
  noTelefon: pemohon.profil.telefon,
  email: pemohon.profil.email,
  pekerjaanSekarang: "Usahawan",
  pendapatanBulanan: 3000,
  namaPerniagaan: pemohon.profil.perniagaan,
  noSsm: pemohon.profil.noSsm,
  tempohBerniaga: "",
  tujuanPembiayaan: "",
});

const documents = reactive<Record<string, string | null>>({
  kadPengenalan: null,
  lesenPerniagaan: null,
  penyataBank: null,
  borangSsm: null,
});

const docLabels: Record<string, string> = {
  kadPengenalan: "Kad Pengenalan (depan & belakang)",
  lesenPerniagaan: "Lesen Perniagaan",
  penyataBank: "Penyata Bank 3 Bulan Terkini",
  borangSsm: "SSM Form 9 / Borang D",
};

onMounted(() => {
  const draft = pemohon.loadDraft();
  if (draft) {
    Object.assign(form, draft);
    toast.info("Draf dimuat", "Permohonan draf anda telah dipulihkan.");
  }
});

function onDocSelected(event: Event, key: string) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) return;
  documents[key] = file.name;
}

function saveDraft() {
  pemohon.saveDraft({ ...form });
  toast.success("Draf Disimpan", "Anda boleh menyambung permohonan ini kemudian.");
}

function next() {
  if (currentStep.value < PERMOHONAN_BARU_STEPS.length - 1) currentStep.value += 1;
}

function back() {
  if (currentStep.value > 0) currentStep.value -= 1;
}

function submit() {
  const produkLabel = PRODUK_OPTIONS.find((p) => p.value === form.kategoriPembiayaan)?.label || "SPUMI";
  const item = pemohon.createPermohonan({ produk: produkLabel, jumlah: form.jumlahPermohonan });
  toast.success("Permohonan Dihantar", `Rujukan permohonan anda: ${item.id}`);
  router.push({ name: "pemohon-permohonan" });
}
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-slate-900">Borang Permohonan Pembiayaan</h1>
        <p class="mt-1 text-sm text-slate-500">Isi maklumat berikut untuk memohon pembiayaan baharu.</p>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-6 flex justify-center overflow-x-hidden">
          <SpptStepper :steps="[...PERMOHONAN_BARU_STEPS]" :current-step="currentStep" @step-click="(i) => (currentStep = i)" />
        </div>

        <div class="mx-auto max-w-xl">
        <!-- Asas -->
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

        <!-- Pemohon -->
        <div v-else-if="currentStep === 1" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Nama Penuh</label>
              <input v-model="form.nama" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">No. Kad Pengenalan</label>
              <input v-model="form.noIc" type="text" :class="inputClass" placeholder="YYMMDD-NN-GGGG" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Jantina</label>
              <select v-model="form.jantina" :class="inputClass">
                <option value="">Pilih</option>
                <option value="lelaki">Lelaki</option>
                <option value="perempuan">Perempuan</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Umur</label>
              <input v-model.number="form.umur" type="number" :class="inputClass" />
            </div>
          </div>
        </div>

        <!-- Alamat -->
        <div v-else-if="currentStep === 2" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
            <textarea v-model="form.alamat" rows="2" :class="inputClass" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Poskod</label>
              <input v-model="form.poskod" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Negeri</label>
              <input v-model="form.negeri" type="text" :class="inputClass" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">No. Telefon</label>
              <input v-model="form.noTelefon" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Emel</label>
              <input v-model="form.email" type="email" :class="inputClass" />
            </div>
          </div>
        </div>

        <!-- Pekerjaan -->
        <div v-else-if="currentStep === 3" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Pekerjaan Sekarang</label>
            <input v-model="form.pekerjaanSekarang" type="text" :class="inputClass" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Pendapatan Bulanan (RM)</label>
            <input v-model.number="form.pendapatanBulanan" type="number" :class="inputClass" />
          </div>
        </div>

        <!-- Perniagaan -->
        <div v-else-if="currentStep === 4" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Nama Perniagaan</label>
            <input v-model="form.namaPerniagaan" type="text" :class="inputClass" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">No. Pendaftaran SSM</label>
              <input v-model="form.noSsm" type="text" :class="inputClass" />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700">Tempoh Berniaga (tahun)</label>
              <input v-model="form.tempohBerniaga" type="text" :class="inputClass" />
            </div>
          </div>
        </div>

        <!-- Pembiayaan -->
        <div v-else-if="currentStep === 5" class="space-y-4">
          <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Ringkasan Permohonan</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">
              {{ PRODUK_OPTIONS.find((p) => p.value === form.kategoriPembiayaan)?.label || "-" }} — RM {{ form.jumlahPermohonan.toLocaleString("ms-MY") }}
            </p>
          </div>
          <p class="text-sm text-slate-500">Sila semak semula maklumat sebelum meneruskan ke langkah dokumen sokongan.</p>
        </div>

        <!-- Dokumen -->
        <div v-else class="space-y-3">
          <p class="text-sm text-slate-500">Muat naik dokumen sokongan yang diperlukan (format PDF/JPG/PNG).</p>
          <label
            v-for="(label, key) in docLabels"
            :key="key"
            class="flex cursor-pointer items-center justify-between rounded-lg border border-dashed border-slate-300 px-4 py-3 transition-colors hover:border-violet-400 hover:bg-violet-50"
          >
            <div class="flex items-center gap-2">
              <Upload class="h-4 w-4 text-slate-400" />
              <span class="text-sm font-medium text-slate-700">{{ label }}</span>
            </div>
            <span class="text-xs" :class="documents[key] ? 'text-emerald-600' : 'text-slate-400'">
              {{ documents[key] || "Belum dimuat naik" }}
            </span>
            <input type="file" accept=".pdf,image/*" class="hidden" @change="onDocSelected($event, key)" />
          </label>
        </div>

        <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
          <button
            type="button"
            class="flex items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 disabled:opacity-40"
            :disabled="currentStep === 0"
            @click="back"
          >
            <ArrowLeft class="h-4 w-4" />
            Kembali
          </button>

          <button
            type="button"
            class="flex items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
            @click="saveDraft"
          >
            <Save class="h-4 w-4" />
            Simpan Draf
          </button>

          <button
            v-if="currentStep < PERMOHONAN_BARU_STEPS.length - 1"
            type="button"
            class="flex items-center gap-1.5 rounded-md bg-violet-600 px-4 py-1.5 text-sm font-medium text-white transition-colors hover:bg-violet-700"
            @click="next"
          >
            Seterusnya
            <ArrowRight class="h-4 w-4" />
          </button>
          <button
            v-else
            type="button"
            class="flex items-center gap-1.5 rounded-md bg-emerald-600 px-4 py-1.5 text-sm font-medium text-white transition-colors hover:bg-emerald-700"
            @click="submit"
          >
            Hantar Permohonan
            <Send class="h-4 w-4" />
          </button>
        </div>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
