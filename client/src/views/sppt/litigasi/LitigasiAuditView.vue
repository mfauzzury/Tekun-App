<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { LITIGASI_AUDIT } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const q = ref("");

const items = computed(() => {
  if (!q.value) return [...LITIGASI_AUDIT];
  const lower = q.value.toLowerCase();
  return LITIGASI_AUDIT.filter((x) => x.pegawai.toLowerCase().includes(lower) || x.kes.toLowerCase().includes(lower) || x.tindakan.toLowerCase().includes(lower));
});
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Log Audit Litigasi"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Audit' }]"
      />

      <LitigasiSubNav />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Log Audit dan Dokumentasi (7.15)</h2>
          <SpptFilterBar v-model:q="q" search-placeholder="Cari pegawai atau kes..." />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.dateTime") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Pegawai</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Firma Guaman</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Dokumen</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2">
                  <div class="font-medium text-slate-900">{{ item.tarikh }}</div>
                  <div class="text-xs text-slate-500">{{ item.masa }}</div>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.pegawai }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tindakan }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.kes }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.firmaGuaman ?? "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.dokumen ?? "—" }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Audit Trail (7.15.1)</h3>
        <p class="text-sm text-slate-600">Rekod tarikh rujukan, pegawai yang meluluskan, firma guaman, dokumen sokongan. Sokong semakan dalaman, pengauditan, pematuhan polisi audit.</p>
      </article>
    </div>
  </AdminLayout>
</template>
