<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import { Eye, Phone } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");

const summary = [
  { label: "Jumlah Tunggakan", value: "RM 125K" },
  { label: "Akaun Tertunggak", value: 18 },
  { label: "1–30 Hari", value: 12 },
  { label: "> 90 Hari", value: 3 },
];

const items = [
  { id: "A-001", nama: "Ahmad bin Abdullah", tunggakan: "RM 7,500", tempoh: "45 hari", status: "Sederhana" },
  { id: "A-002", nama: "Siti Nurhaliza binti Omar", tunggakan: "RM 4,500", tempoh: "15 hari", status: "Rendah" },
  { id: "A-003", nama: "Mohd Rizal bin Hassan", tunggakan: "RM 15,000", tempoh: "95 hari", status: "Tinggi" },
  { id: "A-004", nama: "Fatimah binti Ibrahim", tunggakan: "RM 2,500", tempoh: "8 hari", status: "Rendah" },
];

function statusClass(s: string) {
  if (s === "Rendah") return "bg-emerald-100 text-emerald-700";
  if (s === "Sederhana") return "bg-amber-100 text-amber-700";
  if (s === "Tinggi") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pemantauan Tunggakan"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Pemantauan Tunggakan' }]"
      />

      <SpptSummaryCards :items="summary" />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Tunggakan</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            search-placeholder="Cari nama atau no. akaun..."
            :filter-options="[
              { value: 'rendah', label: 'Rendah (1-30 hari)' },
              { value: 'sederhana', label: 'Sederhana (31-90 hari)' },
              { value: 'tinggi', label: 'Tinggi (>90 hari)' },
            ]"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tunggakan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tempoh</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tahap</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tunggakan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tempoh }}</td>
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
                      <Phone class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Panggil</span>
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
