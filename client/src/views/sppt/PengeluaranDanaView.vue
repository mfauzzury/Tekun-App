<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, watch, onMounted } from "vue";
import { RouterLink } from "vue-router";
import {
  Eye,
  Send,
  Search,
  FileCheck,
  Wallet,
  Save,
  FileText,
  Shield,
  AlertTriangle,
  Layers,
  BarChart3,
  Download,
  FileSignature,
  AlertCircle,
  Server,
  RefreshCw,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import { fetchSpptModuleDatasets, listPengeluaranDana, pickSpptModuleDataset } from "@/api/sppt";
import {
  BANK_OPTIONS,
  RBAC_ROLES,
  simulateAccountValidation,
  type PengeluaranItem,
  type BatchDisbursement,
  type AuditTrailEntry,
  type LegalDocument,
  type ExceptionReport,
  type IntegrationStatus,
} from "@/data/pengeluaran-dana-dummy";
import { exportToCSV, exportToExcel, exportToPDF } from "@/composables/useExport";
import { useSpptStatus } from "@/composables/useSpptStatus";

const { t, tp } = useI18n();
const { statusLabel, statusClass } = useSpptStatus();

const items = ref<PengeluaranItem[]>([]);
const auditTrail = ref<AuditTrailEntry[]>([]);
const batchesData = ref<BatchDisbursement[]>([]);
const legalDocsTemplate = ref<LegalDocument[]>([]);
const exceptions = ref<ExceptionReport[]>([]);
const integrationStatuses = ref<IntegrationStatus[]>([]);
const dataLoading = ref(true);

onMounted(async () => {
  try {
    const [listRes, datasets] = await Promise.all([
      listPengeluaranDana({ limit: 100 }),
      fetchSpptModuleDatasets("pengeluaran"),
    ]);
    items.value = listRes.data as PengeluaranItem[];
    auditTrail.value = pickSpptModuleDataset(datasets, "audit_trail", [] as AuditTrailEntry[]);
    batchesData.value = pickSpptModuleDataset(datasets, "batches", [] as BatchDisbursement[]);
    legalDocsTemplate.value = pickSpptModuleDataset(datasets, "legal_docs", [] as LegalDocument[]);
    exceptions.value = pickSpptModuleDataset(datasets, "exceptions", [] as ExceptionReport[]);
    integrationStatuses.value = pickSpptModuleDataset(datasets, "integration_statuses", [] as IntegrationStatus[]);
  } finally {
    dataLoading.value = false;
  }
});

const activeTab = ref<"senarai" | "batch" | "audit" | "laporan" | "dokumen" | "exception" | "integrasi">("senarai");
const status = ref("");
const searchQuery = ref("");
const saving = ref(false);
const saved = ref(false);
const validationResult = ref<{ valid: boolean; message: string } | null>(null);
const validationLoading = ref(false);
const currentRole = ref((RBAC_ROLES[1] as (typeof RBAC_ROLES)[number]).id);
const selectedBatch = ref<BatchDisbursement | null>(null);
const batches = ref<BatchDisbursement[]>([]);

watch(batchesData, (val) => {
  batches.value = [...val];
}, { immediate: true });

const summary = [
  { label: "Menunggu Pengeluaran", value: 8 },
  { label: t("sppt.inProgress"), value: 3 },
  { label: "Berjaya Bulan Ini", value: 18 },
  { label: "Jumlah Dana Keluar", value: "RM 450K" },
];

const form = ref({
  idPembiayaan: "",
  namaUsahawan: "",
  jumlahPembiayaan: "",
  bank: "",
  noAkaunBank: "",
  tarikhPengeluaran: "",
  jenisPengeluaran: "Penuh" as "Penuh" | "Berperingkat",
  fasa: 1,
  peratusFasa: 40,
});

const filteredItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  const statusVal = status.value.toLowerCase();
  let list = [...items.value];
  if (query) {
    list = list.filter((i) => i.nama.toLowerCase().includes(query) || i.id.toLowerCase().includes(query) || i.idPembiayaan.toLowerCase().includes(query));
  }
  if (statusVal) {
    const statusMap: Record<string, string> = {
      menunggu: "Menunggu",
      proses: "Dalam Proses",
      berjaya: "Berjaya",
      gagal: "Gagal",
      ditolak: "Ditolak",
    };
    const match = statusMap[statusVal];
    if (match) list = list.filter((i) => i.status === match);
  }
  return list;
});

const selectedItem = ref<PengeluaranItem | null>(null);

watch(selectedItem, (item) => {
  if (item) {
    form.value = {
      idPembiayaan: item.idPembiayaan,
      namaUsahawan: item.nama,
      jumlahPembiayaan: item.jumlah,
      bank: item.bank ?? "",
      noAkaunBank: item.noAkaunBank ?? "",
      tarikhPengeluaran: item.tarikhIso ?? "",
      jenisPengeluaran: item.jenisPengeluaran,
      fasa: item.fasa ?? 1,
      peratusFasa: item.peratusFasa ?? 40,
    };
    validationResult.value = null;
  }
}, { immediate: true });

const rbacRole = computed(() => RBAC_ROLES.find((r) => r.id === currentRole.value) ?? RBAC_ROLES[0]);

function fraudRiskClass(r: string) {
  const map: Record<string, string> = {
    Rendah: "bg-emerald-100 text-emerald-700",
    Sederhana: "bg-amber-100 text-amber-700",
    Tinggi: "bg-red-100 text-red-700",
  };
  return map[r] ?? "bg-slate-100 text-slate-600";
}

function integrationStatusClass(s: string) {
  const map: Record<string, string> = {
    Aktif: "bg-emerald-100 text-emerald-700",
    Gagal: "bg-red-100 text-red-700",
    Penyelenggaraan: "bg-amber-100 text-amber-700",
  };
  return map[s] ?? "bg-slate-100 text-slate-600";
}

function selectItem(item: PengeluaranItem) {
  selectedItem.value = item;
}

function validateAccount() {
  validationLoading.value = true;
  validationResult.value = null;
  setTimeout(() => {
    validationResult.value = simulateAccountValidation(form.value.noAkaunBank, form.value.bank);
    validationLoading.value = false;
  }, 600);
}

function simpanPengeluaran() {
  saving.value = true;
  setTimeout(() => {
    saving.value = false;
    saved.value = true;
  }, 800);
}

function approveBatch(b: BatchDisbursement) {
  const idx = batches.value.findIndex((x) => x.id === b.id);
  if (idx >= 0) {
    batches.value[idx] = { ...b, status: "Diproses" };
  }
  selectedBatch.value = null;
}

function rejectBatch(b: BatchDisbursement) {
  const idx = batches.value.findIndex((x) => x.id === b.id);
  if (idx >= 0) {
    batches.value[idx] = { ...b, status: "Draf" };
  }
  selectedBatch.value = null;
}

const showEmptyState = computed(() => !selectedItem.value);

// Export
const exportData = computed(() =>
  items.value.map((i) => ({
    ID: i.id,
    "ID Pembiayaan": i.idPembiayaan,
    "Nama Usahawan": i.nama,
    Jumlah: i.jumlah,
    Tarikh: i.tarikh,
    Status: i.status,
    Bank: i.bank ?? "",
    "No Akaun": i.noAkaunBank ?? "",
    "Jenis Pengeluaran": i.jenisPengeluaran,
    "No Rujukan Bank": i.noRujukanBank ?? "",
  }))
);

function doExportCSV() {
  exportToCSV(exportData.value, `pengeluaran-dana-${new Date().toISOString().slice(0, 10)}`);
}

function doExportExcel() {
  exportToExcel(exportData.value, `pengeluaran-dana-${new Date().toISOString().slice(0, 10)}`);
}

function doExportPDF() {
  const el = document.getElementById("export-pdf-content");
  if (el) {
    exportToPDF("export-pdf-content", `pengeluaran-dana-${new Date().toISOString().slice(0, 10)}`);
  } else {
    window.print();
  }
}

const tabs = [
  { id: "senarai" as const, label: "Senarai & Pengeluaran", icon: Wallet },
  { id: "batch" as const, label: "Batch Disbursement", icon: Layers },
  { id: "audit" as const, label: "Audit Trail", icon: FileText },
  { id: "laporan" as const, label: "Laporan & Rekod", icon: BarChart3 },
  { id: "dokumen" as const, label: "Dokumen Undang-Undang", icon: FileSignature },
  { id: "exception" as const, label: "Pengurusan Isu", icon: AlertCircle },
  { id: "integrasi" as const, label: "Integrasi & Status", icon: Server },
];
</script>

<template>
  <AdminLayout>
    <div class="flex min-h-[calc(100vh-8rem)] flex-col">
      <SpptPageHeader
        title="Pengeluaran Dana"
        :breadcrumb="[{ label: 'Pembiayaan', to: '/admin/pembiayaan/penilaian' }, { label: 'Pengeluaran Dana' }]"
      >
        <template #actions>
          <div class="flex items-center gap-2">
            <!-- RBAC Simulator -->
            <select
              v-model="currentRole"
              class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 shadow-sm focus:border-slate-400 focus:outline-none"
              title="Simulasi RBAC - Pilih peranan"
            >
              <option v-for="r in RBAC_ROLES" :key="r.id" :value="r.id">{{ r.label }}</option>
            </select>
            <RouterLink
              to="/admin/pembiayaan/pengeluaran/proses"
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
            >
              Proses Pengeluaran
            </RouterLink>
          </div>
        </template>
      </SpptPageHeader>

      <SpptSummaryCards :items="summary" class="mt-2 shrink-0" />

      <!-- Tab Navigation -->
      <div class="mt-4 flex gap-1 overflow-x-auto border-b border-slate-200 bg-white px-2">
        <button
          v-for="t in tabs"
          :key="t.id"
          type="button"
          class="flex items-center gap-1.5 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition-colors"
          :class="activeTab === t.id ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
          @click="activeTab = t.id"
        >
          <component :is="t.icon" class="h-4 w-4" />
          {{ t.label }}
        </button>
      </div>

      <!-- Tab: Senarai & Pengeluaran -->
      <div v-show="activeTab === 'senarai'" class="mt-4 flex flex-1 min-h-0 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <aside class="flex w-[360px] shrink-0 flex-col border-r border-slate-200 bg-slate-50/50">
          <div class="border-b border-slate-200 bg-white px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Senarai Pengeluaran</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ filteredItems.length }} rekod</p>
          </div>
          <div class="border-b border-slate-200 p-3">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari no. atau nama..."
                class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
            <div class="mt-2">
              <select
                v-model="status"
                class="w-full rounded-lg border border-slate-300 bg-white py-2 px-3 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              >
                <option value="">Semua Status</option>
                <option value="menunggu">Menunggu</option>
                <option value="proses">Dalam Proses</option>
                <option value="berjaya">Berjaya</option>
                <option value="gagal">Gagal</option>
              </select>
            </div>
          </div>
          <div class="flex-1 overflow-y-auto p-2">
            <button
              v-for="item in filteredItems"
              :key="item.id"
              type="button"
              class="mb-2 w-full rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition-all hover:border-slate-300 hover:shadow"
              :class="[
                selectedItem?.id === item.id ? 'border-slate-400 bg-sky-50/80 ring-1 ring-sky-200' : '',
              ]"
              @click="selectItem(item)"
            >
              <p class="font-semibold uppercase tracking-wide text-slate-900">{{ item.nama }}</p>
              <p class="mt-1 font-mono text-xs text-slate-500">{{ item.id }}</p>
              <p class="mt-1 text-xs text-slate-600">{{ item.jumlah }} · {{ item.jenisPengeluaran }}</p>
              <div class="mt-2 flex flex-wrap gap-1.5">
                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="statusClass(item.status)">
                  {{ statusLabel(item.status) }}
                </span>
                <span v-if="item.fraudRisk" class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="fraudRiskClass(item.fraudRisk)">
                  {{ item.fraudRisk }}
                </span>
              </div>
            </button>
            <p v-if="filteredItems.length === 0" class="py-8 text-center text-sm text-slate-500">{{ tp("Tiada rekod dijumpai.") }}</p>
          </div>
        </aside>

        <main class="flex flex-1 flex-col overflow-hidden bg-white">
          <div v-if="showEmptyState" class="flex flex-1 items-center justify-center text-slate-400">
            <div class="text-center">
              <FileCheck class="mx-auto h-12 w-12 text-slate-300" />
              <p class="mt-2 text-sm font-medium text-slate-500">Pilih permohonan daripada senarai</p>
              <p class="text-xs text-slate-400">Klik pada permohonan di sebelah kiri untuk melihat dan mengisi maklumat pengeluaran</p>
            </div>
          </div>

          <div v-else-if="selectedItem" class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/50 px-6 py-4">
              <div>
                <h3 class="font-semibold uppercase tracking-wide text-slate-900">{{ selectedItem.nama }}</h3>
                <p class="mt-0.5 font-mono text-sm text-slate-500">{{ selectedItem.id }}</p>
                <div class="mt-1 flex flex-wrap gap-1.5">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(selectedItem.status)">
                    {{ statusLabel(selectedItem.status) }}
                  </span>
                  <span v-if="selectedItem.fraudRisk" class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="fraudRiskClass(selectedItem.fraudRisk)">
                    AI: {{ selectedItem.fraudRisk }}
                  </span>
                  <span v-if="selectedItem.bsasVerified" class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                    BSAS ✓
                  </span>
                  <span v-if="selectedItem.legalDocsComplete" class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-medium text-sky-700">
                    Dokumen ✓
                  </span>
                </div>
                <p v-if="selectedItem.fraudAlert" class="mt-1 text-xs text-amber-600">{{ selectedItem.fraudAlert }}</p>
              </div>
              <div class="flex items-center gap-1">
                <button type="button" class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-200/60 hover:text-slate-700" :title="t('common.view')">
                  <Eye class="h-5 w-5" />
                </button>
                <button
                  v-if="selectedItem.status === 'Menunggu'"
                  type="button"
                  class="rounded-lg p-2 text-emerald-600 transition-colors hover:bg-emerald-50"
                  title="Hantar"
                >
                  <Send class="h-5 w-5" />
                </button>
              </div>
            </div>

            <form class="flex-1 space-y-6 p-6" @submit.prevent="simpanPengeluaran">
              <!-- Fasa 2: Pengeluaran Penuh vs Berperingkat -->
              <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                  <Layers class="h-4 w-4 text-slate-500" />
                  <h4 class="text-sm font-semibold text-slate-900">Jenis Pengeluaran</h4>
                </div>
                <div class="space-y-4 p-4">
                  <div class="flex gap-4">
                    <label class="flex cursor-pointer items-center gap-2">
                      <input v-model="form.jenisPengeluaran" type="radio" value="Penuh" class="rounded border-slate-300" />
                      <span class="text-sm">Penuh (100%)</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2">
                      <input v-model="form.jenisPengeluaran" type="radio" value="Berperingkat" class="rounded border-slate-300" />
                      <span class="text-sm">Berperingkat</span>
                    </label>
                  </div>
                  <div v-if="form.jenisPengeluaran === 'Berperingkat'" class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="mb-1 block text-xs font-medium text-slate-600">Fasa</label>
                      <select v-model="form.fasa" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option :value="1">Fasa 1 (40%)</option>
                        <option :value="2">Fasa 2 (30%)</option>
                        <option :value="3">Fasa 3 (30%)</option>
                      </select>
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-medium text-slate-600">Peratus</label>
                      <input v-model.number="form.peratusFasa" type="number" min="1" max="100" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                  </div>
                </div>
              </section>

              <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
                  <Wallet class="h-4 w-4 text-slate-500" />
                  <h4 class="text-sm font-semibold text-slate-900">Maklumat Pengeluaran</h4>
                </div>
                <div class="space-y-4 p-4">
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">ID Pembiayaan</label>
                    <input v-model="form.idPembiayaan" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Contoh: P-2024-001" />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Nama Usahawan</label>
                    <input v-model="form.namaUsahawan" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Nama penuh usahawan" />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Jumlah Pembiayaan</label>
                    <input v-model="form.jumlahPembiayaan" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Contoh: RM 50,000" />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Bank</label>
                    <select v-model="form.bank" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                      <option value="">-- Pilih Bank --</option>
                      <option v-for="b in BANK_OPTIONS" :key="b" :value="b">{{ b }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">No Akaun Bank</label>
                    <div class="flex gap-2">
                      <input
                        v-model="form.noAkaunBank"
                        type="text"
                        class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        placeholder="Nombor akaun bank"
                      />
                      <button
                        type="button"
                        :disabled="validationLoading"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-60"
                        title="Fasa 2: Validasi checksum (simulasi)"
                        @click="validateAccount"
                      >
                        <Shield class="h-4 w-4" />
                      </button>
                    </div>
                    <p v-if="validationResult" class="mt-1 text-xs" :class="validationResult.valid ? 'text-emerald-600' : 'text-red-600'">
                      {{ validationResult.message }}
                    </p>
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Tarikh Pengeluaran</label>
                    <input v-model="form.tarikhPengeluaran" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                  </div>
                </div>
              </section>

              <div v-if="saved" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                Pengeluaran Dana berjaya disimpan. (Dummy – tiada sambungan ke jadual.)
              </div>

              <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4">
                <button
                  type="submit"
                  :disabled="saving"
                  class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-60"
                >
                  <Save class="h-4 w-4" />
                  {{ saving ? "Menyimpan..." : "Simpan" }}
                </button>
              </div>
            </form>
          </div>
        </main>
      </div>

      <!-- Tab: Batch Disbursement -->
      <div v-show="activeTab === 'batch'" class="mt-4 flex flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-slate-900">Batch Disbursement (Fasa 2)</h3>
        <p class="mb-4 text-sm text-slate-500">Proses pengeluaran secara batch dan re-batch. Approval workflow berlapis.</p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="b in batches"
            :key="b.id"
            class="rounded-lg border border-slate-200 p-4 shadow-sm transition-all hover:border-slate-300"
            :class="{ 'ring-2 ring-sky-200': selectedBatch?.id === b.id }"
          >
            <div class="flex items-start justify-between">
              <div>
                <p class="font-mono text-sm font-semibold text-slate-900">{{ b.id }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ b.tarikh }}</p>
                <p class="mt-1 text-lg font-semibold text-slate-700">{{ b.jumlahTotal }}</p>
                <p class="text-xs text-slate-500">{{ b.jumlahRekod }} rekod</p>
              </div>
              <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(b.status)">{{ statusLabel(b.status) }}</span>
            </div>
            <div v-if="rbacRole.canApprove && (b.status === 'Menunggu Kelulusan' || b.status === 'Draf')" class="mt-3 flex gap-2">
              <button
                v-if="b.status === 'Menunggu Kelulusan'"
                type="button"
                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                @click="approveBatch(b)"
              >
                Lulus
              </button>
              <button
                type="button"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
                @click="selectedBatch = selectedBatch?.id === b.id ? null : b"
              >
                {{ selectedBatch?.id === b.id ? "Tutup" : "Detail" }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: Audit Trail -->
      <div v-show="activeTab === 'audit'" class="mt-4 flex flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
          <h3 class="text-lg font-semibold text-slate-900">Audit Trail (Fasa 1)</h3>
          <p class="mt-1 text-sm text-slate-500">Rekod pengesahan akaun, transaksi BSAS, dual verification. RBAC: {{ rbacRole.canViewAudit ? 'Akses dibenarkan' : 'Akses ditolak' }}</p>
        </div>
        <div v-if="rbacRole.canViewAudit" class="overflow-y-auto p-6">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-left">
                <th class="pb-2 font-medium text-slate-600">{{ t("common.date") }}</th>
                <th class="pb-2 font-medium text-slate-600">Masa</th>
                <th class="pb-2 font-medium text-slate-600">Pegawai</th>
                <th class="pb-2 font-medium text-slate-600">{{ t("common.actions") }}</th>
                <th class="pb-2 font-medium text-slate-600">Keputusan</th>
                <th class="pb-2 font-medium text-slate-600">No Rujukan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="a in auditTrail" :key="a.id" class="border-b border-slate-100">
                <td class="py-2">{{ a.tarikh }}</td>
                <td class="py-2">{{ a.masa }}</td>
                <td class="py-2">{{ a.namaPegawai }} ({{ a.idPegawai }})</td>
                <td class="py-2">{{ a.tindakan }}</td>
                <td class="py-2">{{ a.keputusan ?? "-" }}</td>
                <td class="py-2 font-mono text-xs">{{ a.noRujukan ?? "-" }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="flex flex-1 items-center justify-center p-8 text-slate-500">
          <AlertTriangle class="h-12 w-12" />
          <p class="mt-2">Anda tidak mempunyai akses untuk melihat audit trail.</p>
        </div>
      </div>

      <!-- Tab: Laporan & Rekod -->
      <div v-show="activeTab === 'laporan'" class="mt-4 flex flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Laporan & Rekod Transaksi (Fasa 1)</h3>
            <p class="mt-1 text-sm text-slate-500">Filter, drill-down, eksport PDF/Excel/CSV. RBAC: {{ rbacRole.canExport ? 'Eksport dibenarkan' : 'Eksport ditolak' }}</p>
          </div>
          <div v-if="rbacRole.canExport" class="flex gap-2">
            <button
              type="button"
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
              @click="doExportCSV"
            >
              <Download class="h-4 w-4" />
              CSV
            </button>
            <button
              type="button"
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
              @click="doExportExcel"
            >
              <Download class="h-4 w-4" />
              Excel
            </button>
            <button
              type="button"
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
              @click="doExportPDF"
            >
              <Download class="h-4 w-4" />
              PDF
            </button>
          </div>
        </div>
        <div id="export-pdf-content" class="overflow-y-auto p-6">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-left">
                <th class="pb-2 font-medium text-slate-600">ID</th>
                <th class="pb-2 font-medium text-slate-600">ID Pembiayaan</th>
                <th class="pb-2 font-medium text-slate-600">{{ t("common.name") }}</th>
                <th class="pb-2 font-medium text-slate-600">{{ t("common.amount") }}</th>
                <th class="pb-2 font-medium text-slate-600">{{ t("common.date") }}</th>
                <th class="pb-2 font-medium text-slate-600">{{ t("common.status") }}</th>
                <th class="pb-2 font-medium text-slate-600">Bank</th>
                <th class="pb-2 font-medium text-slate-600">No Akaun</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="i in items" :key="i.id" class="border-b border-slate-100">
                <td class="py-2 font-mono">{{ i.id }}</td>
                <td class="py-2 font-mono">{{ i.idPembiayaan }}</td>
                <td class="py-2">{{ i.nama }}</td>
                <td class="py-2">{{ i.jumlah }}</td>
                <td class="py-2">{{ i.tarikh }}</td>
                <td class="py-2">{{ statusLabel(i.status) }}</td>
                <td class="py-2">{{ i.bank ?? "-" }}</td>
                <td class="py-2 font-mono">{{ i.noAkaunBank ?? "-" }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Dokumen Undang-Undang -->
      <div v-show="activeTab === 'dokumen'" class="mt-4 flex flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-2 text-lg font-semibold text-slate-900">Dokumen Undang-Undang (Fasa 3)</h3>
        <p class="mb-4 text-sm text-slate-500">Perjanjian Pembiayaan, Surat Ikatan, Jadual Bayaran Balik. E-signature, RBAC, enkripsi AES-256 (simulasi).</p>
        <div class="space-y-3">
          <div
            v-for="doc in legalDocsTemplate"
            :key="doc.id"
            class="flex items-center justify-between rounded-lg border border-slate-200 p-4"
          >
            <div class="flex items-center gap-3">
              <FileSignature class="h-8 w-8 text-slate-400" />
              <div>
                <p class="font-medium text-slate-900">{{ doc.nama }}</p>
                <p class="text-xs text-slate-500">{{ statusLabel(doc.status) }} · {{ doc.tarikhJana ?? "-" }}</p>
              </div>
            </div>
            <span
              class="rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="
                doc.status === 'Disahkan'
                  ? 'bg-emerald-100 text-emerald-700'
                  : doc.status === 'Ditandatangani'
                    ? 'bg-sky-100 text-sky-700'
                    : doc.status === 'Dijana'
                      ? 'bg-amber-100 text-amber-700'
                      : 'bg-slate-100 text-slate-600'
              "
            >
              {{ statusLabel(doc.status) }}
            </span>
          </div>
        </div>
        <p class="mt-4 text-xs text-slate-400">Pelepasan dana hanya dibenarkan selepas semua dokumen lengkap dan disahkan (Spec 3.4.4).</p>
      </div>

      <!-- Tab: Pengurusan Isu / Exception -->
      <div v-show="activeTab === 'exception'" class="mt-4 flex flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
          <h3 class="text-lg font-semibold text-slate-900">Pengurusan Isu & Exception (Fasa 3)</h3>
          <p class="mt-1 text-sm text-slate-500">Alert kegagalan, refund/reprocess, manual override dengan dual approval, tracking & escalation.</p>
        </div>
        <div class="overflow-y-auto p-6">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-left">
                <th class="pb-2 font-medium text-slate-600">ID Transaksi</th>
                <th class="pb-2 font-medium text-slate-600">{{ t("common.date") }}</th>
                <th class="pb-2 font-medium text-slate-600">Sebab</th>
                <th class="pb-2 font-medium text-slate-600">{{ t("common.actions") }}</th>
                <th class="pb-2 font-medium text-slate-600">{{ t("common.status") }}</th>
                <th class="pb-2 font-medium text-slate-600">Pegawai</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="e in exceptions" :key="e.id" class="border-b border-slate-100">
                <td class="py-2 font-mono">{{ e.idTransaksi }}</td>
                <td class="py-2">{{ e.tarikh }}</td>
                <td class="py-2">{{ e.sebab }}</td>
                <td class="py-2">{{ e.tindakan }}</td>
                <td class="py-2">
                  <span
                    class="rounded-full px-2 py-0.5 text-xs"
                    :class="
                      e.status === 'Selesai'
                        ? 'bg-emerald-100 text-emerald-700'
                        : e.status === 'Dalam Tindakan'
                          ? 'bg-amber-100 text-amber-700'
                          : 'bg-red-100 text-red-700'
                    "
                  >
                    {{ statusLabel(e.status) }}
                  </span>
                </td>
                <td class="py-2">{{ e.pegawaiBertanggungjawab }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Integrasi & Status -->
      <div v-show="activeTab === 'integrasi'" class="mt-4 flex flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-2 text-lg font-semibold text-slate-900">Integrasi & Status (Fasa 3)</h3>
        <p class="mb-4 text-sm text-slate-500">BSAS, MyGDX, Takaful, Wasiat, PERKESO, EFT/IBG. Real-time status (simulasi).</p>
        <div class="space-y-3">
          <div
            v-for="int in integrationStatuses"
            :key="int.sistem"
            class="flex items-center justify-between rounded-lg border border-slate-200 p-4"
          >
            <div class="flex items-center gap-3">
              <Server class="h-8 w-8 text-slate-400" />
              <div>
                <p class="font-medium text-slate-900">{{ int.sistem }}</p>
                <p class="text-xs text-slate-500">Last sync: {{ int.lastSync ?? "-" }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span v-if="int.transaksiHariIni" class="text-sm text-slate-500">{{ int.transaksiHariIni }} transaksi hari ini</span>
              <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="integrationStatusClass(int.status)">
                {{ statusLabel(int.status) }}
              </span>
              <button type="button" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Refresh">
                <RefreshCw class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
          <div class="flex items-start gap-2">
            <AlertTriangle class="h-5 w-5 shrink-0 text-amber-600" />
            <div class="text-sm text-amber-800">
              <p class="font-medium">AI Fraud Detection (Simulasi)</p>
              <p class="mt-1">Sistem mengesan D-2024-005: Akaun pihak ketiga mencurigakan. Tahap risiko: Tinggi. Tindakan: Transaksi ditahan untuk semakan manual.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
