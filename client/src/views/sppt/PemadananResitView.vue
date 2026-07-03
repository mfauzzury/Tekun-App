<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import { Check, Hand, AlertTriangle } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import { PEMADANAN_RESIT } from "@/data/bayaran-pembiayaan-dummy";

const { t, tp } = useI18n();

const q = ref("");
const statusFilter = ref("");

const items = ref([...PEMADANAN_RESIT]);

const filteredItems = computed(() => {
  let list = items.value;
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((r) => r.noAkaun.toLowerCase().includes(lower) || r.kodTransaksi.toLowerCase().includes(lower));
  }
  if (statusFilter.value) {
    list = list.filter((r) => r.status === statusFilter.value);
  }
  return list;
});

function statusClass(s: string) {
  if (s === "auto") return "bg-emerald-100 text-emerald-700";
  if (s === "manual") return "bg-blue-100 text-blue-700";
  if (s === "unverified") return "bg-amber-100 text-amber-700";
  return "bg-slate-100 text-slate-600";
}

function statusLabel(s: string) {
  if (s === "auto") return "Auto";
  if (s === "manual") return "Manual";
  if (s === "unverified") return "Tidak Dikenali";
  return s;
}

function padanManual(id: string) {
  alert(`Pemadanan manual untuk ${id} (dummy)`);
}

function prosesUnmatched(id: string) {
  alert(`Proses bayaran tidak dikenali ${id} (dummy)`);
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pemadanan Resit Bayaran"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Pemadanan Resit' }]"
      />

      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2">
            <Check class="h-5 w-5 text-emerald-600" />
            <span class="text-sm font-medium text-slate-700">Auto (Direct Hit)</span>
          </div>
          <p class="mt-1 text-2xl font-bold text-slate-900">2</p>
          <p class="text-xs text-slate-500">Berdasarkan no akaun, IC, kod transaksi</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2">
            <Hand class="h-5 w-5 text-blue-600" />
            <span class="text-sm font-medium text-slate-700">Manual</span>
          </div>
          <p class="mt-1 text-2xl font-bold text-slate-900">1</p>
          <p class="text-xs text-slate-500">Pemadanan oleh pegawai</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2">
            <AlertTriangle class="h-5 w-5 text-amber-600" />
            <span class="text-sm font-medium text-slate-700">Tidak Dikenali</span>
          </div>
          <p class="mt-1 text-2xl font-bold text-slate-900">1</p>
          <p class="text-xs text-slate-500">Perlu tindakan</p>
        </div>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Pemadanan</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="statusFilter"
            search-placeholder="Cari no. akaun atau kod transaksi..."
            :filter-options="[
              { value: 'auto', label: 'Auto' },
              { value: 'manual', label: 'Manual' },
              { value: 'unverified', label: 'Tidak Dikenali' },
            ]"
            filter-label="Status"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Resit</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. IC</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.amount") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Kod Transaksi</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in filteredItems" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noAkaun }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.ic }}</td>
                <td class="px-4 py-2 text-slate-600">RM {{ item.jumlah.toLocaleString() }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikh }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.kodTransaksi }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <button
                    v-if="item.status === 'unverified'"
                    class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-200"
                    @click="prosesUnmatched(item.id)"
                  >
                    Proses
                  </button>
                  <button
                    v-else-if="item.status === 'manual'"
                    class="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-200"
                    @click="padanManual(item.id)"
                  >
                    Padan Manual
                  </button>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td colspan="8" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
