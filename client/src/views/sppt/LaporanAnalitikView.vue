<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import {
  Download,
  FileText,
  LayoutDashboard,
  FileBarChart,
  FileSpreadsheet,
  Archive,
  Settings,
  Bell,
  Star,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import LaporanBarChart from "@/components/sppt/laporan/LaporanBarChart.vue";
import LaporanLineChart from "@/components/sppt/laporan/LaporanLineChart.vue";
import LaporanPieChart from "@/components/sppt/laporan/LaporanPieChart.vue";
import LaporanMapMalaysia from "@/components/sppt/laporan/LaporanMapMalaysia.vue";
import { exportToCSV, exportToExcel } from "@/composables/useExport";
import {
  KPI_SUMMARY,
  LAPORAN_ITEMS,
  KUTIPAN_VS_SASARAN,
  TREND_NPF,
  AGIHAN_STATUS_AKAUN,
  PRESTASI_NEGERI,
  CROSS_ANALYSIS_PRODUK,
  LAPORAN_INDIVIDU,
  LAPORAN_RESIT,
  LAPORAN_PENYESUAIAN_BANK,
  LAPORAN_MONTH_END,
  LAPORAN_NPF,
  LAPORAN_PATUH_SYARIAH,
  LAPORAN_SNC,
  LAPORAN_NISBAH_SELIAAN,
  AUDIT_LAPORAN,
  ARKIB_LAPORAN,
  NOTIFIKASI_LAPORAN,
  MEDAN_TERSEDIA,
  type LaporanItem,
  type RoleLaporan,
} from "@/data/laporan-dummy";

// --- Fasa 1: Filter & state ---
const activeTab = ref<"dashboard" | "utama" | "khusus" | "arkib">("dashboard");
const q = ref("");
const status = ref("");
const jenis = ref("");
const tempoh = ref("");
const negeri = ref("");
const role = ref<RoleLaporan>("hq");
const showCustomizeModal = ref(false);
const favouriteFields = ref<string[]>(["Negeri", "Cawangan", "Nama Usahawan", "Amaun Pembiayaan"]);
const drillDown = ref<{ level: string; value: string } | null>(null);

// --- Fasa 1: Filtered items ---
const filteredItems = computed(() => {
  let list = [...LAPORAN_ITEMS];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((x) => x.tajuk.toLowerCase().includes(lower) || x.id.toLowerCase().includes(lower));
  }
  if (status.value) {
    list = list.filter((x) => x.jenis.toLowerCase() === status.value);
  }
  if (tempoh.value) {
    list = list.filter((x) => x.tempoh === tempoh.value);
  }
  return list;
});

// --- Fasa 1: Summary dinamik ---
const summaryCards = computed(() => {
  const k = KPI_SUMMARY;
  const kutipanPct = Math.round((k.kutipanBulanan / k.sasaranKutipan) * 100);
  const kutipanColor = kutipanPct >= 90 ? "text-emerald-600" : kutipanPct >= 70 ? "text-amber-600" : "text-rose-600";
  return [
    { label: "Portfolio Aktif", value: `RM ${(k.portfolioAktif / 1e6).toFixed(1)}M`, alert: "" },
    { label: "Pembiayaan Diluluskan", value: k.pembiayaanDiluluskan, alert: "" },
    { label: "Kadar Bayaran Balik", value: `${k.kadarBayaranBalik}%`, alert: "" },
    { label: "Pertumbuhan YoY", value: `+${k.pertumbuhanYoY}%`, alert: "" },
    { label: "Kutipan vs Sasaran", value: `${kutipanPct}%`, alert: kutipanColor },
    { label: "Kadar NPF", value: `${k.kadarNpf}%`, alert: k.kadarNpf > 6 ? "text-rose-600" : "" },
    { label: "Akaun Tunggakan", value: k.akaunTunggakan, alert: "" },
    { label: "Akaun Litigasi", value: k.akaunLitigasi, alert: "" },
  ];
});

// --- Fasa 1: Export ---
function exportTable(format: "csv" | "excel") {
  const data = filteredItems.value.map((x) => ({ id: x.id, tajuk: x.tajuk, tarikh: x.tarikh, jenis: x.jenis, tempoh: x.tempoh, format: x.format }));
  if (format === "csv") exportToCSV(data, "laporan-tersedia");
  else exportToExcel(data, "laporan-tersedia");
}

const { t, tp } = useI18n();

function downloadReport(item: LaporanItem) {
  const data = [{ id: item.id, tajuk: item.tajuk, tarikh: item.tarikh, jenis: item.jenis }];
  exportToExcel(data, `laporan-${item.id}`);
}

// --- Fasa 2: Chart data ---
const barChartData = computed(() =>
  KUTIPAN_VS_SASARAN.map((r) => ({ label: r.bulan, value: r.kutipan, value2: r.sasaran }))
);

// --- Fasa 3: Cross-analysis filtered ---
const crossAnalysisFiltered = computed(() => {
  let list = [...CROSS_ANALYSIS_PRODUK];
  if (negeri.value) list = list.filter((x) => x.negeri === negeri.value);
  if (drillDown.value?.level === "negeri") list = list.filter((x) => x.negeri === drillDown.value?.value);
  return list;
});

// --- Fasa 5: Audit filtered ---
const auditFiltered = computed(() => AUDIT_LAPORAN);

// --- Fasa 5: Toggle favourite ---
function toggleFavourite(field: string) {
  const i = favouriteFields.value.indexOf(field);
  if (i >= 0) favouriteFields.value.splice(i, 1);
  else favouriteFields.value.push(field);
}

// --- Filter options ---
const filterOptions = [
  { value: "kewangan", label: "Kewangan" },
  { value: "prestasi", label: "Prestasi" },
  { value: "tunggakan", label: "Tunggakan" },
  { value: "kutipan", label: "Kutipan" },
  { value: "npf", label: "NPF" },
  { value: "syariah", label: "Syariah" },
  { value: "pemantauan", label: "Pemantauan" },
];

const tempohOptions = [
  { value: "harian", label: "Harian" },
  { value: "mingguan", label: "Mingguan" },
  { value: "bulanan", label: "Bulanan" },
  { value: "suku-tahun", label: "Suku Tahun" },
  { value: "tahunan", label: "Tahunan" },
];

const negeriOptions = [
  { value: "Selangor", label: "Selangor" },
  { value: "Johor", label: "Johor" },
  { value: "Kedah", label: "Kedah" },
  { value: "Pulau Pinang", label: "Pulau Pinang" },
];

const roleOptions: { value: RoleLaporan; label: string }[] = [
  { value: "hq", label: "Ibu Pejabat (HQ)" },
  { value: "pengurus-negeri", label: "Pengurus Negeri" },
  { value: "pegawai-kutipan", label: "Pegawai Kutipan" },
];
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <!-- RBAC indicator -->
      <div v-if="role !== 'hq'" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
        <strong>RBAC:</strong> Paparan terhad mengikut peranan {{ role === "pengurus-negeri" ? "(cawangan negeri sahaja)" : "(akaun tanggungjawab sahaja)" }}.
      </div>

      <SpptPageHeader
        title="Laporan & Analitik"
        :breadcrumb="[{ label: 'Laporan', to: '/admin/laporan' }, { label: 'Laporan & Analitik' }]"
      >
        <template #actions>
          <div class="flex items-center gap-2">
            <select
              v-model="role"
              class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              title="RBAC: Tukar peranan"
            >
              <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <button
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
              @click="showCustomizeModal = true"
            >
              <Settings class="h-4 w-4" />
              Customize
            </button>
          </div>
        </template>
      </SpptPageHeader>

      <!-- Tabs -->
      <div class="flex flex-wrap gap-1 border-b border-slate-200">
        <button
          v-for="t in [
            { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
            { id: 'utama', label: 'Laporan Utama', icon: FileBarChart },
            { id: 'khusus', label: 'Laporan Khusus', icon: FileSpreadsheet },
            { id: 'arkib', label: 'Arkib & Audit', icon: Archive },
          ]"
          :key="t.id"
          class="flex items-center gap-2 border-b-2 px-4 py-2 text-sm font-medium transition-colors"
          :class="activeTab === t.id ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700'"
          @click="activeTab = t.id as 'dashboard' | 'utama' | 'khusus' | 'arkib'"
        >
          <component :is="t.icon" class="h-4 w-4" />
          {{ t.label }}
        </button>
      </div>

      <!-- Tab: Dashboard (Fasa 2) -->
      <div v-show="activeTab === 'dashboard'" class="space-y-4">
        <!-- KPI Cards with threshold -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="(item, i) in summaryCards"
            :key="i"
            class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
          >
            <p class="text-xs font-medium text-slate-500">{{ item.label }}</p>
            <p class="mt-1 text-xl font-semibold" :class="item.alert || 'text-slate-900'">{{ item.value }}</p>
          </article>
        </div>

        <!-- Charts -->
        <div class="grid gap-4 lg:grid-cols-2">
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Kutipan vs Sasaran (RM '000)</h3>
            <LaporanBarChart :data="barChartData" :max="350" />
          </article>
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Trend Kadar NPF (%)</h3>
            <LaporanLineChart :data="TREND_NPF.map((r) => ({ label: r.bulan, value: r.kadar }))" :max="8" />
          </article>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Agihan Status Akaun</h3>
            <LaporanPieChart :data="AGIHAN_STATUS_AKAUN" />
          </article>
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Prestasi Kutipan mengikut Negeri (%)</h3>
            <LaporanMapMalaysia :negeri="PRESTASI_NEGERI" />
          </article>
        </div>
      </div>

      <!-- Tab: Laporan Utama (Fasa 1, 3) -->
      <div v-show="activeTab === 'utama'" class="space-y-4">
        <!-- Filter bar -->
        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            search-placeholder="Cari laporan..."
            :filter-options="filterOptions"
            filter-label="Jenis Laporan"
          />
          <select
            v-model="tempoh"
            class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          >
            <option value="">Semua Tempoh</option>
            <option v-for="opt in tempohOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <select
            v-model="negeri"
            class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          >
            <option value="">Semua Negeri</option>
            <option v-for="opt in negeriOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <div class="flex gap-2">
            <button
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
              @click="exportTable('csv')"
            >
              <Download class="h-4 w-4" />
              CSV
            </button>
            <button
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
              @click="exportTable('excel')"
            >
              <Download class="h-4 w-4" />
              Excel
            </button>
          </div>
        </div>

        <!-- Laporan Tersedia -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Laporan Tersedia</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No.</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tajuk Laporan</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tempoh</th>
                  <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in filteredItems" :key="item.id" class="transition-colors hover:bg-slate-50">
                  <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ item.tajuk }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ item.tarikh }}</td>
                  <td class="px-4 py-2">
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ item.jenis }}</span>
                  </td>
                  <td class="px-4 py-2 text-slate-600">{{ item.tempoh }}</td>
                  <td class="px-4 py-2 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <button
                        class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                        @click="downloadReport(item)"
                      >
                        <FileText class="h-3.5 w-3.5" />
                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.view") }}</span>
                      </button>
                      <button
                        class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                        @click="downloadReport(item)"
                      >
                        <Download class="h-3.5 w-3.5" />
                        <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Muat Turun</span>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredItems.length === 0">
                  <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <!-- Cross-analysis (Fasa 3) -->
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Analisis Silang: Produk × Negeri</h3>
          <div v-if="drillDown" class="mb-2 flex items-center gap-2 text-sm">
            <button
              class="text-slate-500 hover:text-slate-700"
              @click="drillDown = null"
            >
              ← Kembali
            </button>
            <span class="text-slate-600">Drill-down: {{ drillDown.value }}</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Produk</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Negeri</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Diluluskan</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Dikeluarkan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="row in crossAnalysisFiltered"
                  :key="`${row.produk}-${row.negeri}`"
                  class="cursor-pointer hover:bg-slate-50"
                  @click="drillDown = { level: 'negeri', value: row.negeri }"
                >
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.produk }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.negeri }}</td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.diluluskan }}</td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.dikeluarkan }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <!-- Laporan Individu -->
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Laporan Individu (Filter: Negeri, Cawangan, Nama)</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">No</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Negeri</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Cawangan</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">{{ t("common.name") }}</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Kategori</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Amaun</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="row in LAPORAN_INDIVIDU" :key="row.no" class="hover:bg-slate-50">
                  <td class="px-4 py-2 font-mono text-slate-600">{{ row.no }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.negeri }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.cawangan }}</td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.nama }}</td>
                  <td class="px-4 py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ row.kategori }}</span></td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.amaun }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- Tab: Laporan Khusus (Fasa 4) -->
      <div v-show="activeTab === 'khusus'" class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-2">
          <!-- Laporan Resit -->
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Laporan Resit Kutipan</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-slate-100 text-left">
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">{{ t("common.date") }}</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Resit</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Tunai</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">EFT</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">{{ t("common.amount") }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="row in LAPORAN_RESIT" :key="row.tarikh" class="hover:bg-slate-50">
                    <td class="px-4 py-2 font-medium text-slate-900">{{ row.tarikh }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ row.resit }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ row.tunai }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ row.eft }}</td>
                    <td class="px-4 py-2 font-medium text-slate-900">{{ row.jumlah }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>

          <!-- Laporan Penyesuaian Bank -->
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Laporan Penyesuaian Bank</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-slate-100 text-left">
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Bank</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Baki Bawa</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Kutipan</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Baki Hadapan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="row in LAPORAN_PENYESUAIAN_BANK" :key="row.bank" class="hover:bg-slate-50">
                    <td class="px-4 py-2 font-medium text-slate-900">{{ row.bank }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ row.bakiBawa }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ row.kutipan }}</td>
                    <td class="px-4 py-2 font-medium text-slate-900">{{ row.bakiHadapan }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>
        </div>

        <!-- Laporan Month-End -->
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Laporan Month-End</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Item</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Nilai</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Catatan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="row in LAPORAN_MONTH_END" :key="row.item" class="hover:bg-slate-50">
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.item }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.nilai }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.catatan }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <!-- Laporan NPF -->
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Laporan NPF (Aging)</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Kategori</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Bil. Akaun</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Amaun</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">%</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="row in LAPORAN_NPF" :key="row.kategori" class="hover:bg-slate-50">
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.kategori }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.bil }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.amaun }}</td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.peratus }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <!-- Laporan Syariah -->
        <div class="grid gap-4 lg:grid-cols-2">
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Laporan Patuh Syariah</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-slate-100 text-left">
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Produk</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Akaun</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Disalurkan</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Baki</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="row in LAPORAN_PATUH_SYARIAH" :key="row.produk" class="hover:bg-slate-50">
                    <td class="px-4 py-2 font-medium text-slate-900">{{ row.produk }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ row.akaun }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ row.disalurkan }}</td>
                    <td class="px-4 py-2 font-medium text-slate-900">{{ row.baki }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>

          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Laporan SNC (Shariah Non Compliance)</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-slate-100 text-left">
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Produk</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Akaun</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Disalurkan</th>
                    <th class="px-4 py-2 text-xs font-semibold text-slate-500">Baki</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="row in LAPORAN_SNC" :key="row.produk" class="hover:bg-slate-50">
                    <td class="px-4 py-2 font-medium text-slate-900">{{ row.produk }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ row.akaun }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ row.disalurkan }}</td>
                    <td class="px-4 py-2 font-medium text-slate-900">{{ row.baki }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>
        </div>

        <!-- Laporan Pemantauan -->
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Laporan Nisbah Seliaan Pegawai Pembiayaan (PP)</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Pegawai</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Negeri</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Cawangan</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Penerima</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Prestasi</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">{{ t("common.status") }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="row in LAPORAN_NISBAH_SELIAAN" :key="row.pegawai" class="hover:bg-slate-50">
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.pegawai }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.negeri }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.cawangan }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.penerima }}</td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.prestasi }}</td>
                  <td class="px-4 py-2">
                    <span class="rounded-full px-2 py-0.5 text-xs" :class="row.status === 'baik' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">{{ row.status }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- Tab: Arkib & Audit (Fasa 5) -->
      <div v-show="activeTab === 'arkib'" class="space-y-4">
        <!-- Notifikasi -->
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
            <Bell class="h-4 w-4" />
            Notifikasi & Amaran
          </h3>
          <ul class="space-y-2">
            <li
              v-for="n in NOTIFIKASI_LAPORAN"
              :key="n.id"
              class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2"
              :class="{ 'bg-slate-50': !n.dibaca }"
            >
              <span class="text-sm text-slate-700">{{ n.mesej }}</span>
              <span
                class="rounded-full px-2 py-0.5 text-xs"
                :class="{
                  'bg-rose-100 text-rose-800': n.tahap === 'kritikal',
                  'bg-amber-100 text-amber-800': n.tahap === 'amaran',
                  'bg-slate-100 text-slate-600': n.tahap === 'info',
                }"
              >
                {{ n.tahap }}
              </span>
            </li>
          </ul>
        </article>

        <!-- Audit trail -->
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Jejak Audit (Penjanaan Laporan)</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Tajuk</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Pengguna</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">{{ t("common.date") }}</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Masa</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Format</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="row in auditFiltered" :key="row.id" class="hover:bg-slate-50">
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.tajuk }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.pengguna }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.tarikh }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.masa }}</td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.format }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>

        <!-- Arkib -->
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Arkib Laporan</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Tajuk</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">{{ t("common.date") }}</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Saiz</th>
                  <th class="px-4 py-2 text-xs font-semibold text-slate-500">Jenis</th>
                  <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">{{ t("common.actions") }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="row in ARKIB_LAPORAN" :key="row.id" class="hover:bg-slate-50">
                  <td class="px-4 py-2 font-medium text-slate-900">{{ row.tajuk }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.tarikh }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ row.saiz }}</td>
                  <td class="px-4 py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ row.jenis }}</span></td>
                  <td class="px-4 py-2 text-right">
                    <button class="text-slate-600 hover:text-slate-900">Muat Turun</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- Modal: Customize (Fasa 5) -->
      <div
        v-if="showCustomizeModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50"
        @click.self="showCustomizeModal = false"
      >
        <div class="max-h-[80vh] w-full max-w-md overflow-auto rounded-lg border border-slate-200 bg-white p-4 shadow-xl">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Customize Laporan</h3>
            <button class="text-slate-400 hover:text-slate-600" @click="showCustomizeModal = false">×</button>
          </div>
          <p class="mb-3 text-xs text-slate-500">Pilih medan untuk laporan. Simpan sebagai kegemaran.</p>
          <div class="space-y-2">
            <label
              v-for="field in MEDAN_TERSEDIA"
              :key="field"
              class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-100 px-3 py-2 hover:bg-slate-50"
            >
              <input
                type="checkbox"
                :checked="favouriteFields.includes(field)"
                @change="toggleFavourite(field)"
              />
              <span class="text-sm text-slate-700">{{ field }}</span>
              <Star v-if="favouriteFields.includes(field)" class="ml-auto h-4 w-4 text-amber-500" />
            </label>
          </div>
          <div class="mt-4 flex justify-end gap-2">
            <button
              class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
              @click="showCustomizeModal = false"
            >
              Batal
            </button>
            <button
              class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-800"
              @click="showCustomizeModal = false"
            >
              Simpan
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
