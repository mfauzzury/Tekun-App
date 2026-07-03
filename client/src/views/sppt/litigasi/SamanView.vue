<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { KES_LITIGASI } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");

const samanItems = computed(() => {
  let list = KES_LITIGASI.filter((k) => ["saman-dihantar", "pendengaran", "perbicaraan"].includes(k.status));
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((x) => x.noKes.toLowerCase().includes(lower) || x.nama.toLowerCase().includes(lower));
  }
  if (status.value) list = list.filter((x) => x.status === status.value);
  return list;
});

function statusLabel(s: string) {
  const m: Record<string, string> = { "saman-dihantar": "Saman Dihantar", pendengaran: "Pendengaran", perbicaraan: "Dalam Perbicaraan" };
  return m[s] ?? s;
}

function statusClass(s: string) {
  const m: Record<string, string> = { "saman-dihantar": "bg-orange-100 text-orange-700", pendengaran: "bg-amber-100 text-amber-700", perbicaraan: "bg-blue-100 text-blue-700" };
  return m[s] ?? "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Saman Sivil"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Saman' }]"
        action-label="Permohonan Saman"
        action-to="/admin/undang-undang/saman/permohonan"
      />

      <LitigasiSubNav />

      <div class="grid gap-4 sm:grid-cols-3">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Saman Dihantar</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">12</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Menunggu Kelulusan</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">3</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Kes Didaftar Mahkamah</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">8</p>
        </article>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">{{ t("sppt.summonsList") }}</h2>
          <SpptFilterBar v-model:q="q" v-model:status="status" search-placeholder="Cari no. kes..." :filter-options="[{ value: 'saman-dihantar', label: 'Saman Dihantar' }, { value: 'pendengaran', label: 'Pendengaran' }, { value: 'perbicaraan', label: 'Perbicaraan' }]" />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Mahkamah</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Pendengaran</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Panel Peguam</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in samanItems" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noKes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.namaMahkamah ?? "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhPendengaran ?? "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.panelPeguam ?? "—" }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Kelulusan Saman (7.6.3)</h3>
        <p class="text-sm text-slate-600">Pengesahan BSK → Jabatan produk → TKPE. Arahan ke panel → Notifikasi emel. Panel upload salinan, kemaskini maklumat mahkamah, tarikh penting. Peringatan 7 hari sebelum tarikh.</p>
      </article>
    </div>
  </AdminLayout>
</template>
