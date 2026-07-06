<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { fetchSpptDataset } from "@/api/sppt";
import type { JdsItem } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const items = ref<JdsItem[]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("litigasi", "jds_items");
  items.value = res.data as JdsItem[];
});

function statusClass(s: string) {
  const m: Record<string, string> = { dijadual: "bg-amber-100 text-amber-700", hadir: "bg-blue-100 text-blue-700", "tidak-hadir": "bg-rose-100 text-rose-700", tangguh: "bg-slate-100 text-slate-600", selesai: "bg-emerald-100 text-emerald-700" };
  return m[s] ?? "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Judgment Debtor Summon (JDS)"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'JDS' }]"
        action-label="Permohonan JDS"
        action-to="/admin/undang-undang/jds/permohonan"
      />

      <LitigasiSubNav />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Pengurusan JDS (7.12)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh JDS</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Keputusan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noKes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhJds }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">{{ item.status }}</span>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.keputusan ?? "—" }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">JDS (7.12.1)</h3>
        <p class="text-sm text-slate-600">Rekod keputusan JDS. Prosedur permohonan JDS. Tagging owing dapat dilaksanakan. Indicator peminjam JDS berfungsi.</p>
      </article>
    </div>
  </AdminLayout>
</template>
