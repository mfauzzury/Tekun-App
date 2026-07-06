<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import { CheckCircle, FileCheck } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import { fetchSpptDataset } from "@/api/sppt";
import { AKAUN_SELESAI_BAYAR } from "@/data/bayaran-pembiayaan-dummy";

const { t, tp } = useI18n();

const q = ref("");
const statusFilter = ref("");
const items = ref<(typeof AKAUN_SELESAI_BAYAR)[number][]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("pembayaran", "akaun_selesai_bayar");
  items.value = res.data as typeof items.value;
});

const filteredItems = computed(() => {
  let list = items.value;
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((r) => r.nama.toLowerCase().includes(lower) || r.noAkaun.toLowerCase().includes(lower));
  }
  if (statusFilter.value === "proses") list = list.filter((r) => r.statusNOD.includes("proses"));
  if (statusFilter.value === "dihantar") list = list.filter((r) => r.statusNOD === "Dihantar");
  return list;
});

function statusClass(s: string) {
  if (s === "Dihantar") return "bg-emerald-100 text-emerald-700";
  if (s === "Dalam proses") return "bg-amber-100 text-amber-700";
  return "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pengurusan Akaun Selesai Bayar"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Akaun Selesai Bayar' }]"
      />

      <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2">
            <CheckCircle class="h-5 w-5 text-emerald-600" />
            <span class="text-sm font-medium text-slate-700">Akaun Selesai Bayar</span>
          </div>
          <p class="mt-1 text-2xl font-bold text-slate-900">3</p>
          <p class="text-xs text-slate-500">Bulan ini</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2">
            <FileCheck class="h-5 w-5 text-blue-600" />
            <span class="text-sm font-medium text-slate-700">NOD dalam Proses</span>
          </div>
          <p class="mt-1 text-2xl font-bold text-slate-900">2</p>
          <p class="text-xs text-slate-500">Makluman serahan NOD</p>
        </div>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Dashboard Cawangan – Akaun Selesai Bayar</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="statusFilter"
            search-placeholder="Cari nama atau no. akaun..."
            :filter-options="[
              { value: 'proses', label: 'Dalam proses' },
              { value: 'dihantar', label: 'Dihantar' },
            ]"
            filter-label="Status NOD"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Selesai</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status NOD</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Cawangan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in filteredItems" :key="item.noAkaun" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noAkaun }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhSelesai }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.statusNOD)">
                    {{ item.statusNOD }}
                  </span>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.cawangan }}</td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
