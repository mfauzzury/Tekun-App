<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { fetchSpptDataset } from "@/api/sppt";
import type { GarnisheeItem } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const items = ref<GarnisheeItem[]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("litigasi", "garnishee_items");
  items.value = res.data as GarnisheeItem[];
});

function statusClass(s: string) {
  const m: Record<string, string> = { permohonan: "bg-amber-100 text-amber-700", diluluskan: "bg-blue-100 text-blue-700", dikredit: "bg-emerald-100 text-emerald-700", ditolak: "bg-rose-100 text-rose-700" };
  return m[s] ?? "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Garnishee"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Garnishee' }]"
        action-label="Permohonan Garnishee"
        action-to="/admin/undang-undang/garnishee/permohonan"
      />

      <LitigasiSubNav />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Pengurusan Garnishee (7.11)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Institusi</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.amount") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noKes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.institusi }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.jumlah }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">{{ item.status }}</span>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikh }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Garnishee (7.11.1)</h3>
        <p class="text-sm text-slate-600">Rekod keputusan Garnishee. Tagging owing dapat dilaksanakan ke atas akaun. Indicator peminjam Garnishee berfungsi.</p>
      </article>
    </div>
  </AdminLayout>
</template>
