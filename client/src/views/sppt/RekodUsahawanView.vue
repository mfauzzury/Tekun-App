<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import { Eye, Pencil } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");

const summary = [
  { label: "Jumlah Usahawan", value: 156 },
  { label: "Aktif", value: 142 },
  { label: "Pembiayaan Berjalan", value: 89 },
  { label: "Daftar Bulan Ini", value: 12 },
];

const items = [
  { id: "U-001", nama: "Ahmad bin Abdullah", noIc: "850101-01-1234", negeri: "Selangor", status: "Aktif" },
  { id: "U-002", nama: "Siti Nurhaliza binti Omar", noIc: "920315-14-5678", negeri: "Johor", status: "Aktif" },
  { id: "U-003", nama: "Mohd Rizal bin Hassan", noIc: "880722-08-9012", negeri: "Kedah", status: "Aktif" },
  { id: "U-004", nama: "Fatimah binti Ibrahim", noIc: "950410-10-3456", negeri: "Perak", status: "Tidak Aktif" },
];

function statusClass(s: string) {
  return s === "Aktif" ? "bg-emerald-100 text-emerald-700" : "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Rekod Usahawan"
        :breadcrumb="[{ label: 'Permohonan', to: '/admin/permohonan' }, { label: 'Rekod Usahawan' }]"
        action-label="Daftar Usahawan Baru"
        action-to="/admin/permohonan/usahawan/daftar"
      />

      <SpptSummaryCards :items="summary" />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Usahawan</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            search-placeholder="Cari nama atau no. IC..."
            :filter-options="[
              { value: 'aktif', label: 'Aktif' },
              { value: 'tidak-aktif', label: 'Tidak Aktif' },
            ]"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Usahawan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. IC</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Negeri</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.noIc }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.negeri }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ item.status }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                      <Eye class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.view") }}</span>
                    </button>
                    <button class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                      <Pencil class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.edit") }}</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
