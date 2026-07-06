<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { fetchSpptDataset } from "@/api/sppt";
import type { ExecutionItem } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const items = ref<ExecutionItem[]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("litigasi", "execution_items");
  items.value = res.data as ExecutionItem[];
});

function statusClass(s: string) {
  const m: Record<string, string> = { draf: "bg-slate-100 text-slate-600", permohonan: "bg-amber-100 text-amber-700", "notis-hantar": "bg-blue-100 text-blue-700", penyitaan: "bg-orange-100 text-orange-700", lelongan: "bg-purple-100 text-purple-700", selesai: "bg-emerald-100 text-emerald-700" };
  return m[s] ?? "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pelaksanaan Penghakiman"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Execution' }]"
        action-label="Permohonan Execution"
        action-to="/admin/undang-undang/execution/permohonan"
      />

      <LitigasiSubNav />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Pelaksanaan (7.9)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jumlah Diputuskan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Keputusan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis Penghakiman</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noKes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.jumlahDiputuskan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhKeputusan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.jenisPenghakiman }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">{{ item.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Execution (7.9.1)</h3>
        <p class="text-sm text-slate-600">Jika tiada bayaran/part payment. Sistem simpan: jenis penghakiman, jumlah diputuskan, tarikh, arahan mahkamah. Panel sediakan dokumen permohonan, notis pelaksanaan.</p>
      </article>
    </div>
  </AdminLayout>
</template>
