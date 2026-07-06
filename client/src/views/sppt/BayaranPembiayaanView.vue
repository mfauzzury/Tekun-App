<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import { Eye } from "lucide-vue-next";
import { RouterLink } from "vue-router";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import { fetchSpptDataset } from "@/api/sppt";
import { BAYARAN_ITEMS } from "@/data/bayaran-pembiayaan-dummy";

const { t, tp } = useI18n();

const rawItems = ref<(typeof BAYARAN_ITEMS)[number][]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("pembayaran", "bayaran_items");
  rawItems.value = res.data as typeof rawItems.value;
});

const q = ref("");
const status = ref("");

const summary = [
  { label: "Bayaran Bulan Ini", value: "RM 85K" },
  { label: "Jumlah Transaksi", value: 67 },
  { label: "Bayaran Hari Ini", value: "RM 12K" },
  { label: "Resit Dijana", value: 45 },
];

const items = computed(() => {
  let list = rawItems.value;
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((r) => r.id.toLowerCase().includes(lower) || r.nama.toLowerCase().includes(lower));
  }
  if (status.value) {
    list = list.filter((r) => r.kaedah.toLowerCase().includes(status.value));
  }
  return list;
});

const modules = [
  { label: "Terima Bayaran", to: "/admin/pembayaran/terima", desc: "FPX, e-Wallet, Auto Debit, Kaunter" },
  { label: "Pemadanan Resit", to: "/admin/pembayaran/pemadanan", desc: "Auto & manual matching" },
  { label: "Rekonsiliasi Bank", to: "/admin/pembayaran/rekon", desc: "Pemadanan penyata bank" },
  { label: "Lebihan & Kekurangan", to: "/admin/pembayaran/lebihan-kekurangan", desc: "Overpayment / underpayment" },
  { label: "Penyata Bayaran", to: "/admin/pembayaran/penyata", desc: "Jana & muat turun penyata" },
  { label: "Early Settlement", to: "/admin/pembayaran/early-settlement", desc: "Penyelesaian awal" },
  { label: "Akaun Selesai Bayar", to: "/admin/pembayaran/selesai-bayar", desc: "NOD & dashboard" },
  { label: "AI Bayaran", to: "/admin/pembayaran/ai", desc: "OCR, analitik, chatbot" },
];
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Bayaran Pembiayaan"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Bayaran Pembiayaan' }]"
        action-label="Terima Bayaran"
        action-to="/admin/pembayaran/terima"
      />

      <SpptSummaryCards :items="summary" />

      <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Modul Bayaran</h2>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          <RouterLink
            v-for="m in modules"
            :key="m.to"
            :to="m.to"
            class="rounded-lg border border-slate-200 p-3 transition-colors hover:border-slate-300 hover:bg-slate-50"
          >
            <p class="font-medium text-slate-900">{{ m.label }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ m.desc }}</p>
          </RouterLink>
        </div>
      </section>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Bayaran</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            search-placeholder="Cari no. bayaran atau nama..."
            :filter-options="[
              { value: 'fpx', label: 'FPX' },
              { value: 'tunai', label: 'Tunai' },
              { value: 'cek', label: 'Cek' },
              { value: 'tng', label: 'e-Wallet' },
              { value: 'auto', label: 'Auto Debit' },
            ]"
            filter-label="Kaedah Bayaran"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Bayaran</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.amount") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Kaedah</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">RM {{ item.jumlah.toLocaleString() }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikh }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ item.kaedah }}</span>
                </td>
                <td class="px-4 py-2 text-right">
                  <button class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <Eye class="h-3.5 w-3.5" />
                    <span class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">{{ t("common.view") }}</span>
                  </button>
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
