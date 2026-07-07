<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { computed, onMounted, ref } from "vue";
import { Eye, Pencil, Trash2 } from "lucide-vue-next";

import { deletePermohonan, listPermohonan } from "@/api/sppt";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useSpptStatus } from "@/composables/useSpptStatus";
import { useToast } from "@/composables/useToast";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import type { Permohonan } from "@/types";

const { t, tp } = useI18n();
const { statusLabel, statusClass } = useSpptStatus();
const confirmDialog = useConfirmDialog();
const toast = useToast();

const q = ref("");
const status = ref("");
const loading = ref(true);
const items = ref<Permohonan[]>([]);
const summaryData = ref({
  jumlah: 0,
  draf: 0,
  dalamProses: 0,
  menungguDokumen: 0,
  selesaiBulanIni: 0,
});

const statusMap: Record<string, string> = {
  draf: "Draf",
  proses: "Dalam Proses",
  dokumen: "Menunggu Dokumen",
  lengkap: "Lengkap",
};

const summary = computed(() => [
  { label: t("sppt.totalApplications"), value: loading.value ? "—" : summaryData.value.jumlah },
  { label: t("sppt.status.draf"), value: loading.value ? "—" : summaryData.value.draf },
  { label: t("sppt.inProgress"), value: loading.value ? "—" : summaryData.value.dalamProses },
  { label: t("sppt.awaitingDocuments"), value: loading.value ? "—" : summaryData.value.menungguDokumen },
]);

async function loadItems() {
  loading.value = true;
  try {
    const res = await listPermohonan({
      limit: 100,
      include_summary: 1,
      q: q.value.trim() || undefined,
      status: statusMap[status.value] ?? undefined,
    });
    items.value = res.data;
    const summary = res.meta?.summary as Record<string, number> | undefined;
    if (summary) {
      summaryData.value = {
        jumlah: summary.jumlah ?? 0,
        draf: summary.draf ?? 0,
        dalamProses: summary.dalamProses ?? 0,
        menungguDokumen: summary.menungguDokumen ?? 0,
        selesaiBulanIni: summary.selesaiBulanIni ?? 0,
      };
    }
  } catch {
    items.value = [];
  } finally {
    loading.value = false;
  }
}

async function reload() {
  await loadItems();
}

onMounted(reload);

function fmtRm(amount?: number | null) {
  if (amount == null || amount <= 0) return "—";
  return `RM ${amount.toLocaleString("en-MY", { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
}

function fmtDate(value?: string | null) {
  if (!value) return "—";
  return new Date(value).toLocaleDateString("ms-MY", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

function displayNo(item: Permohonan) {
  return item.noRujukan || `PM-${item.id}`;
}

function editTo(item: Permohonan) {
  return item.status === "Draf" ? `/admin/permohonan/baru/${item.id}` : `/admin/permohonan/${item.id}`;
}

function viewTo(item: Permohonan) {
  return item.status === "Draf" ? `/admin/permohonan/baru/${item.id}` : `/admin/permohonan/${item.id}`;
}

async function remove(item: Permohonan) {
  const allowed = await confirmDialog.confirm({
    title: tp("Padam permohonan draf?"),
    message: tp(`Permohonan ${displayNo(item)} akan dipadam secara kekal.`),
    confirmText: t("common.delete"),
    destructive: true,
  });
  if (!allowed) return;

  try {
    await deletePermohonan(item.id);
    await reload();
    toast.success(tp("Permohonan draf dipadam."));
  } catch (e) {
    toast.error(tp("Gagal memadam permohonan."), e instanceof Error ? e.message : undefined);
  }
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pendaftaran Permohonan"
        :breadcrumb="[{ label: 'Permohonan', to: '/admin/permohonan' }, { label: 'Pendaftaran Permohonan' }]"
        action-label="Daftar Permohonan Baru"
        action-to="/admin/permohonan/baru"
      />

      <SpptSummaryCards :items="summary" />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">{{ t("sppt.applicationList") }}</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            :search-placeholder="t('sppt.searchApplicationPlaceholder')"
            :filter-options="[
              { value: 'draf', label: t('sppt.status.draf') },
              { value: 'proses', label: t('sppt.inProgress') },
              { value: 'dokumen', label: t('sppt.awaitingDocuments') },
              { value: 'lengkap', label: t('sppt.complete') },
            ]"
            @filter="reload"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("sppt.applicationNo") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Negeri</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Cawangan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.amount") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="loading">
                <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Memuatkan...") }}</td>
              </tr>
              <tr v-for="item in items" v-else :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ displayNo(item) }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama || "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.negeri || "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.cawangan || "—" }}</td>
                <td class="px-4 py-2 text-slate-600">{{ fmtRm(item.jumlahPermohonan) }}</td>
                <td class="px-4 py-2 text-slate-600">{{ fmtDate(item.tarikhPermohonan ?? item.createdAt) }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <router-link
                      :to="viewTo(item)"
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                    >
                      <Eye class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.view") }}</span>
                    </router-link>
                    <router-link
                      v-if="item.status === 'Draf'"
                      :to="editTo(item)"
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.edit") }}</span>
                    </router-link>
                    <button
                      v-if="item.status === 'Draf'"
                      type="button"
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                      @click="remove(item)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                      <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.delete") }}</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && items.length === 0">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
