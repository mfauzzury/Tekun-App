<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import {
  Eye,
  Download,
  History,
  Server,
  Database,
  AlertTriangle,
  BarChart3,
  Cpu,
  Shield,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import { exportToCSV, exportToExcel } from "@/composables/useExport";
import {
  AUDIT_TRAIL,
  LOG_SISTEM,
  LOG_PERUBAHAN_DATA,
  AMARAN_LIST,
  HEATMAP_AKTIVITI,
  PENGGUNA_BERISIKO,
  ANALITIK_AUDIT,
  AI_RISK_ITEMS,
  JENIS_TINDAKAN_LABELS,
  type AuditTrailItem,
  type AmaranItem,
} from "@/data/audit-dummy";

const { t, tp } = useI18n();

const router = useRouter();

// --- Tabs: 9.2 Audit Trail, 9.3 Log Sistem, 9.3 Perubahan Data, 9.5 Amaran, 9.2.6 Analytics, 9.6 AI Risk ---
type TabId = "audit" | "sistem" | "perubahan" | "amaran" | "analitik" | "ai";
const activeTab = ref<TabId>("audit");

const q = ref("");
const status = ref("");
const modul = ref("");
const dateFrom = ref("");
const dateTo = ref("");

// --- Summary (9.2, 9.3, 9.5) ---
const summary = computed(() => [
  { label: "Log Audit Bulan Ini", value: "1,245" },
  { label: "Tindakan Pengguna", value: 892 },
  { label: "Log Sistem", value: 353 },
  { label: "Amaran Aktif", value: AMARAN_LIST.filter((a) => !a.dibaca).length, alert: AMARAN_LIST.filter((a) => !a.dibaca).length > 0 ? "text-amber-600" : "" },
]);

// --- Filtered Audit Trail (9.2.3: carian, filter, penjanaan laporan) ---
const filteredAudit = computed(() => {
  let list = [...AUDIT_TRAIL];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter(
      (i) =>
        i.userId.toLowerCase().includes(lower) ||
        i.nama.toLowerCase().includes(lower) ||
        jenisTindakanLabel(i.jenisTindakan).toLowerCase().includes(lower) ||
        i.modul.toLowerCase().includes(lower) ||
        (i.rujukan && i.rujukan.toLowerCase().includes(lower))
    );
  }
  if (status.value) list = list.filter((i) => i.jenisTindakan === status.value);
  if (modul.value) list = list.filter((i) => i.modul.toLowerCase().includes(modul.value.toLowerCase()));
  return list;
});

// --- Filtered Log Sistem ---
const filteredLogSistem = computed(() => {
  let list = [...LOG_SISTEM];
  if (status.value) list = list.filter((i) => i.jenis === status.value);
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((i) => i.keterangan.toLowerCase().includes(lower) || (i.butiran && i.butiran.toLowerCase().includes(lower)));
  }
  return list;
});

// --- Filtered Perubahan Data ---
const filteredPerubahan = computed(() => {
  let list = [...LOG_PERUBAHAN_DATA];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter(
      (i) =>
        i.modul.toLowerCase().includes(lower) ||
        i.rujukan.toLowerCase().includes(lower) ||
        i.pengguna.toLowerCase().includes(lower)
    );
  }
  if (modul.value) list = list.filter((i) => i.modul.toLowerCase().includes(modul.value.toLowerCase()));
  return list;
});

// --- Filtered Amaran ---
const filteredAmaran = computed(() => {
  let list = [...AMARAN_LIST];
  if (status.value) list = list.filter((i) => i.tahap === status.value);
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((i) => i.keterangan.toLowerCase().includes(lower) || (i.pengguna && i.pengguna.toLowerCase().includes(lower)));
  }
  return list;
});

// --- Heatmap data for Analytics (9.5.5) ---
const heatmapByHour = computed(() => {
  const byHour: Record<number, number> = {};
  for (let h = 8; h <= 18; h++) byHour[h] = 0;
  HEATMAP_AKTIVITI.forEach((c) => {
    byHour[c.jam] = (byHour[c.jam] ?? 0) + c.nilai;
  });
  return Object.entries(byHour).map(([jam, nilai]) => ({ jam: Number(jam), nilai }));
});

// --- Jenis labels ---
function jenisTindakanLabel(j: AuditTrailItem["jenisTindakan"]) {
  return JENIS_TINDAKAN_LABELS[j] ?? j;
}

function statusLogClass(s: string) {
  const map: Record<string, string> = {
    ok: "bg-emerald-100 text-emerald-700",
    amaran: "bg-amber-100 text-amber-700",
    ralat: "bg-rose-100 text-rose-700",
  };
  return map[s] ?? "bg-slate-100 text-slate-700";
}

function tahapAmaranClass(t: AmaranItem["tahap"]) {
  const map: Record<string, string> = {
    info: "bg-blue-100 text-blue-700",
    amaran: "bg-amber-100 text-amber-700",
    kritikal: "bg-rose-100 text-rose-700",
  };
  return map[t] ?? "bg-slate-100 text-slate-700";
}

function jenisAmaranLabel(j: AmaranItem["jenis"]) {
  const map: Record<string, string> = {
    luar_waktu: "Luar Waktu Pejabat",
    ip_luar: "IP Luar Negara",
    modul_tidak_skop: "Akses Modul Tidak Skop",
    perubahan_kerap: "Perubahan Terlalu Kerap",
    transaksi_anomali: "Transaksi Anomali",
    kelulusan_luar_norma: "Kelulusan Luar Norma",
    sistem: "Sistem",
  };
  return map[j] ?? j;
}

function risikoClass(s: string) {
  const map: Record<string, string> = {
    rendah: "bg-emerald-100 text-emerald-700",
    sederhana: "bg-amber-100 text-amber-700",
    tinggi: "bg-rose-100 text-rose-700",
  };
  return map[s] ?? "bg-slate-100 text-slate-700";
}

// --- Export (9.2.3: penjanaan laporan) ---
function exportAuditTrail(format: "csv" | "excel") {
  const data = filteredAudit.value.map((i) => ({
    ID: i.id,
    Pengguna: i.userId,
    Nama: i.nama,
    Peranan: i.peranan,
    Tarikh: i.tarikh,
    Masa: i.masa,
    IP: i.ip,
    "Jenis Tindakan": jenisTindakanLabel(i.jenisTindakan),
    Modul: i.modul,
    Rujukan: i.rujukan ?? "",
    "Nilai Asal": i.nilaiAsal ?? "",
    "Nilai Baharu": i.nilaiBaharu ?? "",
  }));
  if (format === "csv") exportToCSV(data, "audit-trail");
  else exportToExcel(data, "audit-trail");
}

function exportLogSistem(format: "csv" | "excel") {
  const data = filteredLogSistem.value.map((i) => ({
    ID: i.id,
    Tarikh: i.tarikh,
    Masa: i.masa,
    Jenis: i.jenis,
    Keterangan: i.keterangan,
    Status: i.status,
    Butiran: i.butiran ?? "",
  }));
  if (format === "csv") exportToCSV(data, "log-sistem");
  else exportToExcel(data, "log-sistem");
}

function exportPerubahanData(format: "csv" | "excel") {
  const data = filteredPerubahan.value.map((i) => ({
    ID: i.id,
    Tarikh: i.tarikh,
    Masa: i.masa,
    Modul: i.modul,
    Medan: i.medan,
    Rujukan: i.rujukan,
    "Nilai Asal": i.nilaiAsal,
    "Nilai Baharu": i.nilaiBaharu,
    Pengguna: i.pengguna,
    IP: i.ip,
  }));
  if (format === "csv") exportToCSV(data, "log-perubahan-data");
  else exportToExcel(data, "log-perubahan-data");
}

// --- Tab config ---
const tabs: { id: TabId; label: string; icon: typeof History }[] = [
  { id: "audit", label: "Audit Trail (9.2)", icon: History },
  { id: "sistem", label: "Log Sistem (9.3)", icon: Server },
  { id: "perubahan", label: "Perubahan Data (9.3)", icon: Database },
  { id: "amaran", label: "Amaran & Anomali (9.5)", icon: AlertTriangle },
  { id: "analitik", label: "Laporan Analitik (9.2.6)", icon: BarChart3 },
  { id: "ai", label: "AI Risk (9.6)", icon: Cpu },
];

// --- Filter options per tab ---
const auditFilterOptions = [
  { value: "log_masuk", label: "Log Masuk" },
  { value: "log_keluar", label: "Log Keluar" },
  { value: "paparan", label: "Paparan" },
  { value: "kemaskini", label: "Kemaskini" },
  { value: "kelulusan", label: "Kelulusan" },
  { value: "penghapusan", label: "Penghapusan" },
  { value: "eksport", label: "Eksport" },
  { value: "lain", label: "Lain-lain" },
];

const sistemFilterOptions = [
  { value: "server", label: "Server" },
  { value: "login_berjaya", label: "Log Masuk Berjaya" },
  { value: "login_gagal", label: "Log Masuk Gagal" },
  { value: "sesi", label: "Sesi" },
  { value: "sumber", label: "Sumber" },
  { value: "api", label: "API" },
  { value: "backup", label: "Backup" },
];

const amaranFilterOptions = [
  { value: "info", label: "Info" },
  { value: "amaran", label: "Amaran" },
  { value: "kritikal", label: "Kritikal" },
];

function goToKawalanDalaman() {
  router.push("/admin/laporan/audit/kawalan");
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Audit & Kawalan"
        :breadcrumb="[
          { label: 'Laporan', to: '/admin/laporan' },
          { label: 'Audit & Kawalan' },
        ]"
      >
        <template #actions>
          <button
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
            @click="goToKawalanDalaman"
          >
            <Shield class="h-4 w-4" />
            Kawalan Dalaman
          </button>
        </template>
      </SpptPageHeader>

      <SpptSummaryCards :items="summary" />

      <!-- Tabs -->
      <div class="flex flex-wrap gap-1 border-b border-slate-200">
        <button
          v-for="t in tabs"
          :key="t.id"
          class="flex items-center gap-2 border-b-2 px-4 py-2 text-sm font-medium transition-colors"
          :class="activeTab === t.id ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700'"
          @click="activeTab = t.id"
        >
          <component :is="t.icon" class="h-4 w-4" />
          {{ t.label }}
        </button>
      </div>

      <!-- Tab: Audit Trail (9.2) -->
      <div v-show="activeTab === 'audit'" class="space-y-4">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Log Audit Aktiviti Pengguna (9.2.2)</h2>
            <div class="flex flex-wrap items-center gap-3">
              <SpptFilterBar
                v-model:q="q"
                v-model:status="status"
                search-placeholder="Cari pengguna, modul, rujukan..."
                :filter-options="auditFilterOptions"
                filter-label="Jenis Tindakan"
              />
              <button
                class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                @click="exportAuditTrail('csv')"
              >
                <Download class="h-4 w-4" />
                CSV
              </button>
              <button
                class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                @click="exportAuditTrail('excel')"
              >
                <Download class="h-4 w-4" />
                Excel
              </button>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Pengguna / Nama</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Peranan</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.dateTime") }}</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">IP</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Modul</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Nilai Asal → Baharu</th>
                  <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in filteredAudit" :key="item.id" class="transition-colors hover:bg-slate-50">
                  <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                  <td class="px-4 py-2">
                    <div class="font-medium text-slate-900">{{ item.userId }}</div>
                    <div class="text-xs text-slate-500">{{ item.nama }}</div>
                  </td>
                  <td class="px-4 py-2 text-slate-600">{{ item.peranan }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ item.tarikh }} {{ item.masa }}</td>
                  <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ item.ip }}</td>
                  <td class="px-4 py-2">
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ jenisTindakanLabel(item.jenisTindakan) }}</span>
                  </td>
                  <td class="px-4 py-2 text-slate-600">{{ item.modul }}</td>
                  <td class="px-4 py-2 text-xs text-slate-600">
                    <span v-if="item.nilaiAsal || item.nilaiBaharu">
                      {{ item.nilaiAsal ?? "-" }} → {{ item.nilaiBaharu ?? "-" }}
                    </span>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="px-4 py-2 text-right">
                    <button class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                      <Eye class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.view") }}</span>
                    </button>
                  </td>
                </tr>
                <tr v-if="filteredAudit.length === 0">
                  <td colspan="9" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="border-t border-slate-100 px-4 py-2 text-xs text-slate-500">9.2.5: Log disimpan sekurang-kurangnya 7 tahun, read-only, tamper-proof.</p>
        </article>
      </div>

      <!-- Tab: Log Sistem (9.3.1) -->
      <div v-show="activeTab === 'sistem'" class="space-y-4">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Log Sistem (9.3.1)</h2>
            <div class="flex flex-wrap items-center gap-3">
              <SpptFilterBar
                v-model:q="q"
                v-model:status="status"
                search-placeholder="Cari keterangan..."
                :filter-options="sistemFilterOptions"
                filter-label="Jenis"
              />
              <button class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" @click="exportLogSistem('csv')">
                <Download class="h-4 w-4" /> CSV
              </button>
              <button class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" @click="exportLogSistem('excel')">
                <Download class="h-4 w-4" /> Excel
              </button>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.dateTime") }}</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Keterangan</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Butiran</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in filteredLogSistem" :key="item.id" class="transition-colors hover:bg-slate-50">
                  <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ item.tarikh }} {{ item.masa }}</td>
                  <td class="px-4 py-2">
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ item.jenis }}</span>
                  </td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ item.keterangan }}</td>
                  <td class="px-4 py-2">
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusLogClass(item.status)">{{ item.status }}</span>
                  </td>
                  <td class="px-4 py-2 text-xs text-slate-600">{{ item.butiran ?? "-" }}</td>
                </tr>
                <tr v-if="filteredLogSistem.length === 0">
                  <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <!-- Tab: Perubahan Data (9.3.2) -->
      <div v-show="activeTab === 'perubahan'" class="space-y-4">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Log Perubahan Data Penting (9.3.2)</h2>
            <div class="flex flex-wrap items-center gap-3">
              <SpptFilterBar
                v-model:q="q"
                v-model:status="modul"
                search-placeholder="Cari modul, rujukan, pengguna..."
                :filter-options="[
                  { value: 'Permohonan', label: 'Permohonan' },
                  { value: 'Penilaian', label: 'Penilaian' },
                  { value: 'Akaun', label: 'Akaun' },
                  { value: 'Pengeluaran', label: 'Pengeluaran' },
                  { value: 'Litigasi', label: 'Litigasi' },
                ]"
                filter-label="Modul"
              />
              <button class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" @click="exportPerubahanData('csv')">
                <Download class="h-4 w-4" /> CSV
              </button>
              <button class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50" @click="exportPerubahanData('excel')">
                <Download class="h-4 w-4" /> Excel
              </button>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100 text-left">
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.dateTime") }}</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Modul</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Medan</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Rujukan</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Nilai Asal → Baharu</th>
                  <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Pengguna / IP</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in filteredPerubahan" :key="item.id" class="transition-colors hover:bg-slate-50">
                  <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ item.tarikh }} {{ item.masa }}</td>
                  <td class="px-4 py-2 font-medium text-slate-900">{{ item.modul }}</td>
                  <td class="px-4 py-2 text-slate-600">{{ item.medan }}</td>
                  <td class="px-4 py-2 font-mono text-slate-600">{{ item.rujukan }}</td>
                  <td class="px-4 py-2 text-slate-600">
                    {{ item.nilaiAsal }} → {{ item.nilaiBaharu }}
                  </td>
                  <td class="px-4 py-2">
                    <div class="text-slate-900">{{ item.pengguna }}</div>
                    <div class="text-xs text-slate-500">{{ item.ip }}</div>
                  </td>
                </tr>
                <tr v-if="filteredPerubahan.length === 0">
                  <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="border-t border-slate-100 px-4 py-2 text-xs text-slate-500">9.3.4: Log read-only, tidak boleh dihapus/ubah. Non-repudiation.</p>
        </article>
      </div>

      <!-- Tab: Amaran & Anomali (9.3.6, 9.5) -->
      <div v-show="activeTab === 'amaran'" class="space-y-4">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Amaran & Pengesanan Anomali (9.5)</h2>
            <SpptFilterBar
              v-model:q="q"
              v-model:status="status"
              search-placeholder="Cari amaran..."
              :filter-options="amaranFilterOptions"
              filter-label="Tahap"
            />
          </div>
          <div class="divide-y divide-slate-100">
            <div
              v-for="item in filteredAmaran"
              :key="item.id"
              class="flex flex-wrap items-start justify-between gap-3 px-4 py-3 transition-colors hover:bg-slate-50"
              :class="!item.dibaca ? 'bg-amber-50/50' : ''"
            >
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="tahapAmaranClass(item.tahap)">{{ item.tahap }}</span>
                  <span class="text-xs text-slate-500">{{ jenisAmaranLabel(item.jenis) }}</span>
                  <span v-if="!item.dibaca" class="rounded-full bg-amber-200 px-2 py-0.5 text-xs font-medium text-amber-800">Baru</span>
                </div>
                <p class="mt-1 text-sm font-medium text-slate-900">{{ item.keterangan }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ item.tarikh }} {{ item.masa }} · {{ item.pengguna || item.ip || "Sistem" }}</p>
              </div>
            </div>
            <div v-if="filteredAmaran.length === 0" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada amaran.") }}</div>
          </div>
          <p class="border-t border-slate-100 px-4 py-2 text-xs text-slate-500">9.3.6: Alert untuk perubahan luar kebiasaan, kemaskini tanpa justifikasi, luar waktu pejabat, lokasi luar biasa.</p>
        </article>
      </div>

      <!-- Tab: Laporan Analitik (9.2.6, 9.5.5) -->
      <div v-show="activeTab === 'analitik'" class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-2">
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Heatmap Masa Aktif (9.5.5)</h3>
            <p class="mb-3 text-xs text-slate-500">Aktiviti pengguna mengikut jam (Isnin–Jumaat).</p>
            <div class="space-y-2">
              <div v-for="row in heatmapByHour" :key="row.jam" class="flex items-center gap-2">
                <span class="w-12 shrink-0 text-xs text-slate-600">{{ row.jam }}:00</span>
                <div class="min-w-0 flex-1">
                  <div
                    class="h-5 rounded transition-all"
                    :class="row.nilai > 30 ? 'bg-rose-400' : row.nilai > 15 ? 'bg-amber-400' : 'bg-slate-300'"
                    :style="{ width: `${Math.min(100, (row.nilai / 50) * 100)}%` }"
                  />
                </div>
                <span class="w-12 shrink-0 text-right text-xs text-slate-600">{{ row.nilai }}</span>
              </div>
            </div>
          </article>
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Pengguna Berisiko Tinggi (9.5.5)</h3>
            <div class="space-y-2">
              <div
                v-for="u in PENGGUNA_BERISIKO"
                :key="u.id"
                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-100 p-3"
              >
                <div>
                  <div class="font-medium text-slate-900">{{ u.nama }}</div>
                  <div class="text-xs text-slate-500">{{ u.pengguna }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="risikoClass(u.status)">{{ u.status }}</span>
                  <span class="text-sm font-semibold text-slate-700">Skor: {{ u.skorRisiko }}</span>
                </div>
                <div class="w-full text-xs text-slate-500">
                  Amaran: {{ u.bilAmaran }} · Luar waktu: {{ u.bilAktivitiLuarWaktu }} · Perubahan: {{ u.bilPerubahanData }}
                </div>
              </div>
            </div>
          </article>
        </div>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-3 text-sm font-semibold text-slate-900">Analitik Audit Trail (9.2.6)</h3>
          <p class="mb-3 text-xs text-slate-500">Pengguna perubahan tidak konsisten, kelulusan luar norma, potensi penyalahgunaan.</p>
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div
              v-for="a in ANALITIK_AUDIT"
              :key="a.id"
              class="rounded-lg border border-slate-100 p-3"
            >
              <p class="text-xs font-medium text-slate-500">{{ a.metrik }}</p>
              <p class="mt-1 text-lg font-semibold text-slate-900">{{ a.nilai }}</p>
              <p class="mt-0.5 text-xs text-slate-500">{{ a.perbandingan }}</p>
            </div>
          </div>
        </article>
      </div>

      <!-- Tab: AI Risk (9.6) -->
      <div v-show="activeTab === 'ai'" class="space-y-4">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">AI Risk & Anomaly Detection (9.6)</h2>
            <p class="mt-1 text-xs text-slate-500">9.6.1: AI Risk Scoring & Early Warning. 9.6.2: AI Anomaly Detection untuk kelakuan pengguna dan transaksi.</p>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="item in AI_RISK_ITEMS" :key="item.id" class="flex flex-wrap items-start justify-between gap-3 px-4 py-3 transition-colors hover:bg-slate-50">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-slate-600">{{ item.rujukan }}</span>
                  <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="item.skorRisiko >= 70 ? 'bg-rose-100 text-rose-700' : item.skorRisiko >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'"
                  >
                    Skor: {{ item.skorRisiko }}
                  </span>
                </div>
                <p class="mt-1 text-sm text-slate-900">{{ item.keterangan }}</p>
                <p v-if="item.cadangan" class="mt-1 text-xs font-medium text-slate-600">{{ item.cadangan }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ item.tarikh }}</p>
              </div>
            </div>
          </div>
        </article>
      </div>
    </div>
  </AdminLayout>
</template>
