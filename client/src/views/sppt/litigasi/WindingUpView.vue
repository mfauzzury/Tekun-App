<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { WINDING_UP_ITEMS } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const items = ref([...WINDING_UP_ITEMS]);

function statusClass(s: string) {
  const m: Record<string, string> = { petisyen: "bg-amber-100 text-amber-700", perintah: "bg-blue-100 text-blue-700", "block-payment": "bg-rose-100 text-rose-700", liquidator: "bg-purple-100 text-purple-700" };
  return m[s] ?? "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Winding-Up"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Winding-Up' }]"
        action-label="Petisyen Winding-Up"
        action-to="/admin/undang-undang/winding-up/petisyen"
      />

      <LitigasiSubNav />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Winding-Up Proceeding (7.14)</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.companyName") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes Winding-Up</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Petisyen</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noKes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.namaSyarikat }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noKesWindingUp }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhPetisyen }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">{{ item.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Winding-Up (7.14.1)</h3>
        <p class="text-sm text-slate-600">Setiap perlaksanaan mempunyai prosiding tersendiri. Pemilihan mengikut nasihat peguam. Tindakan block payment dapat dilaksanakan.</p>
      </article>
    </div>
  </AdminLayout>
</template>
