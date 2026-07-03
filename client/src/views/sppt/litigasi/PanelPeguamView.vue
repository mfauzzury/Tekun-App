<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { PANEL_PEGUAM } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");

const items = computed(() => {
  let list = [...PANEL_PEGUAM];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((x) => x.namaFirma.toLowerCase().includes(lower) || x.pegawaiUtama.toLowerCase().includes(lower));
  }
  if (status.value) list = list.filter((x) => x.status === status.value);
  return list;
});

function statusClass(s: string) {
  if (s === "aktif") return "bg-emerald-100 text-emerald-700";
  if (s === "dalam-semakan") return "bg-amber-100 text-amber-700";
  return "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Panel Peguam"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Panel Peguam' }]"
        action-label="Pelantikan Panel Baru"
        action-to="/admin/undang-undang/panel-peguam/baru"
      />

      <LitigasiSubNav />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Direktori Panel Peguam (7.1)</h2>
          <SpptFilterBar v-model:q="q" v-model:status="status" search-placeholder="Cari firma..." :filter-options="[{ value: 'aktif', label: 'Aktif' }, { value: 'dalam-semakan', label: 'Dalam Semakan' }]" />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Firma</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Pegawai Utama</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Negeri</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Kuota NOD</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Kes Aktif</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2">
                  <div class="font-medium text-slate-900">{{ item.namaFirma }}</div>
                  <div class="text-xs text-slate-500">{{ item.noPendaftaran }}</div>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.pegawaiUtama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.negeri }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.kuotaNod }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.kesAktif }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">{{ item.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Workflow Pelantikan (7.1.1)</h3>
        <p class="text-sm text-slate-600">BSK semak keperluan → JUU sahkan → Makluman tawaran ke panel → Surat lantikan/tolak → Notifikasi emel → Pengesahan penyelia.</p>
      </article>
    </div>
  </AdminLayout>
</template>
