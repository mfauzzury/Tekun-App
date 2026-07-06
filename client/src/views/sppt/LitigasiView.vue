<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import { Eye, FileText } from "lucide-vue-next";
import { RouterLink } from "vue-router";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { fetchSpptDataset } from "@/api/sppt";
import { type KesLitigasi, type StatusKes } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const kesLitigasi = ref<KesLitigasi[]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("litigasi", "kes_litigasi");
  kesLitigasi.value = res.data as KesLitigasi[];
});

const q = ref("");
const status = ref("");

const summary = [
  { label: "Kes Aktif", value: 8 },
  { label: "Dalam Perbicaraan", value: 3 },
  { label: "Selesai Tahun Ini", value: 5 },
  { label: "Kes Baru Bulan Ini", value: 1 },
];

const items = computed(() => {
  let list = [...kesLitigasi.value];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((x) => x.noKes.toLowerCase().includes(lower) || x.nama.toLowerCase().includes(lower));
  }
  if (status.value) {
    list = list.filter((x) => x.status === status.value);
  }
  return list;
});

function statusLabel(s: StatusKes): string {
  const map: Record<StatusKes, string> = {
    "fail-dibuka": "Fail Dibuka",
    "nod-dihantar": "NOD Dihantar",
    "saman-dihantar": "Saman Dihantar",
    pendengaran: "Pendengaran",
    perbicaraan: "Dalam Perbicaraan",
    penghakiman: "Penghakiman",
    execution: "Execution",
    selesai: "Selesai",
    henti: "Henti",
  };
  return map[s] ?? s;
}

function statusClass(s: StatusKes) {
  const map: Record<string, string> = {
    "fail-dibuka": "bg-slate-100 text-slate-600",
    "nod-dihantar": "bg-amber-100 text-amber-700",
    "saman-dihantar": "bg-orange-100 text-orange-700",
    pendengaran: "bg-amber-100 text-amber-700",
    perbicaraan: "bg-blue-100 text-blue-700",
    penghakiman: "bg-purple-100 text-purple-700",
    execution: "bg-rose-100 text-rose-700",
    selesai: "bg-emerald-100 text-emerald-700",
    henti: "bg-slate-100 text-slate-500",
  };
  return map[s] ?? "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Litigasi"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Litigasi' }]"
        action-label="Rekod Kes Baru"
        action-to="/admin/undang-undang/litigasi/rekod"
      />

      <LitigasiSubNav />

      <SpptSummaryCards :items="summary" />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Kes Litigasi</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            search-placeholder="Cari no. kes atau nama..."
            :filter-options="[
              { value: 'fail-dibuka', label: 'Fail Dibuka' },
              { value: 'nod-dihantar', label: 'NOD Dihantar' },
              { value: 'saman-dihantar', label: 'Saman Dihantar' },
              { value: 'pendengaran', label: 'Pendengaran' },
              { value: 'perbicaraan', label: 'Dalam Perbicaraan' },
              { value: 'selesai', label: 'Selesai' },
            ]"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Nama / Syarikat</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Cawangan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Buka</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noKes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.cawangan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhBuka }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <RouterLink
                      :to="`/admin/undang-undang/kes/${item.id}`"
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                    >
                      <Eye class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.view") }}</span>
                    </RouterLink>
                    <button class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                      <FileText class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Dokumen</span>
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
