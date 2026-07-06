<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { ArrowLeft, Save, Upload, FileText, Sparkles, CheckCircle } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import {
  JENIS_CAGARAN_OPTIONS,
  PINJAMAN_OPTIONS,
  type JaminanItem,
  type JenisCagaran,
  type RisikoCagaran,
} from "@/data/pengurusan-jaminan-dummy";
import { listJaminan } from "@/api/sppt";

const { t, tp } = useI18n();

const router = useRouter();
const route = useRoute();
const saving = ref(false);
const saved = ref(false);
const uploading = ref(false);
const uploadComplete = ref(false);

/** Fasa 5: Mock AI OCR result - simulate extraction from uploaded doc */
const ocrResult = ref<{
  jenisDikesan: string;
  nilaiDikesan: string;
  tarikhDikesan: string;
  namaPemohon: string;
  keyValuePairs: { key: string; value: string }[];
} | null>(null);

/** Fasa 5: Mock document classification result */
const docClassification = ref<{ category: string; confidence: number } | null>(null);

const uploadedFiles = ref<{ name: string; size: number; category?: string }[]>([]);

const form = ref({
  id: "",
  nama: "",
  jenis: "" as JenisCagaran | "",
  nilai: 0,
  risiko: "Rendah" as RisikoCagaran,
  noPinjaman: "",
  tarikhMula: "",
  tarikhTamat: "",
  deskripsi: "",
});

const jaminanItems = ref<JaminanItem[]>([]);

const isNew = computed(() => !route.query.id);

onMounted(async () => {
  const res = await listJaminan({ limit: 100 });
  jaminanItems.value = res.data as JaminanItem[];

  const id = route.query.id as string;
  if (id) {
    const item = jaminanItems.value.find((i) => i.id === id);
    if (item) {
      form.value = {
        id: item.id,
        nama: item.nama,
        jenis: item.jenis,
        nilai: item.nilai,
        risiko: item.risiko,
        noPinjaman: item.noPinjaman,
        tarikhMula: item.tarikhMula,
        tarikhTamat: item.tarikhTamat,
        deskripsi: item.deskripsi ?? "",
      };
      if (item.dokumen?.length) {
        uploadedFiles.value = item.dokumen.map((d) => ({ name: d.nama, size: 0, category: d.jenis }));
      }
    }
  } else {
    form.value.id = `JAM-${new Date().getFullYear()}-${String(jaminanItems.value.length + 1).padStart(3, "0")}`;
  }
});

/** Fasa 5: Simulate AI OCR - SPEC 11.2.1 */
function simulateOcr(file: File) {
  const ext = file.name.split(".").pop()?.toLowerCase();
  const isPdf = ext === "pdf";
  const isImage = ["jpg", "jpeg", "png"].includes(ext ?? "");

  if (!isPdf && !isImage) return;

  ocrResult.value = {
    jenisDikesan: "Inden",
    nilaiDikesan: "RM 50,000",
    tarikhDikesan: new Date().toISOString().slice(0, 10),
    namaPemohon: form.value.nama || "Nama dari dokumen",
    keyValuePairs: [
      { key: "No. Rujukan", value: "IND-2024-001" },
      { key: "Nilai Jaminan", value: "RM 50,000.00" },
      { key: "Tarikh Mula", value: "2024-01-15" },
      { key: "Tarikh Tamat", value: "2029-01-15" },
      { key: "Penerima", value: "TEKUN Nasional" },
    ],
  };
}

/** Fasa 5: Simulate AI Document Classification - SPEC 11.2.2 */
function simulateDocClassification(filename: string): { category: string; confidence: number } {
  const lower = filename.toLowerCase();
  if (lower.includes("geran") || lower.includes("tanah")) return { category: "Geran", confidence: 0.92 };
  if (lower.includes("resit") || lower.includes("cukai")) return { category: "Resit", confidence: 0.88 };
  if (lower.includes("inden")) return { category: "Inden", confidence: 0.95 };
  if (lower.includes("sst")) return { category: "SST", confidence: 0.91 };
  if (lower.includes("po") || lower.includes("purchase")) return { category: "PO", confidence: 0.87 };
  if (lower.includes("lo") || lower.includes("letter")) return { category: "LO", confidence: 0.89 };
  if (lower.includes("perjanjian") || lower.includes("surat")) return { category: "Surat Perjanjian", confidence: 0.93 };
  return { category: "Dokumen Umum", confidence: 0.65 };
}

function onFileSelect(e: Event) {
  const input = e.target as HTMLInputElement;
  const files = input.files;
  if (!files?.length) return;

  uploading.value = true;
  uploadComplete.value = false;
  ocrResult.value = null;
  docClassification.value = null;

  setTimeout(() => {
    for (const file of Array.from(files)) {
      const classification = simulateDocClassification(file.name);
      docClassification.value = classification;
      uploadedFiles.value.push({
        name: file.name,
        size: file.size,
        category: classification.category,
      });
      simulateOcr(file);
    }
    uploading.value = false;
    uploadComplete.value = true;
    input.value = "";
  }, 1200);
}

function applyOcrToForm() {
  if (!ocrResult.value) return;
  form.value.nilai = parseInt(ocrResult.value.nilaiDikesan.replace(/[^0-9]/g, ""), 10) || form.value.nilai;
  form.value.tarikhMula = ocrResult.value.tarikhDikesan || form.value.tarikhMula;
  form.value.nama = ocrResult.value.namaPemohon || form.value.nama;
  if (ocrResult.value.jenisDikesan) {
    const match = JENIS_CAGARAN_OPTIONS.find((o) => o.label.includes(ocrResult.value!.jenisDikesan));
    if (match) form.value.jenis = match.value;
  }
}

function removeUploadedFile(index: number) {
  uploadedFiles.value.splice(index, 1);
  if (uploadedFiles.value.length === 0) {
    ocrResult.value = null;
    docClassification.value = null;
    uploadComplete.value = false;
  }
}

function simpan() {
  saving.value = true;
  setTimeout(() => {
    saving.value = false;
    saved.value = true;
    setTimeout(() => router.push("/admin/jaminan"), 600);
  }, 800);
}

function batal() {
  router.push("/admin/jaminan");
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-3xl space-y-6">
      <SpptPageHeader
        :title="isNew ? 'Daftar Jaminan Baru' : 'Kemaskini Jaminan'"
        :breadcrumb="[
          { label: 'Jaminan', to: '/admin/jaminan' },
          { label: isNew ? 'Daftar Baru' : 'Kemaskini' },
        ]"
      />

      <form class="space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="simpan">
        <!-- Maklumat Asas -->
        <section>
          <h3 class="mb-4 text-sm font-semibold text-slate-900">Maklumat Jaminan</h3>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">No. Jaminan</label>
              <input
                v-model="form.id"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                readonly
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Nama / Penerima</label>
              <input
                v-model="form.nama"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                placeholder="Nama pemegang jaminan"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Jenis Cagaran</label>
              <select
                v-model="form.jenis"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              >
                <option value="">-- Pilih --</option>
                <option v-for="opt in JENIS_CAGARAN_OPTIONS" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Nilai (RM)</label>
              <input
                v-model.number="form.nilai"
                type="number"
                min="0"
                step="0.01"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                placeholder="0.00"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Kategori Risiko</label>
              <select
                v-model="form.risiko"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              >
                <option value="Rendah">Rendah</option>
                <option value="Tinggi">Tinggi</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Pautan Pinjaman</label>
              <select
                v-model="form.noPinjaman"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              >
                <option value="">-- Pilih No. Pinjaman --</option>
                <option v-for="opt in PINJAMAN_OPTIONS" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Tarikh Mula</label>
              <input
                v-model="form.tarikhMula"
                type="date"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Tarikh Tamat</label>
              <input
                v-model="form.tarikhTamat"
                type="date"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              />
            </div>
            <div class="sm:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600">Deskripsi (pilihan)</label>
              <textarea
                v-model="form.deskripsi"
                rows="2"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                placeholder="Catatan tambahan..."
              />
            </div>
          </div>
        </section>

        <!-- Fasa 2 & 5: Muat Naik Dokumen + AI OCR + Document Classification -->
        <section class="rounded-lg border border-slate-200 bg-slate-50/50 p-4">
          <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
            <Upload class="h-4 w-4" />
            Muat Naik Dokumen Sokongan
          </h3>
          <p class="mb-3 text-xs text-slate-600">
            Inden, SST, PO, LO, Surat Perjanjian dan dokumen berkaitan. AI OCR akan mengekstrak maklumat secara automatik.
          </p>

          <div class="mb-4">
            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 bg-white px-4 py-6 text-sm text-slate-600 transition-colors hover:border-slate-400 hover:bg-slate-50">
              <Upload class="h-5 w-5" />
              <span v-if="uploading">Memproses... (AI OCR & Klasifikasi)</span>
              <span v-else>Klik atau seret fail ke sini</span>
              <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png" multiple @change="onFileSelect" />
            </label>
          </div>

          <!-- Fasa 5: AI Document Classification result -->
          <div
            v-if="docClassification && uploadComplete"
            class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
          >
            <CheckCircle class="h-4 w-4 shrink-0" />
            <span>
              <strong>AI Document Classification:</strong> Dokumen diklasifikasikan sebagai
              <strong>{{ docClassification.category }}</strong>
              (keyakinan {{ Math.round(docClassification.confidence * 100) }}%)
            </span>
          </div>

          <!-- Fasa 5: AI OCR result -->
          <div
            v-if="ocrResult && uploadComplete"
            class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4"
          >
            <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-blue-900">
              <Sparkles class="h-4 w-4" />
              AI OCR – Maklumat Diekstrak
            </div>
            <div class="grid gap-2 text-sm text-blue-800 sm:grid-cols-2">
              <p><span class="text-blue-600">Jenis:</span> {{ ocrResult.jenisDikesan }}</p>
              <p><span class="text-blue-600">Nilai:</span> {{ ocrResult.nilaiDikesan }}</p>
              <p><span class="text-blue-600">Tarikh:</span> {{ ocrResult.tarikhDikesan }}</p>
              <p><span class="text-blue-600">Nama:</span> {{ ocrResult.namaPemohon }}</p>
            </div>
            <div v-if="ocrResult.keyValuePairs?.length" class="mt-2 text-xs">
              <p class="mb-1 font-medium text-blue-700">Key-value pairs:</p>
              <ul class="space-y-0.5">
                <li v-for="kv in ocrResult.keyValuePairs" :key="kv.key">
                  {{ kv.key }}: {{ kv.value }}
                </li>
              </ul>
            </div>
            <button
              type="button"
              class="mt-3 rounded border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100"
              @click="applyOcrToForm"
            >
              Guna ke Borang
            </button>
          </div>

          <!-- Uploaded files list -->
          <ul v-if="uploadedFiles.length" class="space-y-2">
            <li
              v-for="(f, idx) in uploadedFiles"
              :key="idx"
              class="flex items-center justify-between rounded border border-slate-200 bg-white px-3 py-2 text-sm"
            >
              <div class="flex items-center gap-2">
                <FileText class="h-4 w-4 text-slate-400" />
                <span>{{ f.name }}</span>
                <span v-if="f.category" class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">
                  {{ f.category }}
                </span>
              </div>
              <button
                type="button"
                class="text-slate-400 hover:text-red-600"
                @click="removeUploadedFile(idx)"
              >
                ×
              </button>
            </li>
          </ul>
        </section>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="batal"
          >
            <ArrowLeft class="h-4 w-4" />
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
          >
            <Save class="h-4 w-4" />
            {{ saving ? "Menyimpan..." : "Simpan" }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
