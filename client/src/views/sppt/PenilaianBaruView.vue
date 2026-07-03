<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import { ArrowLeft, Save, Sparkles, Search, User, FileCheck, Handshake, Eye } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";

const { t, tp } = useI18n();

const router = useRouter();
const saving = ref(false);
const saved = ref(false);
const searchQuery = ref("");

interface Permohonan {
  id: string;
  nama: string;
  jumlah: string;
  status: string;
  jenisPermohonan: string;
}

const permohonanList: Permohonan[] = [
  { id: "P-2024-001", nama: "Ahmad bin Abdullah", jumlah: "RM 50,000", status: "Menunggu", jenisPermohonan: "Pembiayaan Pertama - Tekun Niaga" },
  { id: "P-2024-002", nama: "Siti Nurhaliza binti Omar", jumlah: "RM 30,000", status: "Dalam Penilaian", jenisPermohonan: "Pembiayaan Ulangan - Teman Tekun" },
  { id: "P-2024-003", nama: "Mohd Rizal bin Hassan", jumlah: "RM 75,000", status: "Menunggu", jenisPermohonan: "Pembiayaan Pertama - Tekun Niaga" },
  { id: "P-2024-004", nama: "Fatimah binti Kassim", jumlah: "RM 25,000", status: "Menunggu", jenisPermohonan: "Pembiayaan Pertama - Qard" },
  { id: "P-2024-005", nama: "Lee Wei Ming", jumlah: "RM 60,000", status: "Menunggu", jenisPermohonan: "Pembiayaan Ulangan - Tekun Niaga" },
];

const JENIS_PEMBIAYAAN_OPTIONS = [
  "Pembiayaan Pertama",
  "Pembiayaan Ulangan",
  "Tekun Niaga",
  "Teman Tekun",
  "Qard",
  "Lain-lain",
];

/** Dummy AI Loan Risk Scoring – tiada sambungan ke AI sebenar */
const aiRiskDummy: Record<
  string,
  { riskScore: number; riskCategory: string; riskCategoryClass: string; recommendedLimit: string }
> = {
  "P-2024-001": { riskScore: 72, riskCategory: "Risiko Sederhana", riskCategoryClass: "bg-amber-100 text-amber-700", recommendedLimit: "RM 40,000" },
  "P-2024-002": { riskScore: 85, riskCategory: "Risiko Rendah", riskCategoryClass: "bg-emerald-100 text-emerald-700", recommendedLimit: "RM 30,000" },
  "P-2024-003": { riskScore: 58, riskCategory: "Risiko Tinggi", riskCategoryClass: "bg-rose-100 text-rose-700", recommendedLimit: "RM 25,000" },
  "P-2024-004": { riskScore: 91, riskCategory: "Risiko Rendah", riskCategoryClass: "bg-emerald-100 text-emerald-700", recommendedLimit: "RM 25,000" },
  "P-2024-005": { riskScore: 65, riskCategory: "Risiko Sederhana", riskCategoryClass: "bg-amber-100 text-amber-700", recommendedLimit: "RM 45,000" },
};

const filteredPermohonan = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return permohonanList;
  return permohonanList.filter(
    (p) =>
      p.nama.toLowerCase().includes(q) ||
      p.id.toLowerCase().includes(q)
  );
});

const selectedPermohonan = ref<Permohonan | null>(null);

const form = ref({
  keputusan: "",
  skorKredit: "",
  cadanganPembiayaan: "",
  cadanganJenisPembiayaan: "",
  catatan: "",
});

function selectPermohonan(p: Permohonan) {
  selectedPermohonan.value = p;
  const ai = aiRiskDummy[p.id];
  if (ai) {
    form.value.skorKredit = String(ai.riskScore);
    form.value.cadanganPembiayaan = ai.recommendedLimit;
  } else {
    form.value.skorKredit = "";
    form.value.cadanganPembiayaan = "";
  }
  form.value.cadanganJenisPembiayaan = "";
  form.value.keputusan = "";
  form.value.catatan = "";
}

function gunakanCadanganAI() {
  if (!selectedPermohonan.value) return;
  const ai = aiRiskDummy[selectedPermohonan.value.id];
  if (ai) form.value.cadanganPembiayaan = ai.recommendedLimit;
}

function simpan() {
  if (!selectedPermohonan.value) return;
  saving.value = true;
  setTimeout(() => {
    saving.value = false;
    saved.value = true;
  }, 800);
}

function batal() {
  router.push("/admin/pembiayaan/penilaian");
}
</script>

<template>
  <AdminLayout>
    <div class="flex h-[calc(100vh-8rem)] flex-col">
      <SpptPageHeader
        title="Penilaian Baru"
        :breadcrumb="[
          { label: 'Pembiayaan', to: '/admin/pembiayaan/penilaian' },
          { label: 'Penilaian & Kelulusan', to: '/admin/pembiayaan/penilaian' },
          { label: 'Penilaian Baru' },
        ]"
      />

      <div class="mt-4 flex flex-1 min-h-0 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <!-- Left Pane: List of Permohonan -->
        <aside class="flex w-[360px] shrink-0 flex-col border-r border-slate-200 bg-slate-50/50">
          <div class="border-b border-slate-200 bg-white px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Permohonan Menunggu Penilaian</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ filteredPermohonan.length }} permohonan</p>
          </div>
          <div class="border-b border-slate-200 p-3">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari nama atau no. permohonan..."
                class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
          </div>
          <div class="flex-1 overflow-y-auto p-2">
            <button
              v-for="p in filteredPermohonan"
              :key="p.id"
              type="button"
              class="mb-2 w-full rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition-all hover:border-slate-300 hover:shadow"
              :class="[
                selectedPermohonan?.id === p.id
                  ? 'border-slate-400 bg-sky-50/80 ring-1 ring-sky-200'
                  : '',
              ]"
              @click="selectPermohonan(p)"
            >
              <p class="font-semibold uppercase tracking-wide text-slate-900">{{ p.nama }}</p>
              <p class="mt-1 font-mono text-xs text-slate-500">{{ p.id }}</p>
              <p class="mt-1 text-xs text-slate-600">{{ p.jenisPermohonan }}</p>
              <div class="mt-2 flex flex-wrap gap-1.5">
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                  :class="
                    p.status === 'Lulus'
                      ? 'bg-emerald-100 text-emerald-700'
                      : p.status === 'Dalam Penilaian'
                        ? 'bg-amber-100 text-amber-700'
                        : 'bg-slate-100 text-slate-600'
                  "
                >
                  {{ p.status }}
                </span>
              </div>
            </button>
            <p v-if="filteredPermohonan.length === 0" class="py-8 text-center text-sm text-slate-500">
              {{ tp("Tiada permohonan dijumpai.") }}
            </p>
          </div>
        </aside>

        <!-- Right Pane: Details -->
        <main class="flex flex-1 flex-col overflow-hidden bg-white">
          <div v-if="!selectedPermohonan" class="flex flex-1 items-center justify-center text-slate-400">
            <div class="text-center">
              <FileCheck class="mx-auto h-12 w-12 text-slate-300" />
              <p class="mt-2 text-sm font-medium text-slate-500">Pilih permohonan daripada senarai</p>
              <p class="text-xs text-slate-400">Klik pada permohonan di sebelah kiri untuk melihat butiran dan mengisi penilaian</p>
            </div>
          </div>

          <div v-else class="flex flex-1 flex-col overflow-y-auto">
            <!-- Header -->
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/50 px-6 py-4">
              <div>
                <h3 class="font-semibold uppercase tracking-wide text-slate-900">{{ selectedPermohonan.nama }}</h3>
                <p class="mt-0.5 font-mono text-sm text-slate-500">{{ selectedPermohonan.id }}</p>
                <p class="text-sm text-slate-600">{{ selectedPermohonan.jenisPermohonan }}</p>
              </div>
              <button type="button" class="shrink-0 rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-200/60 hover:text-slate-700" :title="t('common.view')">
                <Eye class="h-5 w-5" />
              </button>
            </div>

            <form class="flex-1 space-y-6 p-6" @submit.prevent="simpan">
              <!-- Maklumat Permohonan -->
              <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                  <User class="h-4 w-4 text-slate-500" />
                  <h4 class="text-sm font-semibold text-slate-900">Maklumat Permohonan</h4>
                </div>
                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2">
                  <div>
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">No. Permohonan</p>
                    <p class="mt-0.5 font-mono text-sm font-medium text-slate-900">{{ selectedPermohonan.id }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Nama Pemohon</p>
                    <p class="mt-0.5 text-sm font-medium text-slate-900">{{ selectedPermohonan.nama }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Jumlah Dipohon</p>
                    <p class="mt-0.5 text-sm font-medium text-slate-900">{{ selectedPermohonan.jumlah }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Jenis Permohonan</p>
                    <p class="mt-0.5 text-sm text-slate-700">{{ selectedPermohonan.jenisPermohonan }}</p>
                  </div>
                </div>
              </section>

              <!-- AI Loan Risk -->
              <section
                v-if="selectedPermohonan && aiRiskDummy[selectedPermohonan.id]"
                class="rounded-lg border border-violet-200 bg-violet-50/50 shadow-sm"
              >
                <div class="flex items-center gap-2 border-b border-violet-200/50 px-4 py-3">
                  <Sparkles class="h-4 w-4 text-violet-600" />
                  <h4 class="text-sm font-semibold text-violet-900">AI Loan Risk Scoring</h4>
                  <span class="rounded px-1.5 py-0.5 text-[10px] font-medium text-violet-600 bg-violet-100">Sokongan Keputusan</span>
                </div>
                <div class="p-4">
                  <p class="text-xs text-violet-600/90">Dummy data – tiada sambungan ke AI sebenar.</p>
                  <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-white/80 px-3 py-2 shadow-sm">
                      <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Skor Risiko</p>
                      <p class="mt-0.5 text-lg font-bold text-slate-900">{{ aiRiskDummy[selectedPermohonan.id].riskScore }}/100</p>
                    </div>
                    <div class="rounded-lg bg-white/80 px-3 py-2 shadow-sm">
                      <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Kategori Risiko</p>
                      <span
                        class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="aiRiskDummy[selectedPermohonan.id].riskCategoryClass"
                      >
                        {{ aiRiskDummy[selectedPermohonan.id].riskCategory }}
                      </span>
                    </div>
                    <div class="rounded-lg bg-white/80 px-3 py-2 shadow-sm">
                      <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Had Pembiayaan Dicadangkan</p>
                      <p class="mt-0.5 text-lg font-bold text-slate-900">{{ aiRiskDummy[selectedPermohonan.id].recommendedLimit }}</p>
                      <button
                        type="button"
                        class="mt-1.5 text-xs font-medium text-violet-600 hover:text-violet-700"
                        @click="gunakanCadanganAI"
                      >
                        Guna sebagai cadangan →
                      </button>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Skor Kredit, Kelulusan, Cadangan, Catatan -->
              <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                  <Handshake class="h-4 w-4 text-slate-500" />
                  <h4 class="text-sm font-semibold text-slate-900">Penilaian & Kelulusan</h4>
                </div>
                <div class="space-y-4 p-4">
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Skor Kredit</label>
                    <input
                      v-model="form.skorKredit"
                      type="text"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                      placeholder="Contoh: 75"
                    />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Kelulusan Penilaian</label>
                    <select
                      v-model="form.keputusan"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                      <option value="">-- Pilih Keputusan --</option>
                      <option value="Lulus">Lulus</option>
                      <option value="Tolak">Tolak</option>
                      <option value="Tangguh">Tangguh</option>
                    </select>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Cadangan Jumlah Pembiayaan</label>
                    <input
                      v-model="form.cadanganPembiayaan"
                      type="text"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                      placeholder="Contoh: RM 40,000"
                    />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Cadangan Jenis Pembiayaan</label>
                    <select
                      v-model="form.cadanganJenisPembiayaan"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                    >
                      <option value="">-- Pilih Jenis Pembiayaan --</option>
                      <option v-for="opt in JENIS_PEMBIAYAAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Catatan</label>
                    <textarea
                      v-model="form.catatan"
                      rows="4"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                      placeholder="Catatan dan ulasan penilaian..."
                    />
                  </div>
                </div>
              </section>

              <!-- Success message -->
              <div v-if="saved" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                Penilaian berjaya disimpan. (Dummy – tiada sambungan ke jadual.)
              </div>

              <!-- Actions -->
              <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4">
                <button
                  type="submit"
                  :disabled="saving"
                  class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-60"
                >
                  <Save class="h-4 w-4" />
                  {{ saving ? "Menyimpan..." : "Simpan Penilaian" }}
                </button>
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
        </main>
      </div>
    </div>
  </AdminLayout>
</template>
