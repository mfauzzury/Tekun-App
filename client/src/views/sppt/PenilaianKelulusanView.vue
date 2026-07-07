<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { useToast } from "@/composables/useToast";
import { ref, onMounted } from "vue";
import { Check, Eye, FileText, X } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";

import { isApprovedPermohonanStatus, listPermohonan, openPermohonanOfferLetter } from "@/api/sppt";
import { resolvePermohonanDbId } from "@/composables/usePermohonanPenilaian";
import { useSpptStatus } from "@/composables/useSpptStatus";

const { t, tp } = useI18n();
const toast = useToast();
const { statusLabel, statusClass } = useSpptStatus();

const q = ref("");
const status = ref("");

const summary = [
  { label: "Menunggu Penilaian", value: 15 },
  { label: "Dalam Penilaian", value: 6 },
  { label: "Diluluskan Bulan Ini", value: 22 },
  { label: "Ditolak Bulan Ini", value: 3 },
];

const items = ref<{ id: string; permohonanId?: number; _dbId?: number; nama: string; jumlah: string; tarikh: string; status: string }[]>([]);
const downloadingId = ref<number | null>(null);

onMounted(async () => {
  const res = await listPermohonan({ limit: 100, penilaian: 1 });
  items.value = (res.data as Array<Record<string, unknown>>).map((row) => ({
    id: String(row.id ?? row.noRujukan ?? ""),
    permohonanId: typeof row.permohonanId === "number" ? row.permohonanId : undefined,
    _dbId: typeof row._dbId === "number" ? row._dbId : undefined,
    nama: String(row.nama ?? ""),
    jumlah: String(row.jumlah ?? row.jumlahPermohonan ?? "–"),
    tarikh: String(row.tarikh ?? row.tarikhPermohonan ?? "–"),
    status: String(row.status ?? ""),
  }));
});

function itemDbId(item: (typeof items.value)[number]): number | null {
  return resolvePermohonanDbId(item);
}

async function downloadOfferLetter(item: (typeof items.value)[number]) {
  const dbId = itemDbId(item);
  if (!dbId) {
    toast.error("ID permohonan tidak sah.");
    return;
  }

  downloadingId.value = dbId;
  try {
    await openPermohonanOfferLetter(dbId);
  } catch (err) {
    toast.error(err instanceof Error ? err.message : "Gagal menjana surat tawaran.");
  } finally {
    downloadingId.value = null;
  }
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Penilaian & Kelulusan"
        :breadcrumb="[{ label: 'Pembiayaan', to: '/admin/pembiayaan/penilaian' }, { label: 'Penilaian & Kelulusan' }]"
        action-label="Penilaian Baru"
        action-to="/admin/pembiayaan/penilaian/baru"
      />

      <SpptSummaryCards :items="summary" />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Penilaian</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            :search-placeholder="t('sppt.searchApplicationPlaceholder')"
            :filter-options="[
              { value: 'menunggu', label: t('sppt.status.menunggu') },
              { value: 'penilaian', label: t('sppt.status.dalamPenilaian') },
              { value: 'lulus', label: t('sppt.status.diluluskan') },
              { value: 'tolak', label: t('sppt.status.ditolak') },
            ]"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.applicationNo") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.amount") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.jumlah }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikh }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                      <Eye class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.view") }}</span>
                    </button>
                    <button v-if="isApprovedPermohonanStatus(item.status)" class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-blue-600 transition-colors hover:bg-blue-50 disabled:opacity-60" :disabled="downloadingId === itemDbId(item)" @click="downloadOfferLetter(item)">
                      <FileText class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Surat Tawaran PDF</span>
                    </button>
                    <button v-if="item.status === 'Menunggu' || item.status === 'Dalam Penilaian'" class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-emerald-600 transition-colors hover:bg-emerald-50">
                      <Check class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Lulus</span>
                    </button>
                    <button v-if="item.status === 'Menunggu' || item.status === 'Dalam Penilaian'" class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-rose-600 transition-colors hover:bg-rose-50">
                      <X class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Tolak</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
