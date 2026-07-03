<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import { RouterLink } from "vue-router";
import { Eye, Pencil, Download, FileText, X, Bell, FileSignature, History } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import {
  items as rawItems,
  getSummaryFromItems,
  getAuditLogsForJaminan,
  getUnreadNotifikasi,
  notifikasi,
  type JaminanItem,
} from "@/data/pengurusan-jaminan-dummy";
import { exportToCSV, exportToExcel, exportToPDF } from "@/composables/useExport";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");
const page = ref(1);
const perPage = 10;
const selectedItem = ref<JaminanItem | null>(null);
const showNotifikasi = ref(false);
const notifikasiDismissed = ref<Set<string>>(new Set());

const filterOptions = [
  { value: "aktif", label: "Aktif" },
  { value: "sah", label: "Sah" },
  { value: "semakan", label: "Perlu Semakan" },
  { value: "tamat", label: "Tamat Tempoh" },
  { value: "dilepaskan", label: "Dilepaskan" },
  { value: "ditolak", label: "Ditolak" },
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
        i.noPinjaman.toLowerCase().includes(query)
    );
  }

  if (statusVal) {
    if (statusVal === "sah") {
      list = list.filter((i) => i.status === "Aktif" || i.status === "Dilepaskan");
    } else {
      const statusMap: Record<string, string> = {
        aktif: "Aktif",
        semakan: "Perlu Semakan",
        tamat: "Tamat Tempoh",
        dilepaskan: "Dilepaskan",
        ditolak: "Ditolak",
      };
      const match = statusMap[statusVal];
      if (match) list = list.filter((i) => i.status === match);
    }
  }

  return list;
});

const paginatedItems = computed(() => {
  const start = (page.value - 1) * perPage;
  return filteredItems.value.slice(start, start + perPage);
});

const totalPages = computed(() => Math.ceil(filteredItems.value.length / perPage) || 1);

const summary = computed(() => getSummaryFromItems(filteredItems.value));

const unreadNotifikasi = computed(() => getUnreadNotifikasi().filter((n) => !notifikasiDismissed.value.has(n.id)));

function statusClass(s: string) {
  const map: Record<string, string> = {
    Aktif: "bg-emerald-100 text-emerald-700",
    "Perlu Semakan": "bg-amber-100 text-amber-700",
    "Tamat Tempoh": "bg-red-100 text-red-700",
    Dilepaskan: "bg-slate-100 text-slate-600",
    Ditolak: "bg-slate-100 text-slate-500",
  };
  return map[s] ?? "bg-slate-100 text-slate-600";
}

function risikoClass(r: string) {
  return r === "Tinggi" ? "bg-amber-500" : "bg-emerald-500";
}

function fmtRm(n: number) {
  return n === 0 ? "—" : `RM ${n.toLocaleString("en-MY")}`;
}

function openDetail(item: JaminanItem) {
  selectedItem.value = item;
}

function closeDetail() {
  selectedItem.value = null;
}

function auditLogsFor(item: JaminanItem) {
  return getAuditLogsForJaminan(item.id);
}

function doExport(format: "csv" | "excel" | "pdf") {
  const data = filteredItems.value.map((i) => ({
    "No. Jaminan": i.id,
    Nama: i.nama,
    Jenis: i.jenis,
    Nilai: fmtRm(i.nilai),
    Status: i.status,
    "No. Pinjaman": i.noPinjaman,
    "Tarikh Tamat": i.tarikhTamat,
  }));

  if (format === "csv") exportToCSV(data, "senarai-jaminan");
  else if (format === "excel") exportToExcel(data, "senarai-jaminan");
  else {
    const el = document.getElementById("export-table-jaminan");
    if (el) exportToPDF("export-table-jaminan", "senarai-jaminan");
  }
}

function cetakSuratLanjutan(item: JaminanItem) {
  const win = window.open("", "_blank");
  if (!win) return;
  win.document.write(`
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Surat Lanjutan Projek - ${item.id}</title>
<style>body{font-family:system-ui,sans-serif;padding:40px;max-width:600px;margin:0 auto;font-size:14px;line-height:1.6}
h1{font-size:18px;border-bottom:1px solid #333;padding-bottom:8px}
.meta{color:#666;font-size:12px;margin-bottom:24px}
.section{margin:16px 0}
.signature{margin-top:48px}
</style>
</head>
<body>
<h1>SURAT LANJUTAN PROJEK</h1>
<div class="meta">No. Rujukan: ${item.id} | Tarikh: ${new Date().toLocaleDateString("ms-MY")}</div>
<div class="section">
<strong>Kepada:</strong><br>
${item.nama}<br>
No. Pinjaman: ${item.noPinjaman}
</div>
<div class="section">
<strong>Jenis Jaminan:</strong> ${item.jenis}<br>
<strong>Nilai:</strong> ${fmtRm(item.nilai)}<br>
<strong>Tempoh Tamat:</strong> ${item.tarikhTamat}
</div>
<div class="section">
Adalah dimaklumkan bahawa tempoh jaminan di atas akan tamat dalam masa terdekat. Sila kemukakan dokumen lanjutan atau penggantian bagi memastikan kelangsungan pembiayaan.
</div>
<div class="section">
Sekian, terima kasih.
</div>
<div class="signature">
_________________________<br>
<small>Pegawai Pengurusan Jaminan<br>TEKUN Nasional</small>
</div>
</body>
</html>`);
  win.document.close();
  win.print();
  win.close();
}

function dismissNotifikasi(id: string) {
  notifikasiDismissed.value = new Set([...notifikasiDismissed.value, id]);
}

function dismissAllNotifikasi() {
  notifikasi.forEach((n) => notifikasiDismissed.value.add(n.id));
  notifikasiDismissed.value = new Set(notifikasiDismissed.value);
  showNotifikasi.value = false;
}

const exportTableData = computed(() => filteredItems.value);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pengurusan Jaminan"
        :breadcrumb="[{ label: 'Jaminan', to: '/admin/jaminan' }]"
        action-label="Kemaskini Jaminan"
        action-to="/admin/jaminan/kemaskini"
      />

      <!-- Fasa 4: Notifikasi (SPEC 11.1.6, 11.1.7) -->
      <div v-if="unreadNotifikasi.length > 0" class="rounded-lg border border-amber-200 bg-amber-50 p-3">
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-start gap-2">
            <Bell class="mt-0.5 h-5 w-5 text-amber-600" />
            <div>
              <h3 class="text-sm font-semibold text-amber-900">Notifikasi Jaminan</h3>
              <div class="mt-1 space-y-1 text-sm text-amber-800">
                <p v-for="n in unreadNotifikasi.slice(0, 3)" :key="n.id" class="flex items-center gap-2">
                  <span>{{ n.mesej }}</span>
                  <button
                    class="text-amber-600 hover:underline"
                    @click="dismissNotifikasi(n.id)"
                  >
                    Dismiss
                  </button>
                </p>
                <p v-if="unreadNotifikasi.length > 3" class="text-slate-600">
                  +{{ unreadNotifikasi.length - 3 }} lagi
                </p>
              </div>
            </div>
          </div>
          <button
            class="rounded px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100"
            @click="dismissAllNotifikasi"
          >
            Tutup Semua
          </button>
        </div>
      </div>

      <SpptSummaryCards :items="summary" />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">{{ t("sppt.collateralList") }}</h2>
          <div class="flex flex-wrap items-center gap-3">
            <SpptFilterBar
              v-model:q="q"
              v-model:status="status"
              search-placeholder="Cari nama atau no. jaminan..."
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
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Jaminan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Nilai</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Pinjaman</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.expiryDate") }}</th>
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
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.jenis }}</td>
                <td class="px-4 py-2 text-slate-600">{{ fmtRm(item.nilai) }}</td>
                <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ item.noPinjaman }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhTamat }}</td>
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
                    <RouterLink
                      :to="`/admin/jaminan/kemaskini?id=${item.id}`"
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      :title="t('common.edit')"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                    </RouterLink>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td colspan="9" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
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

      <div id="export-table-jaminan" class="hidden">
        <table>
          <thead>
            <tr>
              <th>No. Jaminan</th>
              <th>{{ t("common.name") }}</th>
              <th>Jenis</th>
              <th>Nilai</th>
              <th>{{ t("common.status") }}</th>
              <th>No. Pinjaman</th>
              <th>{{ t("sppt.expiryDate") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="i in exportTableData" :key="i.id">
              <td>{{ i.id }}</td>
              <td>{{ i.nama }}</td>
              <td>{{ i.jenis }}</td>
              <td>{{ fmtRm(i.nilai) }}</td>
              <td>{{ i.status }}</td>
              <td>{{ i.noPinjaman }}</td>
              <td>{{ i.tarikhTamat }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Lihat Detail + Audit Trail (Fasa 4) -->
    <Teleport to="body">
      <div
        v-if="selectedItem"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeDetail"
      >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl">
          <div class="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900">Butiran Jaminan</h3>
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
                <p class="text-xs text-slate-500">No. Jaminan</p>
                <p class="font-mono font-medium">{{ selectedItem.id }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Status</p>
                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(selectedItem.status)">
                  {{ selectedItem.status }}
                </span>
              </div>
              <div>
                <p class="text-xs text-slate-500">Nama</p>
                <p class="font-medium">{{ selectedItem.nama }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Jenis Cagaran</p>
                <p>{{ selectedItem.jenis }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Nilai</p>
                <p class="font-medium">{{ fmtRm(selectedItem.nilai) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Kategori Risiko</p>
                <p>{{ selectedItem.risiko }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">No. Pinjaman</p>
                <p class="font-mono">{{ selectedItem.noPinjaman }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Tempoh</p>
                <p>{{ selectedItem.tarikhMula }} – {{ selectedItem.tarikhTamat }}</p>
              </div>
              <div v-if="selectedItem.deskripsi" class="col-span-2">
                <p class="text-xs text-slate-500">Deskripsi</p>
                <p>{{ selectedItem.deskripsi }}</p>
              </div>
              <div v-if="selectedItem.dokumen?.length" class="col-span-2">
                <p class="text-xs text-slate-500">Dokumen</p>
                <ul class="mt-1 space-y-1 text-slate-600">
                  <li v-for="d in selectedItem.dokumen" :key="d.nama">
                    {{ d.nama }} – {{ d.jenis }} ({{ d.tarikh }})
                  </li>
                </ul>
              </div>
            </div>

            <!-- Fasa 4: Surat Lanjutan Projek (SPEC 11.1.8) -->
            <div class="border-t border-slate-100 pt-4">
              <button
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="cetakSuratLanjutan(selectedItem)"
              >
                <FileSignature class="h-4 w-4" />
                Surat Lanjutan Projek
              </button>
            </div>

            <!-- Fasa 4: Jejak Audit (SPEC 11.1.6) -->
            <div class="border-t border-slate-100 pt-4">
              <h4 class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <History class="h-4 w-4" />
                Jejak Audit
              </h4>
              <ul v-if="auditLogsFor(selectedItem).length" class="space-y-2 text-sm">
                <li
                  v-for="log in auditLogsFor(selectedItem)"
                  :key="log.id"
                  class="rounded border border-slate-100 bg-slate-50 px-3 py-2"
                >
                  <span class="font-medium">{{ log.action }}</span>
                  <span v-if="log.detail" class="text-slate-600"> – {{ log.detail }}</span>
                  <div class="mt-1 text-xs text-slate-500">{{ log.user }} – {{ log.timestamp }}</div>
                </li>
              </ul>
              <p v-else class="text-sm text-slate-500">{{ tp("Tiada rekod audit.") }}</p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
