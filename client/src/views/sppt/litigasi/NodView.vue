<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { NOD_ITEMS } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");
const activeTab = ref<"dalaman" | "panel">("dalaman");

const items = computed(() => {
  let list = [...NOD_ITEMS];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((x) => x.noRujukan.toLowerCase().includes(lower) || x.nama.toLowerCase().includes(lower));
  }
  if (status.value) list = list.filter((x) => x.status === status.value);
  return list;
});

function statusClass(s: string) {
  const m: Record<string, string> = { draf: "bg-slate-100 text-slate-600", "menunggu-bsk": "bg-amber-100 text-amber-700", dihantar: "bg-emerald-100 text-emerald-700", return: "bg-rose-100 text-rose-700", selesai: "bg-emerald-100 text-emerald-700" };
  return m[s] ?? "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="NOD"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'NOD' }]"
        action-label="Mohon NOD Baru"
        action-to="/admin/undang-undang/nod/mohon"
      />

      <LitigasiSubNav />

      <div class="flex gap-2 border-b border-slate-200">
        <button class="border-b-2 px-4 py-2 text-sm font-medium" :class="activeTab === 'dalaman' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500'" @click="activeTab = 'dalaman'">NOD Dalaman (7.4)</button>
        <button class="border-b-2 px-4 py-2 text-sm font-medium" :class="activeTab === 'panel' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500'" @click="activeTab = 'panel'">NOD Panel Peguam (7.5)</button>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">{{ t("sppt.nodList") }}</h2>
          <SpptFilterBar v-model:q="q" v-model:status="status" search-placeholder="Cari no. rujukan..." :filter-options="[{ value: 'draf', label: 'Draf' }, { value: 'menunggu-bsk', label: 'Menunggu BSK' }, { value: 'dihantar', label: 'Dihantar' }]" />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Rujukan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Panel Peguam</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.sendDate") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Semakan Fizikal</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noRujukan }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.jenis }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.panelPeguam ?? "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhHantar ?? "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.semakanFizikal ?? "—" }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">{{ item.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Workflow NOD (7.4.1, 7.5.2)</h3>
        <p class="text-sm text-slate-600">Cawangan mohon NOD → BSK dapat senarai → Notify JKI (Kontrak-i) → Pengesahan BSK → Surat arahan auto ke panel → Panel terima notifikasi emel → Upload salinan NOD batch.</p>
      </article>
    </div>
  </AdminLayout>
</template>
