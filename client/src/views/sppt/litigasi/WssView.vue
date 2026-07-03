<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { WSS_ITEMS } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const items = ref([...WSS_ITEMS]);

function statusClass(s: string) {
  const m: Record<string, string> = { penyitaan: "bg-amber-100 text-amber-700", "lelongan-dijadual": "bg-blue-100 text-blue-700", "lelongan-selesai": "bg-purple-100 text-purple-700", "hasil-dikredit": "bg-emerald-100 text-emerald-700" };
  return m[s] ?? "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Writ of Seizure and Sale (WSS)"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'WSS' }]"
        action-label="Permohonan WSS"
        action-to="/admin/undang-undang/wss/permohonan"
      />

      <LitigasiSubNav />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Pengurusan Penyitaan & Penjualan (7.10)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis Aset</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Lokasi</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Nilai Semasa</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Penyitaan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noKes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.jenisAset }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.lokasi }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nilaiSemasa }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhPenyitaan }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">{{ item.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">WSS (7.10.1–7.10.3)</h3>
        <p class="text-sm text-slate-600">Pengurusan penyitaan aset: jenis, lokasi, nilai, tarikh. Pengurusan lelongan: jadual, lokasi, nilai rizab, pemenang. Kemaskini status & pelaporan.</p>
      </article>
    </div>
  </AdminLayout>
</template>
