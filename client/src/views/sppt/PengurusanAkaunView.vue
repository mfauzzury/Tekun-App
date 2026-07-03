<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import { RouterLink } from "vue-router";
import { Eye, Pencil, Download, FileText, Wallet, Phone, AlertTriangle, FileSearch, X } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import {
  items as rawItems,
  getSummaryFromItems,
  shortcutFunctions,
  type AkaunItem,
} from "@/data/pengurusan-akaun-dummy";
import { exportToCSV, exportToExcel, exportToPDF } from "@/composables/useExport";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");
const page = ref(1);
const perPage = 10;
const selectedItem = ref<AkaunItem | null>(null);
const editItem = ref<AkaunItem | null>(null);

const filterOptions = [
  { value: "aktif", label: "Aktif" },
  { value: "tunggakan", label: "Tunggakan" },
  { value: "npf", label: "NPF" },
  { value: "selesai", label: "Selesai" },
  { value: "dibekukan", label: "Dibekukan" },
  { value: "batal", label: "Batal" },
];

const filteredItems = computed(() => {
  const query = q.value.trim().toLowerCase();
  const statusVal = status.value.toLowerCase();
  let list = [...rawItems];

  if (query) {
    list = list.filter(
      (i) =>
        i.id.toLowerCase().includes(query) ||
        i.nama.toLowerCase().includes(query) ||
        i.ic.includes(query) ||
        (i.ssm && i.ssm.toLowerCase().includes(query)) ||
        (i.pukonsa && i.pukonsa.toLowerCase().includes(query))
    );
  }

  if (statusVal) {
    const statusMap: Record<string, string> = {
      aktif: "Aktif",
      tunggakan: "Tunggakan",
      npf: "NPF",
      selesai: "Selesai",
      dibekukan: "Dibekukan",
      batal: "Batal",
    };
    const match = statusMap[statusVal];
    if (match) list = list.filter((i) => i.status === match);
  }

  return list;
});

const paginatedItems = computed(() => {
  const start = (page.value - 1) * perPage;
  return filteredItems.value.slice(start, start + perPage);
});

const totalPages = computed(() => Math.ceil(filteredItems.value.length / perPage) || 1);

const summary = computed(() => getSummaryFromItems(filteredItems.value));

function statusClass(s: string) {
  const map: Record<string, string> = {
    Aktif: "bg-emerald-100 text-emerald-700",
    Selesai: "bg-slate-100 text-slate-600",
    Tunggakan: "bg-amber-100 text-amber-700",
    NPF: "bg-red-100 text-red-700",
    Dibekukan: "bg-blue-100 text-blue-700",
    Batal: "bg-slate-100 text-slate-500",
    "Write Off": "bg-rose-100 text-rose-700",
    "Meninggal Dunia": "bg-slate-200 text-slate-600",
    Reschedule: "bg-violet-100 text-violet-700",
  };
  return map[s] ?? "bg-slate-100 text-slate-600";
}

function risikoClass(r: string) {
  const map: Record<string, string> = {
    Normal: "bg-emerald-500",
    Amaran: "bg-amber-500",
    Kritikal: "bg-red-500",
  };
  return map[r] ?? "bg-slate-400";
}

function fmtRm(n: number) {
  return n === 0 ? "—" : `RM ${n.toLocaleString("en-MY")}`;
}

function openDetail(item: AkaunItem) {
  selectedItem.value = item;
}

function closeDetail() {
  selectedItem.value = null;
}

function openEdit(item: AkaunItem) {
  editItem.value = { ...item };
}

function closeEdit() {
  editItem.value = null;
}

function doExport(format: "csv" | "excel" | "pdf") {
  const data = filteredItems.value.map((i) => ({
    "No. Akaun": i.id,
    IC: i.ic,
    Nama: i.nama,
    Cawangan: i.cawangan,
    Produk: i.produk,
    Status: i.status,
    "Baki Akhir": fmtRm(i.bakiAkhir),
    Tunggakan: fmtRm(i.tunggakan),
    "Bayaran Bulanan": fmtRm(i.bayaranBulanan),
  }));

  if (format === "csv") exportToCSV(data, "senarai-akaun");
  else if (format === "excel") exportToExcel(data, "senarai-akaun");
  else {
    const el = document.getElementById("export-table-akaun");
    if (el) exportToPDF("export-table-akaun", "senarai-akaun");
  }
}

// Hidden table for PDF export
const exportTableData = computed(() => filteredItems.value);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pengurusan Akaun"
        :breadcrumb="[{ label: 'Pembiayaan', to: '/admin/pembiayaan/penilaian' }, { label: 'Pengurusan Akaun' }]"
        action-label="Kemaskini Akaun"
        action-to="/admin/pembiayaan/akaun/kemaskini"
      />

      <!-- Shortcut functions (SPEC 4.2.2) -->
      <div class="flex flex-wrap gap-2">
        <RouterLink
          v-for="s in shortcutFunctions"
          :key="s.label"
          :to="s.to"
          class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
        >
          <FileSearch v-if="s.icon === 'FileSearch'" class="h-4 w-4" />
          <Wallet v-else-if="s.icon === 'Wallet'" class="h-4 w-4" />
          <Phone v-else-if="s.icon === 'Phone'" class="h-4 w-4" />
          <AlertTriangle v-else class="h-4 w-4" />
          {{ s.label }}
        </RouterLink>
      </div>

      <SpptSummaryCards :items="summary" />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Akaun</h2>
          <div class="flex flex-wrap items-center gap-3">
            <SpptFilterBar
              v-model:q="q"
              v-model:status="status"
              search-placeholder="Cari no. akaun, nama, IC, SSM..."
              :filter-options="filterOptions"
            />
            <div class="flex items-center gap-1">
              <button
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50"
                title="Export CSV"
                @click="doExport('csv')"
              >
                <Download class="h-3.5 w-3.5" /> CSV
              </button>
              <button
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50"
                title="Export Excel"
                @click="doExport('excel')"
              >
                <FileText class="h-3.5 w-3.5" /> Excel
              </button>
              <button
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50"
                title="Export PDF"
                @click="doExport('pdf')"
              >
                <FileText class="h-3.5 w-3.5" /> PDF
              </button>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Risiko</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">IC / Nama</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Cawangan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Produk</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Baki Akhir</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tunggakan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Bayaran/Bulan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in paginatedItems" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2">
                  <span
                    class="inline-block h-2.5 w-2.5 rounded-full"
                    :class="risikoClass(item.risiko)"
                    :title="item.risiko"
                  />
                </td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2">
                  <div class="font-medium text-slate-900">{{ item.nama }}</div>
                  <div class="text-xs text-slate-500">{{ item.ic }}</div>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.cawangan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.produk }}</td>
                <td class="px-4 py-2 font-medium text-slate-700">{{ fmtRm(item.bakiAkhir) }}</td>
                <td class="px-4 py-2" :class="item.tunggakan > 0 ? 'text-amber-600 font-medium' : 'text-slate-500'">
                  {{ fmtRm(item.tunggakan) }}
                </td>
                <td class="px-4 py-2 text-slate-600">{{ fmtRm(item.bayaranBulanan) }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      :title="t('common.view')"
                      @click="openDetail(item)"
                    >
                      <Eye class="h-3.5 w-3.5" />
                    </button>
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      :title="t('common.edit')"
                      @click="openEdit(item)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td colspan="10" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div
          v-if="totalPages > 1"
          class="flex items-center justify-between border-t border-slate-100 px-4 py-3"
        >
          <p class="text-xs text-slate-500">
            Menunjukkan {{ (page - 1) * perPage + 1 }}-{{ Math.min(page * perPage, filteredItems.length) }} daripada
            {{ filteredItems.length }} rekod
          </p>
          <div class="flex gap-1">
            <button
              class="rounded border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 disabled:opacity-50"
              :disabled="page <= 1"
              @click="page = Math.max(1, page - 1)"
            >
              Sebelum
            </button>
            <button
              class="rounded border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 disabled:opacity-50"
              :disabled="page >= totalPages"
              @click="page = Math.min(totalPages, page + 1)"
            >
              Seterusnya
            </button>
          </div>
        </div>
      </article>

      <!-- Hidden table for PDF export -->
      <div id="export-table-akaun" class="hidden">
        <table>
          <thead>
            <tr>
              <th>No. Akaun</th>
              <th>IC</th>
              <th>{{ t("common.name") }}</th>
              <th>Cawangan</th>
              <th>Produk</th>
              <th>{{ t("common.status") }}</th>
              <th>Baki Akhir</th>
              <th>Tunggakan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="i in exportTableData" :key="i.id">
              <td>{{ i.id }}</td>
              <td>{{ i.ic }}</td>
              <td>{{ i.nama }}</td>
              <td>{{ i.cawangan }}</td>
              <td>{{ i.produk }}</td>
              <td>{{ i.status }}</td>
              <td>{{ fmtRm(i.bakiAkhir) }}</td>
              <td>{{ fmtRm(i.tunggakan) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Lihat Detail -->
    <Teleport to="body">
      <div
        v-if="selectedItem"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeDetail"
      >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl">
          <div class="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900">Butiran Akaun</h3>
            <button
              class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
              @click="closeDetail"
            >
              <X class="h-5 w-5" />
            </button>
          </div>
          <div v-if="selectedItem" class="space-y-4 p-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-xs text-slate-500">No. Akaun</p>
                <p class="font-mono font-medium">{{ selectedItem.id }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Status</p>
                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(selectedItem.status)">
                  {{ selectedItem.status }}
                </span>
              </div>
              <div>
                <p class="text-xs text-slate-500">IC</p>
                <p>{{ selectedItem.ic }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Nama</p>
                <p class="font-medium">{{ selectedItem.nama }}</p>
              </div>
              <div v-if="selectedItem.namaSyarikat">
                <p class="text-xs text-slate-500">Nama Syarikat</p>
                <p>{{ selectedItem.namaSyarikat }}</p>
              </div>
              <div v-if="selectedItem.ssm">
                <p class="text-xs text-slate-500">SSM</p>
                <p>{{ selectedItem.ssm }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Cawangan / Negeri</p>
                <p>{{ selectedItem.cawangan }}, {{ selectedItem.negeri }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Produk</p>
                <p>{{ selectedItem.produk }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Tarikh Mula / Tamat</p>
                <p>{{ selectedItem.tarikhMula }} – {{ selectedItem.tarikhTamat }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Jumlah Pembiayaan</p>
                <p class="font-medium">{{ fmtRm(selectedItem.jumlahPembiayaan) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Baki Pokok</p>
                <p>{{ fmtRm(selectedItem.bakiPokok) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Baki Keuntungan</p>
                <p>{{ fmtRm(selectedItem.bakiKeuntungan) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Simpanan</p>
                <p>{{ fmtRm(selectedItem.bakiSimpanan) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Penalti</p>
                <p>{{ fmtRm(selectedItem.penalti) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Tunggakan</p>
                <p class="font-medium" :class="selectedItem.tunggakan > 0 ? 'text-amber-600' : ''">
                  {{ fmtRm(selectedItem.tunggakan) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Baki Akhir</p>
                <p class="font-semibold">{{ fmtRm(selectedItem.bakiAkhir) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Bayaran Bulanan</p>
                <p>{{ fmtRm(selectedItem.bayaranBulanan) }}</p>
              </div>
              <div v-if="selectedItem.noBsas">
                <p class="text-xs text-slate-500">No. Sijil BSAS</p>
                <p>{{ selectedItem.noBsas }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Edit (Kemaskini) -->
    <Teleport to="body">
      <div
        v-if="editItem"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeEdit"
      >
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl">
          <div class="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900">Kemaskini Akaun</h3>
            <button
              class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
              @click="closeEdit"
            >
              <X class="h-5 w-5" />
            </button>
          </div>
          <div class="space-y-4 p-4">
            <p class="text-sm text-slate-600">
              Kemaskini maklumat akaun <strong>{{ editItem.nama }}</strong> ({{ editItem.id }}).
            </p>
            <p class="text-xs text-slate-500">
              Nota: Dalam implementasi penuh, borang ini akan melalui workflow perakuan berperingkat (SPEC 4.26.2) dan audit trail (SPEC 4.26.3).
            </p>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
              Borang kemaskini penuh akan disambungkan apabila API/backend sedia. Data dummy sahaja.
            </div>
            <div class="flex justify-end gap-2">
              <button
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                @click="closeEdit"
              >
                Batal
              </button>
              <button
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                @click="closeEdit"
              >
                Simpan (Dummy)
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
