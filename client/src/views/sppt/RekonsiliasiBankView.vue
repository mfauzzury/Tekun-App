<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import { Upload, FileCheck } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { fetchSpptDataset } from "@/api/sppt";
import { REKON_BANK } from "@/data/bayaran-pembiayaan-dummy";

const { t, tp } = useI18n();

const items = ref<(typeof REKON_BANK)[number][]>([]);
const kekerapan = ref<"harian" | "mingguan" | "bulanan">("harian");
const uploadFile = ref<File | null>(null);

onMounted(async () => {
  const res = await fetchSpptDataset("pembayaran", "rekon_bank");
  items.value = res.data as typeof items.value;
});

function statusClass(s: string) {
  if (s === "padan") return "bg-emerald-100 text-emerald-700";
  if (s === "belum") return "bg-amber-100 text-amber-700";
  return "bg-slate-100 text-slate-600";
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  uploadFile.value = target.files?.[0] ?? null;
}

function uploadPenyata() {
  alert("Muat naik penyata bank (dummy). Tiada sambungan API.");
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Rekonsiliasi Bank"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Rekonsiliasi Bank' }]"
      />

      <div class="grid gap-4 lg:grid-cols-3">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:col-span-2">
          <h3 class="text-sm font-semibold text-slate-900">Muat Naik Penyata Bank</h3>
          <p class="mt-1 text-xs text-slate-500">Pemadanan data resit dengan penyata bank secara automatik dan manual.</p>
          <div class="mt-4 flex flex-wrap items-center gap-3">
            <label class="block">
              <span class="sr-only">Pilih fail</span>
              <input
                type="file"
                accept=".csv,.xlsx,.pdf"
                class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                @change="onFileChange"
              />
            </label>
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
              @click="uploadPenyata"
            >
              <Upload class="h-4 w-4" />
              Muat Naik
            </button>
          </div>
        </article>

        <aside class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-900">Tetapan Kekerapan Recon</h3>
          <div class="mt-3 space-y-2">
            <label class="flex items-center gap-2">
              <input v-model="kekerapan" type="radio" value="harian" class="rounded border-slate-300" />
              <span class="text-sm">Harian</span>
            </label>
            <label class="flex items-center gap-2">
              <input v-model="kekerapan" type="radio" value="mingguan" class="rounded border-slate-300" />
              <span class="text-sm">Mingguan</span>
            </label>
            <label class="flex items-center gap-2">
              <input v-model="kekerapan" type="radio" value="bulanan" class="rounded border-slate-300" />
              <span class="text-sm">Bulanan</span>
            </label>
          </div>
        </aside>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <FileCheck class="h-4 w-4" />
            Pemadanan Resit & Penyata Bank
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Masa</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.amount") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Kod Ejen</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Cek</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(item, i) in items" :key="i" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 text-slate-600">{{ item.tarikh }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.masa }}</td>
                <td class="px-4 py-2 text-slate-600">RM {{ item.jumlah.toLocaleString() }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noAkaun }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.kodEjen }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noCek }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ item.status === "padan" ? "Padan" : "Belum Padan" }}
                  </span>
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
