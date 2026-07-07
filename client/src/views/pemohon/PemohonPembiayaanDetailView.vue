<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute } from "vue-router";
import { ArrowDownCircle, ArrowLeft, ArrowUpCircle, ChevronLeft, ChevronRight, CreditCard, RefreshCw, CheckCircle2 } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { formatRM, generateJadualAnsuran } from "@/data/pembiayaan-dummy";

const route = useRoute();
const pemohon = usePemohonStore();

const akaun = computed(() => pemohon.getPembiayaan(String(route.params.id)));
const transaksi = computed(() => pemohon.transaksiMap[String(route.params.id)] ?? []);
const jadual = computed(() => (akaun.value ? generateJadualAnsuran(akaun.value) : []));
const isSelesai = computed(() => akaun.value?.status === "Selesai");

const tabs = [
  { id: "ringkasan", label: "Ringkasan" },
  { id: "jadual", label: "Jadual Bayaran" },
  { id: "transaksi", label: "Transaksi" },
];
const activeTab = ref("ringkasan");

const progress = computed(() =>
  akaun.value && akaun.value.jumlahAnsuran > 0
    ? Math.round((akaun.value.ansuranDibayar / akaun.value.jumlahAnsuran) * 100)
    : 0,
);

const PAGE_SIZE = 10;
const jadualPage = ref(1);
const transaksiPage = ref(1);

const jadualTotalPages = computed(() => Math.max(1, Math.ceil(jadual.value.length / PAGE_SIZE)));
const jadualPaged = computed(() => {
  const page = Math.min(jadualPage.value, jadualTotalPages.value);
  return jadual.value.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);
});

const transaksiTotalPages = computed(() => Math.max(1, Math.ceil(transaksi.value.length / PAGE_SIZE)));
const transaksiPaged = computed(() => {
  const page = Math.min(transaksiPage.value, transaksiTotalPages.value);
  return transaksi.value.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);
});

function setJadualPage(p: number) {
  jadualPage.value = Math.min(Math.max(1, p), jadualTotalPages.value);
}
function setTransaksiPage(p: number) {
  transaksiPage.value = Math.min(Math.max(1, p), transaksiTotalPages.value);
}

function jadualStatusClass(status: string) {
  if (status === "dibayar") return "bg-emerald-50 text-emerald-700";
  if (status === "tertunggak") return "bg-rose-50 text-rose-700";
  return "bg-slate-100 text-slate-500";
}

function jadualStatusLabel(status: string) {
  if (status === "dibayar") return "Dibayar";
  if (status === "tertunggak") return "Tertunggak";
  return "Akan Datang";
}
</script>

<template>
  <PemohonLayout>
    <div v-if="!akaun" class="mx-auto max-w-5xl rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
      Akaun pembiayaan tidak dijumpai.
    </div>

    <div v-else class="mx-auto max-w-5xl space-y-5">
      <div class="pemohon-hero rounded-b-2xl p-6 shadow-sm">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-200">{{ akaun.id }}</p>
            <h1 class="mt-2 text-xl font-semibold text-white">{{ akaun.produk }}</h1>
          </div>
          <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-medium text-white">{{ akaun.status }}</span>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
          <div>
            <p class="text-xs text-blue-200">Baki Tertunggak</p>
            <p class="text-lg font-bold text-white">{{ formatRM(akaun.bakiAkhir) }}</p>
          </div>
          <div>
            <p class="text-xs text-blue-200">Ansuran Bulanan</p>
            <p class="text-lg font-bold text-white">{{ formatRM(akaun.bayaranBulanan) }}</p>
          </div>
          <div>
            <p class="text-xs text-blue-200">Bayaran Seterusnya</p>
            <p class="text-lg font-bold text-white">{{ isSelesai ? "—" : akaun.tarikhBayaranSeterusnya }}</p>
          </div>
        </div>
      </div>

      <router-link to="/pemohon/pembiayaan" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900">
        <ArrowLeft class="h-4 w-4" />
        Pembiayaan Saya
      </router-link>

      <!-- Actions -->
      <div v-if="!isSelesai" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <router-link
          :to="`/pemohon/pembiayaan/${akaun.id}/bayar`"
          class="flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
        >
          <CreditCard class="h-4 w-4" />
          Buat Bayaran
        </router-link>
        <router-link
          :to="`/pemohon/pembiayaan/${akaun.id}/penstrukturan`"
          class="flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50"
        >
          <RefreshCw class="h-4 w-4" />
          Mohon Penstrukturan
        </router-link>
        <router-link
          :to="`/pemohon/pembiayaan/${akaun.id}/penyelesaian`"
          class="flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50"
        >
          <CheckCircle2 class="h-4 w-4" />
          Penyelesaian Awal
        </router-link>
      </div>
      <div v-else class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
        <CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-600" />
        Pembiayaan ini telah selesai sepenuhnya. Terima kasih.
      </div>

      <!-- Tabs -->
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex border-b border-slate-200">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            class="flex-1 border-b-2 px-4 py-3 text-sm font-medium transition-colors"
            :class="activeTab === tab.id ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
            @click="activeTab = tab.id"
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Ringkasan -->
        <div v-if="activeTab === 'ringkasan'" class="space-y-5 p-5">
          <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div>
              <p class="text-xs text-slate-500">Jumlah Pembiayaan</p>
              <p class="text-sm font-semibold text-slate-900">{{ formatRM(akaun.jumlahPembiayaan) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500">Baki Pokok</p>
              <p class="text-sm font-semibold text-slate-900">{{ formatRM(akaun.bakiPokok) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500">Baki Keuntungan</p>
              <p class="text-sm font-semibold text-slate-900">{{ formatRM(akaun.bakiKeuntungan) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500">Tunggakan</p>
              <p class="text-sm font-semibold" :class="akaun.tunggakan > 0 ? 'text-rose-600' : 'text-slate-900'">{{ formatRM(akaun.tunggakan) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500">Tempoh</p>
              <p class="text-sm font-semibold text-slate-900">{{ akaun.tempohBulan }} bulan</p>
            </div>
            <div>
              <p class="text-xs text-slate-500">Tarikh Mula — Tamat</p>
              <p class="text-sm font-semibold text-slate-900">{{ akaun.tarikhMula }} — {{ akaun.tarikhTamat }}</p>
            </div>
          </div>

          <div>
            <div class="mb-1 flex items-center justify-between text-xs text-slate-500">
              <span>Kemajuan Bayaran</span>
              <span>{{ akaun.ansuranDibayar }} / {{ akaun.jumlahAnsuran }} ansuran ({{ progress }}%)</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
              <div class="h-full rounded-full bg-blue-600" :style="{ width: `${progress}%` }" />
            </div>
          </div>
        </div>

        <!-- Jadual Bayaran -->
        <div v-else-if="activeTab === 'jadual'">
          <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] text-sm">
              <thead>
                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                  <th class="px-4 py-3 font-medium">No.</th>
                  <th class="px-4 py-3 font-medium">Tarikh</th>
                  <th class="px-4 py-3 text-right font-medium">Ansuran</th>
                  <th class="px-4 py-3 text-right font-medium">Baki</th>
                  <th class="px-4 py-3 text-right font-medium">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in jadualPaged" :key="row.no" class="border-b border-slate-100 last:border-0">
                  <td class="px-4 py-2.5 text-slate-500">{{ row.no }}</td>
                  <td class="px-4 py-2.5 text-slate-700">{{ row.tarikh }}</td>
                  <td class="px-4 py-2.5 text-right text-slate-900">{{ formatRM(row.ansuran) }}</td>
                  <td class="px-4 py-2.5 text-right text-slate-500">{{ formatRM(row.baki) }}</td>
                  <td class="px-4 py-2.5 text-right">
                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium" :class="jadualStatusClass(row.status)">{{ jadualStatusLabel(row.status) }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="jadualTotalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-3">
            <span class="text-xs text-slate-500">Halaman {{ Math.min(jadualPage, jadualTotalPages) }} / {{ jadualTotalPages }}</span>
            <div class="flex gap-2">
              <button
                type="button"
                class="flex items-center gap-1 rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="jadualPage <= 1"
                @click="setJadualPage(jadualPage - 1)"
              >
                <ChevronLeft class="h-3.5 w-3.5" />
                Sebelum
              </button>
              <button
                type="button"
                class="flex items-center gap-1 rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="jadualPage >= jadualTotalPages"
                @click="setJadualPage(jadualPage + 1)"
              >
                Seterusnya
                <ChevronRight class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Transaksi -->
        <div v-else>
          <div class="divide-y divide-slate-100">
            <p v-if="!transaksi.length" class="p-5 text-center text-sm text-slate-500">Tiada transaksi lagi.</p>
            <div v-for="trx in transaksiPaged" :key="trx.id" class="flex items-center gap-3 p-4">
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                :class="trx.jenis === 'pengeluaran' ? 'bg-emerald-50' : 'bg-blue-50'"
              >
                <ArrowDownCircle v-if="trx.jenis === 'pengeluaran'" class="h-4 w-4 text-emerald-600" />
                <ArrowUpCircle v-else class="h-4 w-4 text-blue-600" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900">{{ trx.keterangan }}</p>
                <p class="text-xs text-slate-500">
                  {{ trx.tarikh }}<span v-if="trx.kaedah"> · {{ trx.kaedah }}</span> · {{ trx.rujukan }}
                </p>
              </div>
              <div class="shrink-0 text-right">
                <p
                  class="text-sm font-semibold"
                  :class="trx.jenis === 'pengeluaran' ? 'text-emerald-600' : 'text-slate-900'"
                >
                  {{ trx.jenis === 'pengeluaran' ? '+' : '−' }} {{ formatRM(trx.jumlah) }}
                </p>
                <p class="text-xs text-slate-400">{{ trx.status }}</p>
              </div>
            </div>
          </div>
          <div v-if="transaksiTotalPages > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-3">
            <span class="text-xs text-slate-500">Halaman {{ Math.min(transaksiPage, transaksiTotalPages) }} / {{ transaksiTotalPages }}</span>
            <div class="flex gap-2">
              <button
                type="button"
                class="flex items-center gap-1 rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="transaksiPage <= 1"
                @click="setTransaksiPage(transaksiPage - 1)"
              >
                <ChevronLeft class="h-3.5 w-3.5" />
                Sebelum
              </button>
              <button
                type="button"
                class="flex items-center gap-1 rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="transaksiPage >= transaksiTotalPages"
                @click="setTransaksiPage(transaksiPage + 1)"
              >
                Seterusnya
                <ChevronRight class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
