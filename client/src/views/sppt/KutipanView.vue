<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import {
  Calendar,
  Eye,
  Download,
  FileSpreadsheet,
  FileText,
  Phone,
  ClipboardList,
  BarChart3,
  History,
  List,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import { exportToExcel, exportToPDF } from "@/composables/useExport";
import {
  KUTIPAN_ITEMS,
  SKH_ITEMS,
  CALL_CENTER_ITEMS,
  PSAT_ITEMS,
  SKM_MINGGUAN,
  AUDIT_LOG_ITEMS,
  KPI_PEGAWAI,
  CAWANGAN,
  ZON,
  PEGAWAI,
  STATUS_KUTIPAN,
  MAKLUM_BALAS_PANGGILAN,
} from "@/data/kutipan-dummy";

const { t, tp } = useI18n();

const router = useRouter();

const activeTab = ref<"senarai" | "skh" | "call-center" | "psat" | "kpi" | "audit">("senarai");
const q = ref("");
const status = ref("");
const cawangan = ref("");
const zon = ref("");
const pegawai = ref("");
const showRekodPanggilan = ref(false);
const rekodPanggilan = ref({ akaun: "", maklumBalas: "", catatan: "" });

// Summary (KPI dashboard metrics)
const totalKutipan = 85000;
const jumlahLawatan = 45;
const berjayaDikutip = 38;
const perluSusulan = 7;
const peratusKejayaan = Math.round((berjayaDikutip / jumlahLawatan) * 100);

const summary = computed(() => [
  { label: "Kutipan Bulan Ini", value: `RM ${(totalKutipan / 1000).toFixed(0)}K` },
  { label: "Jumlah Lawatan", value: jumlahLawatan },
  { label: "Berjaya Dikutip", value: berjayaDikutip },
  { label: "Peratus Kejayaan", value: `${peratusKejayaan}%` },
  { label: "Perlu Susulan", value: perluSusulan },
]);

// Filtered Senarai Kutipan
const filteredItems = computed(() => {
  let list = [...KUTIPAN_ITEMS];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter(
      (i) =>
        i.nama.toLowerCase().includes(lower) ||
        i.id.toLowerCase().includes(lower) ||
        i.noPembiayaan.toLowerCase().includes(lower)
    );
  }
  if (status.value) {
    const statusMap: Record<string, string[]> = {
      berjaya: ["Berjaya"],
      gagal: ["Gagal"],
      "janji-bayar": ["Janji Bayar"],
      "dalam-tindakan": ["Dalam Tindakan Kutipan"],
      "sedia-litigasi": ["Sedia untuk Tindakan Undang-Undang"],
      "berjaya-dihubungi": ["Berjaya Dihubungi"],
      "gagal-hubungi": ["Gagal Hubungi"],
      "rujuk-litigasi": ["Perlu Rujuk Litigasi"],
    };
    const match = statusMap[status.value] ?? [status.value];
    list = list.filter((i) => match.some((m) => i.status.toLowerCase().includes(m.toLowerCase())));
  }
  if (cawangan.value) list = list.filter((i) => i.cawangan === cawangan.value);
  if (zon.value) list = list.filter((i) => i.zon === zon.value);
  if (pegawai.value) list = list.filter((i) => i.pegawaiSeliaan === pegawai.value);
  return list;
});

// Filtered SKH
const filteredSkh = computed(() => {
  let list = [...SKH_ITEMS];
  if (zon.value) list = list.filter((i) => i.zon === zon.value);
  if (pegawai.value) list = list.filter((i) => i.pegawaiSeliaan === pegawai.value);
  return list;
});

function statusClass(s: string) {
  const map: Record<string, string> = {
    Berjaya: "bg-emerald-100 text-emerald-700",
    Gagal: "bg-amber-100 text-amber-700",
    "Janji Bayar": "bg-blue-100 text-blue-700",
    "Dalam Tindakan Kutipan": "bg-slate-100 text-slate-700",
    "Sedia untuk Tindakan Undang-Undang": "bg-rose-100 text-rose-700",
  };
  return map[s] ?? "bg-slate-100 text-slate-700";
}

function exportSenaraiExcel() {
  const data = filteredItems.value.map((i) => ({
    "No. Kutipan": i.id,
    "No. Pembiayaan": i.noPembiayaan,
    Nama: i.nama,
    "Jumlah Tunggakan": i.jumlahTunggakan,
    "Jumlah Dikutip": i.jumlahDikutip,
    "Tarikh Akhir Bayaran": i.tarikhAkhirBayaran,
    "Tarikh Lawatan": i.tarikhLawatan,
    Cawangan: i.cawangan,
    "Pegawai Seliaan": i.pegawaiSeliaan,
    Zon: i.zon,
    Status: i.status,
  }));
  exportToExcel(data, "Senarai-Kutipan");
}

function exportSenaraiPdf() {
  exportToPDF("table-senarai-kutipan", "Senarai-Kutipan");
}

function exportSkhExcel() {
  const data = filteredSkh.value.map((i) => ({
    ID: i.id,
    "No. Pembiayaan": i.noPembiayaan,
    Nama: i.nama,
    Zon: i.zon,
    "Pegawai Seliaan": i.pegawaiSeliaan,
    "Hari Kutipan": i.hariKutipan,
    Kawasan: i.kawasan,
    Status: i.status,
  }));
  exportToExcel(data, "Senarai-Kutipan-Harian-SKH");
}

function goToRekod(id?: string) {
  router.push({ path: "/admin/pemantauan/kutipan/rekod", query: id ? { akaun: id } : {} });
}

function goToDetail(id: string) {
  router.push({ path: "/admin/pemantauan/kutipan", query: { lihat: id } });
}

function submitRekodPanggilan() {
  if (!rekodPanggilan.value.akaun || !rekodPanggilan.value.maklumBalas) {
    alert("Sila pilih akaun dan maklum balas panggilan.");
    return;
  }
  alert(`Rekod panggilan disimpan (dummy).\nAkaun: ${rekodPanggilan.value.akaun}\nMaklum balas: ${rekodPanggilan.value.maklumBalas}`);
  rekodPanggilan.value = { akaun: "", maklumBalas: "", catatan: "" };
  showRekodPanggilan.value = false;
}

const tabs = [
  { id: "senarai" as const, label: "Senarai Kutipan", icon: List },
  { id: "skh" as const, label: "SKH", icon: ClipboardList },
  { id: "call-center" as const, label: "Call Center", icon: Phone },
  { id: "psat" as const, label: "PSAT", icon: Calendar },
  { id: "kpi" as const, label: "KPI Pegawai", icon: BarChart3 },
  { id: "audit" as const, label: "Audit Log", icon: History },
];
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Kutipan"
        :breadcrumb="[{ label: 'Pemantauan', to: '/admin/pemantauan/usahawan' }, { label: 'Kutipan' }]"
        action-label="Rekod Kutipan"
        action-to="/admin/pemantauan/kutipan/rekod"
      />

      <SpptSummaryCards :items="summary" />

      <!-- Tabs -->
      <div class="flex flex-wrap gap-1 border-b border-slate-200">
        <button
          v-for="t in tabs"
          :key="t.id"
          type="button"
          class="flex items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium transition-colors"
          :class="activeTab === t.id ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
          @click="activeTab = t.id"
        >
          <component :is="t.icon" class="h-4 w-4" />
          {{ t.label }}
        </button>
      </div>

      <!-- Tab: Senarai Kutipan -->
      <article
        v-show="activeTab === 'senarai'"
        class="rounded-lg border border-slate-200 bg-white shadow-sm"
      >
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Kutipan Lapangan</h2>
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex flex-wrap items-center gap-2">
              <select
                v-model="cawangan"
                class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              >
                <option value="">Semua Cawangan</option>
                <option v-for="c in CAWANGAN" :key="c" :value="c">{{ c }}</option>
              </select>
              <select
                v-model="zon"
                class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              >
                <option value="">Semua Zon</option>
                <option v-for="z in ZON" :key="z" :value="z">{{ z }}</option>
              </select>
              <select
                v-model="pegawai"
                class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              >
                <option value="">Semua Pegawai</option>
                <option v-for="p in PEGAWAI" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>
            <SpptFilterBar
              v-model:q="q"
              v-model:status="status"
              search-placeholder="Cari nama atau no. kutipan..."
              :filter-options="STATUS_KUTIPAN.map((s) => ({ value: s.value, label: s.label }))"
              filter-label="Semua Status"
            />
            <div class="flex gap-1">
              <button
                type="button"
                class="flex items-center gap-1.5 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
                @click="exportSenaraiExcel"
              >
                <FileSpreadsheet class="h-4 w-4" />
                Excel
              </button>
              <button
                type="button"
                class="flex items-center gap-1.5 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
                @click="exportSenaraiPdf"
              >
                <FileText class="h-4 w-4" />
                PDF
              </button>
            </div>
          </div>
        </div>
        <div id="table-senarai-kutipan" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kutipan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Pembiayaan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tunggakan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Dikutip</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Akhir Bayaran</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Cawangan / Pegawai</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in filteredItems" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noPembiayaan }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.jumlahTunggakan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.jumlahDikutip }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhAkhirBayaran }}</td>
                <td class="px-4 py-2 text-slate-600">
                  <span class="block">{{ item.cawangan }}</span>
                  <span class="text-xs text-slate-400">{{ item.pegawaiSeliaan }}</span>
                </td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      type="button"
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="goToDetail(item.id)"
                    >
                      <Eye class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.view") }}</span>
                    </button>
                    <button
                      type="button"
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="goToRekod(item.id)"
                    >
                      <Calendar class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Rekod</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td colspan="9" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <!-- Tab: SKH (Senarai Kutipan Harian) -->
      <article
        v-show="activeTab === 'skh'"
        class="rounded-lg border border-slate-200 bg-white shadow-sm"
      >
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Kutipan Harian (SKH)</h2>
          <div class="flex items-center gap-2">
            <select
              v-model="zon"
              class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            >
              <option value="">Semua Zon</option>
              <option v-for="z in ZON" :key="z" :value="z">{{ z }}</option>
            </select>
            <select
              v-model="pegawai"
              class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            >
              <option value="">Semua Pegawai</option>
              <option v-for="p in PEGAWAI" :key="p" :value="p">{{ p }}</option>
            </select>
            <button
              type="button"
              class="flex items-center gap-1.5 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
              @click="exportSkhExcel"
            >
              <Download class="h-4 w-4" />
              Export
            </button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Pembiayaan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Zon</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Pegawai Seliaan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Hari Kutipan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Kawasan / Blok</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in filteredSkh" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noPembiayaan }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.zon }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.pegawaiSeliaan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.hariKutipan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.kawasan }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ item.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <!-- Tab: Call Center -->
      <article
        v-show="activeTab === 'call-center'"
        class="rounded-lg border border-slate-200 bg-white shadow-sm"
      >
        <div class="border-b border-slate-100 px-4 py-3">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-sm font-semibold text-slate-900">Kutipan Call Center</h2>
              <p class="mt-1 text-xs text-slate-500">Maklum balas panggilan: Bayar segera, Janji bayar, RNA, NIS, VM, Busy, FUP, ENG</p>
            </div>
            <button
              type="button"
              class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
              @click="showRekodPanggilan = !showRekodPanggilan"
            >
              {{ showRekodPanggilan ? 'Sembunyikan' : 'Rekod Panggilan' }}
            </button>
          </div>
        </div>
        <!-- Rekod Panggilan form (6.4.3) -->
        <div v-if="showRekodPanggilan" class="border-b border-slate-100 bg-slate-50 p-4">
          <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-600">Rekod Panggilan Baru</h3>
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Usahawan</label>
              <select
                v-model="rekodPanggilan.akaun"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
              >
                <option value="">Pilih...</option>
                <option v-for="k in KUTIPAN_ITEMS" :key="k.id" :value="k.id">{{ k.nama }} ({{ k.noPembiayaan }})</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Maklum Balas</label>
              <select
                v-model="rekodPanggilan.maklumBalas"
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
              >
                <option value="">Pilih...</option>
                <option v-for="m in MAKLUM_BALAS_PANGGILAN" :key="m.value" :value="m.label">{{ m.label }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Catatan</label>
              <input
                v-model="rekodPanggilan.catatan"
                type="text"
                placeholder="Catatan panggilan..."
                class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"
              />
            </div>
            <div class="flex items-end">
              <button
                type="button"
                class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white"
                @click="submitRekodPanggilan"
              >
                Simpan
              </button>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">ID</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Pembiayaan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Telefon</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Panggilan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Pegawai</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Maklum Balas</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Catatan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in CALL_CENTER_ITEMS" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noPembiayaan }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.noTelefon }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhPanggilan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.pegawai }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-700">
                    {{ item.maklumBalas }}
                  </span>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.catatan }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <!-- Tab: PSAT -->
      <article
        v-show="activeTab === 'psat'"
        class="rounded-lg border border-slate-200 bg-white shadow-sm"
      >
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Proses Pelaksanaan PSAT (Perjumpaan Setempat Ahli TEMAN)</h2>
          <p class="mt-1 text-xs text-slate-500">Senarai Kutipan Mingguan (SKM), Penyata Kutipan Bulanan & Mingguan, Minit Mesyuarat PSAT</p>
        </div>
        <div class="divide-y divide-slate-100 p-4">
          <section>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Senarai Kutipan Mingguan (SKM)</h3>
            <table class="mt-2 w-full text-sm">
              <thead>
                <tr class="border-b border-slate-200 text-left">
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">Minggu</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">{{ t("common.amount") }}</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">Berjaya</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">Gagal</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">Janji Bayar</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in SKM_MINGGUAN" :key="row.minggu" class="border-b border-slate-100">
                  <td class="px-3 py-2">{{ row.minggu }}</td>
                  <td class="px-3 py-2">{{ row.jumlah }}</td>
                  <td class="px-3 py-2 text-emerald-600">{{ row.berjaya }}</td>
                  <td class="px-3 py-2 text-amber-600">{{ row.gagal }}</td>
                  <td class="px-3 py-2 text-blue-600">{{ row.janjiBayar }}</td>
                </tr>
              </tbody>
            </table>
          </section>
          <section class="pt-4">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jadual PSAT</h3>
            <table class="mt-2 w-full text-sm">
              <thead>
                <tr class="border-b border-slate-200 text-left">
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">Tajuk</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">{{ t("common.date") }}</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">Lokasi</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">Peserta</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-600">{{ t("common.status") }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in PSAT_ITEMS" :key="item.id" class="border-b border-slate-100">
                  <td class="px-3 py-2 font-medium">{{ item.tajuk }}</td>
                  <td class="px-3 py-2">{{ item.tarikh }}</td>
                  <td class="px-3 py-2">{{ item.lokasi }}</td>
                  <td class="px-3 py-2">{{ item.peserta }}</td>
                  <td class="px-3 py-2">
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                      {{ item.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </section>
        </div>
      </article>

      <!-- Tab: KPI Pegawai -->
      <article
        v-show="activeTab === 'kpi'"
        class="rounded-lg border border-slate-200 bg-white shadow-sm"
      >
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Dashboard Prestasi Kutipan - KPI Pegawai</h2>
          <p class="mt-1 text-xs text-slate-500">Peratus akaun dipulihkan, masa respon, keberkesanan collection, ranking cawangan/negeri/HQ</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Ranking</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Cawangan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Lawatan Berjaya</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Peratus Kejayaan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Sasaran</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">% vs Sasaran</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Akaun Dipulihkan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in KPI_PEGAWAI" :key="item.nama" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2">
                  <span
                    class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                    :class="item.rank <= 3 ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-700'"
                  >
                    {{ item.rank }}
                  </span>
                </td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.cawangan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.lawatanBerjaya }}</td>
                <td class="px-4 py-2">
                  <span class="font-medium text-emerald-600">{{ item.peratusKejayaan }}%</span>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.sasaran }}</td>
                <td class="px-4 py-2">
                  <span :class="item.peratusVsSasaran >= 100 ? 'text-emerald-600' : 'text-amber-600'">
                    {{ item.peratusVsSasaran }}%
                  </span>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.akaunDipulihkan }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <!-- Tab: Audit Log -->
      <article
        v-show="activeTab === 'audit'"
        class="rounded-lg border border-slate-200 bg-white shadow-sm"
      >
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Log Aktiviti & Jejak Audit</h2>
          <p class="mt-1 text-xs text-slate-500">Siapa pegawai bertugas, akaun dikunjungi/dihubungi, outcome setiap tindakan</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.dateTime") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Pegawai</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Outcome</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in AUDIT_LOG_ITEMS" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.tarikh }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.pegawai }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tindakan }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.akaun }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.outcome }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
