<script setup lang="ts">
import { computed } from "vue";
import { AlertTriangle, CalendarClock, ChevronRight, TrendingUp, Wallet } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import PStatCard from "@/components/pemohon/ui/PStatCard.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { formatRM } from "@/data/pembiayaan-dummy";
import { statusTone } from "@/utils/pemohon-status";

const pemohon = usePemohonStore();

const akaunList = computed(() => pemohon.pembiayaanList);
const aktifCount = computed(() => akaunList.value.filter((a) => a.status !== "Selesai").length);
const totalBaki = computed(() => akaunList.value.reduce((sum, a) => sum + a.bakiAkhir, 0));
const totalTunggakan = computed(() => akaunList.value.reduce((sum, a) => sum + a.tunggakan, 0));

function progress(dibayar: number, jumlah: number) {
  return jumlah > 0 ? Math.round((dibayar / jumlah) * 100) : 0;
}
</script>

<template>
  <PemohonLayout>
    <div class="space-y-6">
      <PPageHeader eyebrow="Akaun saya" title="Pembiayaan Saya" subtitle="Urus akaun pembiayaan, bayaran dan penyata anda di satu tempat." />

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
        <PStatCard label="Akaun aktif" :value="aktifCount" :icon="Wallet" tone="brand" :hint="`${akaunList.length} akaun keseluruhan`" />
        <PStatCard label="Jumlah baki" :value="formatRM(totalBaki)" :icon="TrendingUp" tone="info" hint="Pokok + keuntungan tertunggak" />
        <PStatCard
          label="Tunggakan semasa"
          :value="formatRM(totalTunggakan)"
          :icon="CalendarClock"
          :tone="totalTunggakan > 0 ? 'danger' : 'success'"
          :hint="totalTunggakan > 0 ? 'Sila jelaskan segera' : 'Tiada tunggakan'"
        />
      </div>

      <div>
        <h2 class="mb-3 text-sm font-semibold text-ink-900">Akaun pembiayaan</h2>

        <div v-if="!akaunList.length" class="rounded-2xl border border-slate-200/80 bg-white">
          <PEmptyState :icon="Wallet" title="Tiada akaun pembiayaan" description="Akaun akan dipaparkan di sini selepas permohonan anda diluluskan dan dana dikeluarkan." />
        </div>

        <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <router-link
            v-for="akaun in akaunList"
            :key="akaun.id"
            :to="`/pemohon/pembiayaan/${akaun.id}`"
            class="group flex flex-col rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(16,24,40,0.04),0_4px_16px_-8px_rgba(16,24,40,0.08)] transition-all hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-[0_1px_2px_rgba(16,24,40,0.04),0_12px_28px_-12px_rgba(16,24,40,0.18)] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 sm:p-6"
          >
            <div class="flex items-start gap-3">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <Wallet class="h-5 w-5" />
              </span>
              <div class="min-w-0 flex-1">
                <h3 class="truncate text-[15px] font-semibold text-ink-900">{{ akaun.produk }}</h3>
                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ akaun.id }}</p>
              </div>
              <PBadge :tone="statusTone(akaun.statusKey)" dot>{{ akaun.status }}</PBadge>
            </div>

            <div class="mt-5">
              <p class="text-xs font-medium text-slate-500">Baki tertunggak</p>
              <p class="mt-0.5 text-[28px] font-bold leading-tight tracking-tight text-ink-900">{{ formatRM(akaun.bakiAkhir) }}</p>
            </div>

            <div class="mt-4">
              <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                <div
                  class="h-full rounded-full transition-all"
                  :class="akaun.statusKey === 'selesai' ? 'bg-emerald-500' : 'bg-brand-600'"
                  :style="{ width: `${progress(akaun.ansuranDibayar, akaun.jumlahAnsuran)}%` }"
                />
              </div>
              <div class="mt-1.5 flex items-center justify-between text-xs text-slate-500">
                <span>{{ akaun.ansuranDibayar }} / {{ akaun.jumlahAnsuran }} ansuran dibayar</span>
                <span class="font-semibold text-ink-800">{{ progress(akaun.ansuranDibayar, akaun.jumlahAnsuran) }}%</span>
              </div>
            </div>

            <div
              v-if="akaun.tunggakan > 0"
              class="mt-4 flex items-center gap-2 rounded-xl bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700"
            >
              <AlertTriangle class="h-4 w-4 shrink-0" />
              Tunggakan {{ formatRM(akaun.tunggakan) }} perlu dijelaskan
            </div>

            <div class="mt-5 flex items-end justify-between gap-3 border-t border-slate-100 pt-4">
              <dl class="grid flex-1 grid-cols-2 gap-3">
                <div>
                  <dt class="text-xs text-slate-500">Ansuran bulanan</dt>
                  <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ formatRM(akaun.bayaranBulanan) }}</dd>
                </div>
                <div>
                  <dt class="text-xs text-slate-500">Bayaran seterusnya</dt>
                  <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ akaun.status === "Selesai" ? "—" : akaun.tarikhBayaranSeterusnya }}</dd>
                </div>
              </dl>
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition-colors group-hover:bg-brand-50 group-hover:text-brand-600">
                <ChevronRight class="h-4 w-4" />
              </span>
            </div>
          </router-link>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
