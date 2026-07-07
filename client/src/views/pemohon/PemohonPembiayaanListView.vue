<script setup lang="ts">
import { computed } from "vue";
import { ArrowRight, Wallet, TrendingUp, CalendarClock } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { formatRM } from "@/data/pembiayaan-dummy";

const pemohon = usePemohonStore();

const akaunList = computed(() => pemohon.pembiayaanList);
const aktifCount = computed(() => akaunList.value.filter((a) => a.status !== "Selesai").length);
const totalBaki = computed(() => akaunList.value.reduce((sum, a) => sum + a.bakiAkhir, 0));
const totalTunggakan = computed(() => akaunList.value.reduce((sum, a) => sum + a.tunggakan, 0));

function statusBadgeClass(statusKey: string) {
  if (statusKey === "selesai") return "bg-emerald-50 text-emerald-700";
  if (statusKey === "tunggakan") return "bg-rose-50 text-rose-700";
  return "bg-blue-50 text-blue-700";
}

function progress(dibayar: number, jumlah: number) {
  return jumlah > 0 ? Math.round((dibayar / jumlah) * 100) : 0;
}
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="pemohon-hero rounded-b-2xl p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-white">Pembiayaan Saya</h1>
        <p class="mt-1 text-sm text-blue-100">Urus akaun pembiayaan, bayaran, dan penyata anda.</p>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-5 shadow-sm transition-shadow hover:shadow-md">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15">
            <Wallet class="h-5 w-5 text-white" />
          </div>
          <p class="mt-3 text-2xl font-bold text-white">{{ aktifCount }}</p>
          <p class="mt-1 text-xs font-medium text-white/90">Akaun Pembiayaan Aktif</p>
        </div>
        <div class="rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 p-5 shadow-sm transition-shadow hover:shadow-md">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15">
            <TrendingUp class="h-5 w-5 text-white" />
          </div>
          <p class="mt-3 text-2xl font-bold text-white">{{ formatRM(totalBaki) }}</p>
          <p class="mt-1 text-xs font-medium text-white/90">Jumlah Baki Tertunggak</p>
        </div>
        <div
          class="rounded-xl p-5 shadow-sm transition-shadow hover:shadow-md"
          :class="totalTunggakan > 0 ? 'bg-gradient-to-br from-rose-500 to-rose-600' : 'bg-gradient-to-br from-emerald-500 to-emerald-600'"
        >
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15">
            <CalendarClock class="h-5 w-5 text-white" />
          </div>
          <p class="mt-3 text-2xl font-bold text-white">{{ formatRM(totalTunggakan) }}</p>
          <p class="mt-1 text-xs font-medium text-white/90">Tunggakan Semasa</p>
        </div>
      </div>

      <div class="space-y-3">
        <router-link
          v-for="akaun in akaunList"
          :key="akaun.id"
          :to="`/pemohon/pembiayaan/${akaun.id}`"
          class="block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <h2 class="truncate text-base font-semibold text-slate-900">{{ akaun.produk }}</h2>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadgeClass(akaun.statusKey)">{{ akaun.status }}</span>
              </div>
              <p class="mt-0.5 text-xs text-slate-500">{{ akaun.id }}</p>
            </div>
            <ArrowRight class="mt-1 h-4 w-4 shrink-0 text-slate-400" />
          </div>

          <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div>
              <p class="text-xs text-slate-500">Baki Tertunggak</p>
              <p class="text-sm font-semibold text-slate-900">{{ formatRM(akaun.bakiAkhir) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500">Ansuran Bulanan</p>
              <p class="text-sm font-semibold text-slate-900">{{ formatRM(akaun.bayaranBulanan) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-500">Bayaran Seterusnya</p>
              <p class="text-sm font-semibold text-slate-900">{{ akaun.status === "Selesai" ? "—" : akaun.tarikhBayaranSeterusnya }}</p>
            </div>
          </div>

          <div class="mt-4">
            <div class="mb-1 flex items-center justify-between text-xs text-slate-500">
              <span>{{ akaun.ansuranDibayar }} / {{ akaun.jumlahAnsuran }} ansuran</span>
              <span>{{ progress(akaun.ansuranDibayar, akaun.jumlahAnsuran) }}%</span>
            </div>
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full"
                :class="akaun.statusKey === 'selesai' ? 'bg-emerald-500' : 'bg-blue-600'"
                :style="{ width: `${progress(akaun.ansuranDibayar, akaun.jumlahAnsuran)}%` }"
              />
            </div>
          </div>
        </router-link>
      </div>
    </div>
  </PemohonLayout>
</template>
