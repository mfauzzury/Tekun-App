<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute } from "vue-router";
import { ArrowDownLeft, ArrowUpRight, CheckCircle2, ChevronLeft, ChevronRight, CreditCard, Receipt, RefreshCw, Wallet } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { formatRM, generateJadualAnsuran } from "@/data/pembiayaan-dummy";
import { statusTone } from "@/utils/pemohon-status";

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
const activeTab = ref(tabs.some((t) => t.id === route.query.tab) ? String(route.query.tab) : "ringkasan");

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

function jadualStatusLabel(status: string) {
  if (status === "dibayar") return "Dibayar";
  if (status === "tertunggak") return "Tertunggak";
  return "Akan Datang";
}
</script>

<template>
  <PemohonLayout>
    <div v-if="!akaun" class="pt-6">
      <div class="rounded-2xl border border-slate-200/80 bg-white">
        <PEmptyState :icon="Wallet" title="Akaun pembiayaan tidak dijumpai" description="Akaun ini mungkin telah ditutup atau pautan tidak sah.">
          <PButton variant="outline" to="/pemohon/pembiayaan">Kembali ke Pembiayaan Saya</PButton>
        </PEmptyState>
      </div>
    </div>

    <div v-else class="space-y-6">
      <PPageHeader :eyebrow="akaun.id" :title="akaun.produk" back="/pemohon/pembiayaan" back-label="Pembiayaan Saya">
        <template #actions>
          <PBadge :tone="statusTone(akaun.statusKey)" size="md" dot>{{ akaun.status }}</PBadge>
        </template>
      </PPageHeader>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Key balance -->
        <PCard tone="ink" class="relative lg:col-span-2">
          <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-brand-600/25 blur-3xl" />
          <div class="relative">
            <p class="text-sm font-medium text-ink-200">Baki tertunggak</p>
            <p class="mt-1 text-[34px] font-bold leading-tight tracking-tight text-white sm:text-[40px]">{{ formatRM(akaun.bakiAkhir) }}</p>

            <div class="mt-5">
              <div class="h-2 w-full overflow-hidden rounded-full bg-white/10">
                <div class="h-full rounded-full" :class="isSelesai ? 'bg-emerald-400' : 'bg-brand-500'" :style="{ width: `${progress}%` }" />
              </div>
              <div class="mt-1.5 flex items-center justify-between text-xs text-ink-200">
                <span>{{ akaun.ansuranDibayar }} / {{ akaun.jumlahAnsuran }} ansuran dibayar</span>
                <span class="font-semibold text-white">{{ progress }}%</span>
              </div>
            </div>

            <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-white/10 pt-5 sm:grid-cols-3">
              <div>
                <dt class="text-xs text-ink-300">Ansuran bulanan</dt>
                <dd class="mt-0.5 text-base font-semibold text-white">{{ formatRM(akaun.bayaranBulanan) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-ink-300">Bayaran seterusnya</dt>
                <dd class="mt-0.5 text-base font-semibold text-white">{{ isSelesai ? "—" : akaun.tarikhBayaranSeterusnya }}</dd>
              </div>
              <div class="col-span-2 sm:col-span-1">
                <dt class="text-xs text-ink-300">Tunggakan</dt>
                <dd class="mt-0.5 text-base font-semibold" :class="akaun.tunggakan > 0 ? 'text-brand-300' : 'text-white'">{{ formatRM(akaun.tunggakan) }}</dd>
              </div>
            </dl>
          </div>
        </PCard>

        <!-- Actions -->
        <PCard v-if="!isSelesai" title="Tindakan" subtitle="Urus akaun anda dengan pantas.">
          <div class="space-y-2.5">
            <PButton block size="lg" :to="`/pemohon/pembiayaan/${akaun.id}/bayar`">
              <CreditCard class="h-4 w-4" />
              Bayar ansuran
            </PButton>
            <PButton block variant="outline" :to="`/pemohon/pembiayaan/${akaun.id}/penstrukturan`">
              <RefreshCw class="h-4 w-4" />
              Penstrukturan semula
            </PButton>
            <PButton block variant="outline" :to="`/pemohon/pembiayaan/${akaun.id}/penyelesaian`">
              <CheckCircle2 class="h-4 w-4" />
              Penyelesaian awal
            </PButton>
          </div>
        </PCard>
        <div v-else class="flex flex-col items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
          <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
            <CheckCircle2 class="h-6 w-6" />
          </span>
          <p class="mt-3 text-[15px] font-semibold text-emerald-900">Pembiayaan selesai</p>
          <p class="mt-1 text-sm text-emerald-700">Pembiayaan ini telah selesai sepenuhnya. Terima kasih.</p>
        </div>
      </div>

      <!-- Segmented tabs -->
      <div role="tablist" aria-label="Butiran akaun" class="grid w-full grid-cols-3 gap-1 rounded-xl bg-slate-200/60 p-1 sm:inline-grid sm:w-auto">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          role="tab"
          :aria-selected="activeTab === tab.id"
          class="rounded-lg px-3 py-2 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 sm:px-5"
          :class="activeTab === tab.id ? 'bg-white text-ink-900 shadow-sm' : 'text-slate-500 hover:text-ink-900'"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Ringkasan -->
      <PCard v-if="activeTab === 'ringkasan'" title="Ringkasan pembiayaan">
        <dl class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3">
          <div>
            <dt class="text-xs text-slate-500">Jumlah pembiayaan</dt>
            <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ formatRM(akaun.jumlahPembiayaan) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Baki pokok</dt>
            <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ formatRM(akaun.bakiPokok) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Baki keuntungan</dt>
            <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ formatRM(akaun.bakiKeuntungan) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Tunggakan</dt>
            <dd class="mt-0.5 text-sm font-semibold" :class="akaun.tunggakan > 0 ? 'text-rose-600' : 'text-ink-900'">{{ formatRM(akaun.tunggakan) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Tempoh</dt>
            <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ akaun.tempohBulan }} bulan</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Tarikh mula — tamat</dt>
            <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ akaun.tarikhMula }} — {{ akaun.tarikhTamat }}</dd>
          </div>
        </dl>

        <div class="mt-6 rounded-xl bg-slate-50 p-4">
          <div class="mb-2 flex items-center justify-between text-xs">
            <span class="font-medium text-ink-800">Kemajuan bayaran</span>
            <span class="text-slate-500">{{ akaun.ansuranDibayar }} / {{ akaun.jumlahAnsuran }} ansuran ({{ progress }}%)</span>
          </div>
          <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200/70">
            <div class="h-full rounded-full" :class="isSelesai ? 'bg-emerald-500' : 'bg-brand-600'" :style="{ width: `${progress}%` }" />
          </div>
        </div>
      </PCard>

      <!-- Jadual Bayaran -->
      <PCard v-else-if="activeTab === 'jadual'" title="Jadual bayaran" :subtitle="`${jadual.length} ansuran keseluruhan`" :padded="false">
        <!-- md+: table -->
        <div class="hidden md:block">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-y border-slate-100 bg-slate-50/70 text-left text-xs font-medium text-slate-500">
                <th scope="col" class="px-5 py-3 font-medium">No.</th>
                <th scope="col" class="px-5 py-3 font-medium">Tarikh</th>
                <th scope="col" class="px-5 py-3 text-right font-medium">Ansuran</th>
                <th scope="col" class="px-5 py-3 text-right font-medium">Baki</th>
                <th scope="col" class="px-5 py-3 text-right font-medium">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="row in jadualPaged" :key="row.no" class="transition-colors hover:bg-slate-50/60">
                <td class="px-5 py-3 tabular-nums text-slate-500">{{ row.no }}</td>
                <td class="px-5 py-3 text-ink-800">{{ row.tarikh }}</td>
                <td class="px-5 py-3 text-right font-medium tabular-nums text-ink-900">{{ formatRM(row.ansuran) }}</td>
                <td class="px-5 py-3 text-right tabular-nums text-slate-500">{{ formatRM(row.baki) }}</td>
                <td class="px-5 py-3 text-right">
                  <PBadge :tone="statusTone(row.status)">{{ jadualStatusLabel(row.status) }}</PBadge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- mobile: stacked -->
        <ul class="divide-y divide-slate-100 border-t border-slate-100 md:hidden">
          <li v-for="row in jadualPaged" :key="row.no" class="flex items-center gap-3 px-5 py-3.5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold tabular-nums text-slate-600">{{ row.no }}</span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold tabular-nums text-ink-900">{{ formatRM(row.ansuran) }}</p>
              <p class="text-xs text-slate-500">{{ row.tarikh }} · Baki {{ formatRM(row.baki) }}</p>
            </div>
            <PBadge :tone="statusTone(row.status)">{{ jadualStatusLabel(row.status) }}</PBadge>
          </li>
        </ul>
        <div v-if="jadualTotalPages > 1" class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3">
          <span class="text-xs text-slate-500">Halaman {{ Math.min(jadualPage, jadualTotalPages) }} / {{ jadualTotalPages }}</span>
          <div class="flex gap-2">
            <PButton size="sm" variant="outline" :disabled="jadualPage <= 1" aria-label="Halaman sebelum" @click="setJadualPage(jadualPage - 1)">
              <ChevronLeft class="h-4 w-4" />
              <span class="hidden sm:inline">Sebelum</span>
            </PButton>
            <PButton size="sm" variant="outline" :disabled="jadualPage >= jadualTotalPages" aria-label="Halaman seterusnya" @click="setJadualPage(jadualPage + 1)">
              <span class="hidden sm:inline">Seterusnya</span>
              <ChevronRight class="h-4 w-4" />
            </PButton>
          </div>
        </div>
      </PCard>

      <!-- Transaksi -->
      <PCard v-else title="Sejarah transaksi" :subtitle="`${transaksi.length} transaksi`" :padded="false">
        <div v-if="!transaksi.length" class="border-t border-slate-100">
          <PEmptyState :icon="Receipt" title="Tiada transaksi lagi" description="Bayaran dan pengeluaran dana akan dipaparkan di sini." />
        </div>
        <template v-else>
          <!-- md+: table -->
          <div class="hidden md:block">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-y border-slate-100 bg-slate-50/70 text-left text-xs text-slate-500">
                  <th scope="col" class="px-5 py-3 font-medium">Transaksi</th>
                  <th scope="col" class="px-5 py-3 font-medium">Tarikh</th>
                  <th scope="col" class="px-5 py-3 font-medium">Rujukan</th>
                  <th scope="col" class="px-5 py-3 text-right font-medium">Jumlah</th>
                  <th scope="col" class="px-5 py-3 text-right font-medium">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="trx in transaksiPaged" :key="trx.id" class="transition-colors hover:bg-slate-50/60">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                      <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                        :class="trx.jenis === 'pengeluaran' ? 'bg-emerald-50 text-emerald-600' : 'bg-ink-50 text-ink-700'"
                      >
                        <ArrowDownLeft v-if="trx.jenis === 'pengeluaran'" class="h-4 w-4" />
                        <ArrowUpRight v-else class="h-4 w-4" />
                      </span>
                      <div class="min-w-0">
                        <p class="font-medium text-ink-900">{{ trx.keterangan }}</p>
                        <p v-if="trx.kaedah" class="text-xs text-slate-500">{{ trx.kaedah }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-5 py-3 text-ink-800">{{ trx.tarikh }}</td>
                  <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ trx.rujukan }}</td>
                  <td
                    class="whitespace-nowrap px-5 py-3 text-right font-semibold tabular-nums"
                    :class="trx.jenis === 'pengeluaran' ? 'text-emerald-600' : 'text-ink-900'"
                  >
                    {{ trx.jenis === 'pengeluaran' ? '+' : '−' }} {{ formatRM(trx.jumlah) }}
                  </td>
                  <td class="px-5 py-3 text-right"><PBadge :tone="statusTone(trx.status)">{{ trx.status }}</PBadge></td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- mobile: stacked -->
          <ul class="divide-y divide-slate-100 border-t border-slate-100 md:hidden">
            <li v-for="trx in transaksiPaged" :key="trx.id" class="flex items-center gap-3 px-5 py-3.5">
              <span
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                :class="trx.jenis === 'pengeluaran' ? 'bg-emerald-50 text-emerald-600' : 'bg-ink-50 text-ink-700'"
              >
                <ArrowDownLeft v-if="trx.jenis === 'pengeluaran'" class="h-4 w-4" />
                <ArrowUpRight v-else class="h-4 w-4" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-ink-900">{{ trx.keterangan }}</p>
                <p class="truncate text-xs text-slate-500">
                  {{ trx.tarikh }}<span v-if="trx.kaedah"> · {{ trx.kaedah }}</span> · {{ trx.rujukan }}
                </p>
              </div>
              <div class="shrink-0 text-right">
                <p class="text-sm font-semibold tabular-nums" :class="trx.jenis === 'pengeluaran' ? 'text-emerald-600' : 'text-ink-900'">
                  {{ trx.jenis === 'pengeluaran' ? '+' : '−' }} {{ formatRM(trx.jumlah) }}
                </p>
                <p class="text-xs text-slate-400">{{ trx.status }}</p>
              </div>
            </li>
          </ul>
        </template>
        <div v-if="transaksiTotalPages > 1" class="flex items-center justify-between gap-3 border-t border-slate-100 px-5 py-3">
          <span class="text-xs text-slate-500">Halaman {{ Math.min(transaksiPage, transaksiTotalPages) }} / {{ transaksiTotalPages }}</span>
          <div class="flex gap-2">
            <PButton size="sm" variant="outline" :disabled="transaksiPage <= 1" aria-label="Halaman sebelum" @click="setTransaksiPage(transaksiPage - 1)">
              <ChevronLeft class="h-4 w-4" />
              <span class="hidden sm:inline">Sebelum</span>
            </PButton>
            <PButton size="sm" variant="outline" :disabled="transaksiPage >= transaksiTotalPages" aria-label="Halaman seterusnya" @click="setTransaksiPage(transaksiPage + 1)">
              <span class="hidden sm:inline">Seterusnya</span>
              <ChevronRight class="h-4 w-4" />
            </PButton>
          </div>
        </div>
      </PCard>
    </div>
  </PemohonLayout>
</template>
