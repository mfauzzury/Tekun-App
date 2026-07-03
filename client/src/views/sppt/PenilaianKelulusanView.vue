<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import { Check, Eye, X } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");

const summary = [
  { label: "Menunggu Penilaian", value: 15 },
  { label: "Dalam Penilaian", value: 6 },
  { label: "Diluluskan Bulan Ini", value: 22 },
  { label: "Ditolak Bulan Ini", value: 3 },
];

const items = [
  { id: "P-2024-001", nama: "Ahmad bin Abdullah", jumlah: "RM 50,000", tarikh: "10 Mac 2024", status: "Menunggu" },
  { id: "P-2024-002", nama: "Siti Nurhaliza binti Omar", jumlah: "RM 30,000", tarikh: "9 Mac 2024", status: "Dalam Penilaian" },
  { id: "P-2024-003", nama: "Mohd Rizal bin Hassan", jumlah: "RM 75,000", tarikh: "8 Mac 2024", status: "Menunggu" },
  { id: "P-2024-004", nama: "Fatimah binti Ibrahim", jumlah: "RM 25,000", tarikh: "7 Mac 2024", status: "Diluluskan" },
];

function statusClass(s: string) {
  if (s === "Menunggu") return "bg-slate-100 text-slate-600";
  if (s === "Dalam Penilaian") return "bg-amber-100 text-amber-700";
  if (s === "Diluluskan") return "bg-emerald-100 text-emerald-700";
  if (s === "Ditolak") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Penilaian & Kelulusan"
        :breadcrumb="[{ label: 'Pembiayaan', to: '/admin/pembiayaan/penilaian' }, { label: 'Penilaian & Kelulusan' }]"
        action-label="Penilaian Baru"
        action-to="/admin/pembiayaan/penilaian/baru"
      />

      <SpptSummaryCards :items="summary" />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Penilaian</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            :search-placeholder="t('sppt.searchApplicationPlaceholder')"
            :filter-options="[
              { value: 'menunggu', label: 'Menunggu' },
              { value: 'penilaian', label: 'Dalam Penilaian' },
              { value: 'lulus', label: 'Diluluskan' },
              { value: 'tolak', label: 'Ditolak' },
            ]"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.applicationNo") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.amount") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.jumlah }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikh }}</td>
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
                    <button v-if="item.status === 'Menunggu' || item.status === 'Dalam Penilaian'" class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-emerald-600 transition-colors hover:bg-emerald-50">
                      <Check class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Lulus</span>
                    </button>
                    <button v-if="item.status === 'Menunggu' || item.status === 'Dalam Penilaian'" class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-rose-600 transition-colors hover:bg-rose-50">
                      <X class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Tolak</span>
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
