<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import { Calculator, Eye, FileText, Play, RefreshCw } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import { useToast } from "@/composables/useToast";
import {
  fetchKoleksiConfig,
  fetchKlasifikasiSummary,
  fetchTawidhConfig,
  listKlasifikasiAkaun,
  listNotisKutipan,
  notisKutipanPdfUrl,
  processDunning,
  recalculateKlasifikasiNpl,
  recalculateTawidhAll,
} from "@/api/sppt";
import type { AkaunPembiayaan, KoleksiConfig, NotisKutipan, NplAgingBucket, TawidhConfig } from "@/types";

const { t, tp } = useI18n();
const toast = useToast();

const activeTab = ref<"klasifikasi" | "notis">("klasifikasi");
const q = ref("");
const status = ref("");
const notisStatus = ref("");
const items = ref<AkaunPembiayaan[]>([]);
const notisItems = ref<NotisKutipan[]>([]);
const agingBuckets = ref<NplAgingBucket[]>([]);
const koleksiConfig = ref<KoleksiConfig | null>(null);
const tawidhConfig = ref<TawidhConfig | null>(null);
const loading = ref(true);
const notisLoading = ref(false);
const recalculating = ref(false);
const processingDunning = ref(false);

function fmtRm(n: number) {
  return `RM ${n.toLocaleString("ms-MY", { minimumFractionDigits: 2 })}`;
}

function klasifikasiClass(k: string) {
  if (k === "lancar") return "bg-emerald-100 text-emerald-700";
  if (k === "perhatian_khusus") return "bg-amber-100 text-amber-700";
  if (k === "tidak_lancar") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-600";
}

function notisStatusClass(s: string) {
  if (s === "dihantar") return "bg-emerald-100 text-emerald-700";
  if (s === "dijadual") return "bg-blue-100 text-blue-700";
  if (s === "gagal") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-600";
}

const filteredItems = computed(() => {
  const query = q.value.trim().toLowerCase();
  let list = [...items.value];
  if (query) {
    list = list.filter(
      (i) =>
        String(i.id).toLowerCase().includes(query) ||
        i.nama.toLowerCase().includes(query),
    );
  }
  if (status.value === "lancar") list = list.filter((i) => i.klasifikasiNpl === "lancar");
  if (status.value === "perhatian_khusus") list = list.filter((i) => i.klasifikasiNpl === "perhatian_khusus");
  if (status.value === "tidak_lancar") list = list.filter((i) => i.klasifikasiNpl === "tidak_lancar");
  return list;
});

const summary = computed(() => {
  const lancar = agingBuckets.value.find((b) => b.kategori.startsWith("Lancar"));
  const pk = agingBuckets.value.find((b) => b.kategori.startsWith("Perhatian"));
  const npl = agingBuckets.value.find((b) => b.kategori.includes("NPL"));
  const totalTunggakan = items.value.reduce((s, i) => s + (i.tunggakan ?? 0), 0);
  return [
    { label: "Lancar", value: lancar?.bil ?? 0 },
    { label: "Perhatian Khusus", value: pk?.bil ?? 0 },
    { label: "Tidak Lancar (NPL)", value: npl?.bil ?? 0, alert: (npl?.bil ?? 0) > 0 ? "text-rose-600" : "" },
    { label: "Jumlah Tunggakan", value: fmtRm(totalTunggakan) },
  ];
});

async function loadKlasifikasi() {
  loading.value = true;
  try {
    const [akaunRes, configRes, koleksiRes, summaryRes] = await Promise.all([
      listKlasifikasiAkaun({ limit: 200 }),
      fetchTawidhConfig(),
      fetchKoleksiConfig(),
      fetchKlasifikasiSummary(),
    ]);
    items.value = akaunRes.data;
    tawidhConfig.value = configRes.data;
    koleksiConfig.value = koleksiRes.data;
    agingBuckets.value = summaryRes.data;
  } finally {
    loading.value = false;
  }
}

async function loadNotis() {
  notisLoading.value = true;
  try {
    const res = await listNotisKutipan({ limit: 100, status: notisStatus.value || undefined });
    notisItems.value = res.data;
  } finally {
    notisLoading.value = false;
  }
}

onMounted(async () => {
  await loadKlasifikasi();
  await loadNotis();
});

async function handleRecalculate() {
  recalculating.value = true;
  try {
    await recalculateTawidhAll();
    const res = await recalculateKlasifikasiNpl();
    toast.success("Klasifikasi Dikemaskini", `${res.data.akaunDikemaskini} akaun dikemaskini.`);
    await loadKlasifikasi();
  } catch (e) {
    toast.error("Gagal", e instanceof Error ? e.message : undefined);
  } finally {
    recalculating.value = false;
  }
}

async function handleProcessDunning() {
  processingDunning.value = true;
  try {
    const res = await processDunning();
    toast.success(
      "Dunning Diproses",
      `Dijana: ${res.data.notisDijana}, Dihantar: ${res.data.notisDihantar}, Gagal: ${res.data.gagal}`,
    );
    await loadNotis();
  } catch (e) {
    toast.error("Gagal", e instanceof Error ? e.message : undefined);
  } finally {
    processingDunning.value = false;
  }
}

function openNotisPdf(item: NotisKutipan) {
  if (!item._dbId) return;
  window.open(notisKutipanPdfUrl(item._dbId), "_blank");
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pemulihan & Kutipan Hutang"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Pemantauan Tunggakan' }]"
      />

      <div v-if="koleksiConfig" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <strong>Klasifikasi NPL (Aging Analysis):</strong>
        Lancar (0 hari) · Perhatian Khusus ({{ koleksiConfig.npl.perhatianKhususMinHari }}–{{ koleksiConfig.npl.tidakLancarMinHari - 1 }} hari) · Tidak Lancar / NPL (≥{{ koleksiConfig.npl.tidakLancarMinHari }} hari)
      </div>

      <div v-if="tawidhConfig" class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
        <strong>Parameter Ta'widh (LPS TEKUN):</strong>
        Tangguh {{ tawidhConfig.hariTangguh }} hari · Kadar harian {{ tawidhConfig.kadarHarianPct }}% · Maksimum {{ tawidhConfig.maxPctTahunan }}% setahun
        <button
          type="button"
          class="ml-3 inline-flex items-center gap-1 rounded-lg bg-indigo-700 px-2 py-1 text-xs font-medium text-white hover:bg-indigo-800 disabled:opacity-50"
          :disabled="recalculating"
          @click="handleRecalculate"
        >
          <Calculator class="h-3.5 w-3.5" /> Kira Semula
        </button>
      </div>

      <SpptSummaryCards :items="summary" />

      <div class="flex gap-2 border-b border-slate-200">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium transition-colors"
          :class="activeTab === 'klasifikasi' ? 'border-b-2 border-emerald-600 text-emerald-700' : 'text-slate-500 hover:text-slate-700'"
          @click="activeTab = 'klasifikasi'"
        >
          Klasifikasi Akaun (NPL)
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium transition-colors"
          :class="activeTab === 'notis' ? 'border-b-2 border-emerald-600 text-emerald-700' : 'text-slate-500 hover:text-slate-700'"
          @click="activeTab = 'notis'"
        >
          Tindakan Automasi Kutipan
        </button>
      </div>

      <!-- Tab: Klasifikasi NPL -->
      <article v-show="activeTab === 'klasifikasi'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Akaun — Klasifikasi Dinamik</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            search-placeholder="Cari nama atau no. akaun..."
            :filter-options="[
              { value: 'lancar', label: 'Lancar' },
              { value: 'perhatian_khusus', label: 'Perhatian Khusus' },
              { value: 'tidak_lancar', label: 'Tidak Lancar (NPL)' },
            ]"
          />
        </div>
        <div v-if="loading" class="p-6 text-center text-sm text-slate-400">Memuatkan...</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tunggakan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Ta'widh</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Hari Lewat</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Klasifikasi NPL</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in filteredItems" :key="String(item.id)" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-amber-600 font-medium">{{ fmtRm(item.tunggakan) }}</td>
                <td class="px-4 py-2 text-rose-600">{{ fmtRm(item.tawidh ?? item.penalti ?? 0) }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.hariLewat ?? 0 }} hari</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="klasifikasiClass(item.klasifikasiNpl ?? 'lancar')">
                    {{ item.klasifikasiNplLabel ?? item.klasifikasiNpl ?? "Lancar" }}
                  </span>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.status }}</td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <!-- Tab: Automated Dunning -->
      <article v-show="activeTab === 'notis'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <div>
            <h2 class="text-sm font-semibold text-slate-900">Notis Kutipan Automatik</h2>
            <p class="mt-0.5 text-xs text-slate-500">SMS amaran &amp; Notis Peringatan 1/2/3 dijana mengikut garis masa tunggakan</p>
          </div>
          <div class="flex items-center gap-2">
            <select
              v-model="notisStatus"
              class="rounded-lg border border-slate-200 px-2 py-1.5 text-xs"
              @change="loadNotis"
            >
              <option value="">Semua status</option>
              <option value="dijadual">Dijadual</option>
              <option value="dihantar">Dihantar</option>
              <option value="gagal">Gagal</option>
            </select>
            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
              @click="loadNotis"
            >
              <RefreshCw class="h-3.5 w-3.5" />
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-1 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-800 disabled:opacity-50"
              :disabled="processingDunning"
              @click="handleProcessDunning"
            >
              <Play class="h-3.5 w-3.5" /> Proses Dunning
            </button>
          </div>
        </div>

        <div v-if="koleksiConfig" class="border-b border-slate-100 bg-slate-50 px-4 py-2 text-xs text-slate-600">
          <span class="font-medium">Garis masa:</span>
          <span v-for="(rule, idx) in koleksiConfig.dunning" :key="rule.tahap">
            {{ rule.label }} ({{ rule.minHari }}{{ rule.maxHari ? `–${rule.maxHari}` : "+" }} hari){{ idx < koleksiConfig.dunning.length - 1 ? " · " : "" }}
          </span>
        </div>

        <div v-if="notisLoading" class="p-6 text-center text-sm text-slate-400">Memuatkan...</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Rujukan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Akaun / Nama</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tahap</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Hari Lewat</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500" />
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in notisItems" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ item.rujukan }}</td>
                <td class="px-4 py-2">
                  <div class="font-mono text-xs text-slate-500">{{ item.noAkaun }}</div>
                  <div class="font-medium text-slate-900">{{ item.nama }}</div>
                </td>
                <td class="px-4 py-2 text-slate-700">{{ item.tahapLabel }}</td>
                <td class="px-4 py-2 uppercase text-xs text-slate-500">{{ item.jenis }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.hariLewat }} hari</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="notisStatusClass(item.status)">
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-4 py-2 text-xs text-slate-500">
                  {{ item.tarikhDihantar ? new Date(item.tarikhDihantar).toLocaleString("ms-MY") : new Date(item.tarikhDijadual).toLocaleString("ms-MY") }}
                </td>
                <td class="px-4 py-2">
                  <button
                    v-if="item.jenis === 'surat' && item._dbId"
                    type="button"
                    class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800"
                    @click="openNotisPdf(item)"
                  >
                    <FileText class="h-3.5 w-3.5" /> Surat
                  </button>
                </td>
              </tr>
              <tr v-if="notisItems.length === 0">
                <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-400">
                  Tiada notis. Klik "Proses Dunning" untuk menjana notis mengikut garis masa tunggakan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
