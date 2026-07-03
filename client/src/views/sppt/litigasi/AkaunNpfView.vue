<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { AKAUN_NPF } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");

const items = computed(() => {
  let list = [...AKAUN_NPF];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((x) => x.noAkaun.toLowerCase().includes(lower) || x.nama.toLowerCase().includes(lower));
  }
  if (status.value) list = list.filter((x) => x.status === status.value);
  return list;
});

function statusNodLabel(s: string) {
  const m: Record<string, string> = { belum: "Belum NOD", "dalaman-hantar": "NOD Dalaman Dihantar", "panel-hantar": "NOD Panel Dihantar", selesai: "Selesai" };
  return m[s] ?? s;
}

function statusClass(s: string) {
  if (s === "disahkan") return "bg-emerald-100 text-emerald-700";
  if (s === "menunggu") return "bg-amber-100 text-amber-700";
  return "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Akaun NPF"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Akaun NPF' }]"
        action-label="Submit Senarai NPF"
        action-to="/admin/undang-undang/akaun-npf/submit"
      />

      <LitigasiSubNav />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Pengenalpastian Akaun NPF (>90 hari tunggakan) (7.2)</h2>
          <SpptFilterBar v-model:q="q" v-model:status="status" search-placeholder="Cari no. akaun atau nama..." :filter-options="[{ value: 'disahkan', label: 'Disahkan' }, { value: 'menunggu', label: 'Menunggu' }]" />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Cawangan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Hari Tunggakan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.amount") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status NOD</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Pengesahan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noAkaun }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.cawangan }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.hariTunggakan }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.jumlahTunggakan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ statusNodLabel(item.statusNod) }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">{{ item.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Pengesahan Dalaman (7.2.2)</h3>
        <p class="text-sm text-slate-600">Semak penggiliran NOD (~1000 per panel). Pengesahan BSK/Jawatankuasa. Integrasi Insolvensi untuk auto tagging.</p>
      </article>
    </div>
  </AdminLayout>
</template>
