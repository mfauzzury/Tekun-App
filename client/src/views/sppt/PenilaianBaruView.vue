<script setup lang="ts">
import {
  applyCreditScoreToForm,
  applyMitigationToForm,
} from "@/composables/useAiCreditScoring";
import { mapPermohonanPenilaianRow } from "@/composables/usePermohonanPenilaian";
import { useI18n } from "@/composables/useI18n";
import { useToast } from "@/composables/useToast";
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { ArrowLeft, Save, Search, User, FileCheck, Handshake, Eye, Loader2, FileText } from "lucide-vue-next";

import { getPermohonan, isApprovedPermohonanStatus, listPermohonan, openPermohonanOfferLetter, savePermohonanPenilaian, scorePermohonanCredit } from "@/api/sppt";
import AdminLayout from "@/layouts/AdminLayout.vue";
import AiCreditScoringPanel from "@/components/sppt/AiCreditScoringPanel.vue";
import PembiayaanFormulaPanel from "@/components/sppt/PembiayaanFormulaPanel.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import type { AiCreditScoringResult, EdgeCaseMitigation } from "@/types";

const { t, tp } = useI18n();
const toast = useToast();

const router = useRouter();
const saving = ref(false);
const saved = ref(false);
const downloadingOfferLetter = ref(false);
const searchQuery = ref("");
const loadingList = ref(false);
const scoring = ref(false);
const aiCredit = ref<AiCreditScoringResult | null>(null);

interface PermohonanItem {
  id: number;
  noRujukan: string;
  nama: string;
  jumlah: string;
  status: string;
  jenisPermohonan: string;
}

const permohonanList = ref<PermohonanItem[]>([]);

/** Only applications ready for assessment appear in Penilaian Baru. */
const PENILAIAN_BARU_STATUSES = ["Dalam Proses", "Disemak"] as const;

const JENIS_PEMBIAYAAN_OPTIONS = [
  "Pembiayaan Pertama",
  "Pembiayaan Ulangan",
  "Tekun Niaga",
  "Teman Tekun",
  "Qard",
  "Lain-lain",
];

onMounted(async () => {
  loadingList.value = true;
  try {
    const res = await listPermohonan({
      limit: 100,
      penilaian: 1,
      statuses: PENILAIAN_BARU_STATUSES.join(","),
    });
    permohonanList.value = (res.data as Array<Record<string, unknown>>).map((row) =>
      mapPermohonanPenilaianRow(row),
    );
  } finally {
    loadingList.value = false;
  }
});

const filteredPermohonan = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return permohonanList.value;
  return permohonanList.value.filter(
    (p) =>
      p.nama.toLowerCase().includes(q) ||
      p.noRujukan.toLowerCase().includes(q),
  );
});

const selectedPermohonan = ref<PermohonanItem | null>(null);

function parseRmAmount(value: string | number | undefined): number {
  if (typeof value === "number" && Number.isFinite(value)) return value;
  if (!value) return 0;
  const cleaned = String(value).replace(/[^\d.]/g, "");
  const parsed = Number(cleaned);
  return Number.isFinite(parsed) ? parsed : 0;
}

const formulaJumlahPokok = computed(() => parseRmAmount(selectedPermohonan.value?.jumlah));

const form = ref({
  keputusan: "",
  skorKredit: "",
  cadanganPembiayaan: "",
  cadanganJenisPembiayaan: "",
  catatan: "",
  kadarKeuntungan: 4,
  tempohPembiayaan: 36,
  kaedahPengiraan: "reducing_balance" as "flat_rate" | "reducing_balance",
  bukaAkaun: true,
});

const openedAkaunNo = ref<string | null>(null);

function hydratePenilaianFromDetails(details: Record<string, unknown> | undefined) {
  const penilaian = details?.penilaian;
  if (!penilaian || typeof penilaian !== "object") return;
  const row = penilaian as Record<string, unknown>;
  form.value.keputusan = String(row.keputusan ?? "");
  form.value.skorKredit = String(row.skor_kredit ?? row.skorKredit ?? "");
  form.value.cadanganPembiayaan = String(row.cadangan_pembiayaan ?? row.cadanganPembiayaan ?? "");
  form.value.cadanganJenisPembiayaan = String(row.cadangan_jenis_pembiayaan ?? row.cadanganJenisPembiayaan ?? "");
  form.value.catatan = String(row.catatan ?? "");
  form.value.kadarKeuntungan = Number(row.kadar_keuntungan ?? row.kadarKeuntungan ?? 4);
  form.value.tempohPembiayaan = Number(row.tempoh_pembiayaan ?? row.tempohPembiayaan ?? 36);
  const kaedah = String(row.kaedah_pengiraan ?? row.kaedahPengiraan ?? "reducing_balance");
  form.value.kaedahPengiraan = kaedah === "flat_rate" ? "flat_rate" : "reducing_balance";
}

async function selectPermohonan(p: PermohonanItem) {
  selectedPermohonan.value = p;
  aiCredit.value = null;
  openedAkaunNo.value = null;
  form.value.cadanganJenisPembiayaan = "";
  form.value.keputusan = "";
  form.value.catatan = "";
  form.value.skorKredit = "";
  form.value.cadanganPembiayaan = "";
  form.value.kadarKeuntungan = 4;
  form.value.tempohPembiayaan = 36;
  form.value.kaedahPengiraan = "reducing_balance";
  form.value.bukaAkaun = true;
  saved.value = false;

  if (!p.id) {
    toast.error("ID permohonan tidak sah. Sila muat semula senarai.");
    return;
  }

  try {
    const detailRes = await getPermohonan(p.id);
    const details = detailRes.data.details as Record<string, unknown> | undefined;
    hydratePenilaianFromDetails(details);
  } catch {
    // Continue with defaults if detail fetch fails.
  }

  scoring.value = true;
  try {
    const res = await scorePermohonanCredit(p.id);
    aiCredit.value = res.data;
    const applied = applyCreditScoreToForm(res.data);
    form.value.skorKredit = applied.skorKredit;
    form.value.cadanganPembiayaan = applied.cadanganPembiayaan;
    if (applied.keputusan) form.value.keputusan = applied.keputusan;
  } catch (err) {
    aiCredit.value = null;
    const message = err instanceof Error ? err.message : "Gagal mengira skor kredit AI.";
    toast.error(message);
  } finally {
    scoring.value = false;
  }
}

function gunakanCadanganAI() {
  if (!aiCredit.value) return;
  const applied = applyCreditScoreToForm(aiCredit.value);
  form.value.cadanganPembiayaan = applied.cadanganPembiayaan;
  form.value.skorKredit = applied.skorKredit;
  if (applied.keputusan) form.value.keputusan = applied.keputusan;
}

function gunaMitigasi(mitigation: EdgeCaseMitigation) {
  applyMitigationToForm(form.value, mitigation);
}

function applyFormulaInstallment(amount: number, methodLabel: string) {
  const line = `Ansuran bulanan dicadangkan: RM ${Math.round(amount).toLocaleString("en-MY")} (${methodLabel})`;
  form.value.catatan = form.value.catatan ? `${form.value.catatan}\n${line}` : line;
}

async function simpan() {
  if (!selectedPermohonan.value?.id) return;
  if (!form.value.keputusan) {
    toast.error("Sila pilih keputusan penilaian.");
    return;
  }

  saving.value = true;
  saved.value = false;
  try {
    const res = await savePermohonanPenilaian(selectedPermohonan.value.id, {
      keputusan: form.value.keputusan as "Lulus" | "Tolak" | "Tangguh",
      skorKredit: form.value.skorKredit,
      cadanganPembiayaan: form.value.cadanganPembiayaan,
      cadanganJenisPembiayaan: form.value.cadanganJenisPembiayaan,
      catatan: form.value.catatan,
      kadarKeuntunganTahunan: form.value.kadarKeuntungan,
      tempohPembiayaan: form.value.tempohPembiayaan,
      kaedahPengiraan: form.value.kaedahPengiraan,
      bukaAkaun: form.value.bukaAkaun,
    });

    saved.value = true;
    selectedPermohonan.value.status = String(res.data.permohonan.status ?? selectedPermohonan.value.status);
    openedAkaunNo.value = res.data.akaun ? String(res.data.akaun.id ?? "") : null;

    if (res.data.akaun) {
      toast.success(`Penilaian disimpan. Akaun ${openedAkaunNo.value} dibuka dengan jadual ansuran formula.`);
    } else {
      toast.success("Penilaian berjaya disimpan.");
    }
  } catch (err) {
    toast.error(err instanceof Error ? err.message : "Gagal menyimpan penilaian.");
  } finally {
    saving.value = false;
  }
}

function batal() {
  router.push("/admin/pembiayaan/penilaian");
}

async function downloadOfferLetter() {
  if (!selectedPermohonan.value?.id) return;
  downloadingOfferLetter.value = true;
  try {
    await openPermohonanOfferLetter(selectedPermohonan.value.id);
  } catch (err) {
    toast.error(err instanceof Error ? err.message : "Gagal menjana surat tawaran.");
  } finally {
    downloadingOfferLetter.value = false;
  }
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
              <p class="mt-1 font-mono text-xs text-slate-500">{{ p.noRujukan }}</p>
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
            <p v-if="loadingList" class="py-8 text-center text-sm text-slate-500">Memuatkan senarai...</p>
            <p v-else-if="filteredPermohonan.length === 0" class="py-8 text-center text-sm text-slate-500">
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
                <p class="mt-0.5 font-mono text-sm text-slate-500">{{ selectedPermohonan.noRujukan }}</p>
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
                    <p class="mt-0.5 font-mono text-sm font-medium text-slate-900">{{ selectedPermohonan.noRujukan }}</p>
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

              <AiCreditScoringPanel
                :scoring="scoring"
                :ai-credit="aiCredit"
                show-apply-suggestion
                @apply-suggestion="gunakanCadanganAI"
                @apply-mitigation="gunaMitigasi"
              />

              <PembiayaanFormulaPanel
                v-if="selectedPermohonan"
                :jumlah-pokok="formulaJumlahPokok"
                v-model:kaedah-pengiraan="form.kaedahPengiraan"
                v-model:kadar-keuntungan="form.kadarKeuntungan"
                v-model:tempoh-bulan="form.tempohPembiayaan"
                @apply-monthly-installment="applyFormulaInstallment"
              />

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
                  <label v-if="form.keputusan === 'Lulus'" class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="form.bukaAkaun" type="checkbox" class="rounded border-slate-300" />
                    Buka akaun pembiayaan automatik (guna formula dipilih)
                  </label>
                </div>
              </section>

              <!-- Success message -->
              <div v-if="saved" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Penilaian berjaya disimpan ke permohonan.
                <span v-if="openedAkaunNo"> Akaun {{ openedAkaunNo }} dibuka dengan jadual ansuran formula.</span>
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
                  v-if="selectedPermohonan && isApprovedPermohonanStatus(selectedPermohonan.status)"
                  type="button"
                  class="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 shadow-sm transition-colors hover:bg-blue-100 disabled:opacity-60"
                  :disabled="downloadingOfferLetter"
                  @click="downloadOfferLetter"
                >
                  <FileText class="h-4 w-4" />
                  {{ downloadingOfferLetter ? "Menjana PDF..." : "Surat Tawaran PDF" }}
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
