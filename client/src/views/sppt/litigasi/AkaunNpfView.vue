<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { listKlasifikasiAkaun } from "@/api/sppt";
import type { AkaunPembiayaan } from "@/types";

const { t } = useI18n();

const rawItems = ref<AkaunPembiayaan[]>([]);
const loading = ref(true);

onMounted(async () => {
  loading.value = true;
  try {
    const res = await listKlasifikasiAkaun({ klasifikasi: "tidak_lancar", limit: 100 });
    rawItems.value = res.data;
  } finally {
    loading.value = false;
  }
});

const q = ref("");

const items = computed(() => {
  let list = [...rawItems.value];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((x) => String(x.id).toLowerCase().includes(lower) || x.nama.toLowerCase().includes(lower));
  }
  return list;
});

function fmtRm(n: number) {
  return `RM ${n.toLocaleString("ms-MY", { minimumFractionDigits: 2 })}`;
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
          <h2 class="text-sm font-semibold text-slate-900">Pengenalpastian Akaun NPF — Klasifikasi Tidak Lancar (NPL)</h2>
          <SpptFilterBar v-model:q="q" search-placeholder="Cari no. akaun atau nama..." />
        </div>
        <div v-if="loading" class="p-6 text-center text-sm text-slate-400">Memuatkan...</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Cawangan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Hari Tunggakan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.amount") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Klasifikasi</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="String(item.id)" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.cawangan }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.hariLewat ?? 0 }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ fmtRm(item.tunggakan) }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-700">
                    {{ item.klasifikasiNplLabel ?? "Tidak Lancar (NPL)" }}
                  </span>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.status }}</td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">Tiada akaun NPL pada masa ini.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
