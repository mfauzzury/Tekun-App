<script setup lang="ts">
import { computed } from "vue";
import { AlertTriangle, ArrowRight, ChevronRight, CreditCard, MoreVertical, Wallet } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { formatRM } from "@/data/pembiayaan-dummy";
import { statusTone } from "@/utils/pemohon-status";

const pemohon = usePemohonStore();
const todayLabel = new Date().toLocaleDateString("ms-MY", { weekday: "long", day: "numeric", month: "long", year: "numeric" });

const akaunList = computed(() => pemohon.pembiayaanList);
const aktif = computed(() => akaunList.value.filter((a) => a.status !== "Selesai"));
const aktifCount = computed(() => aktif.value.length);
const totalBaki = computed(() => akaunList.value.reduce((sum, a) => sum + a.bakiAkhir, 0));
const totalTunggakan = computed(() => akaunList.value.reduce((sum, a) => sum + a.tunggakan, 0));
const totalBulanan = computed(() => aktif.value.reduce((sum, a) => sum + a.bayaranBulanan, 0));

/** Account to pay next: anything in arrears first, otherwise the first active account. */
const seterusnya = computed(() => aktif.value.find((a) => a.tunggakan > 0) ?? aktif.value[0] ?? null);

const peratusKeseluruhan = computed(() => {
  const dibayar = akaunList.value.reduce((sum, a) => sum + a.ansuranDibayar, 0);
  const jumlah = akaunList.value.reduce((sum, a) => sum + a.jumlahAnsuran, 0);
  return jumlah > 0 ? Math.round((dibayar / jumlah) * 100) : 0;
});
const RADIUS = 52;
const CIRC = 2 * Math.PI * RADIUS;
const ringDash = computed(() => `${(peratusKeseluruhan.value / 100) * CIRC} ${CIRC}`);

function progress(dibayar: number, jumlah: number) {
  return jumlah > 0 ? Math.round((dibayar / jumlah) * 100) : 0;
}
</script>

<template>
  <PemohonLayout>
    <div class="space-y-8">
      <header class="flex flex-col gap-3 pt-6 sm:flex-row sm:items-end sm:justify-between lg:pt-2">
        <div class="min-w-0">
          <p class="text-sm capitalize text-slate-500">{{ todayLabel }}</p>
          <h1 class="mt-1 text-[28px] font-bold tracking-tight text-ink-950 sm:text-[32px]">Pembiayaan saya</h1>
          <p class="mt-1 text-sm text-slate-500">Urus akaun pembiayaan, bayaran dan penyata anda di satu tempat.</p>
        </div>
        <router-link
          v-if="seterusnya"
          :to="`/pemohon/pembiayaan/${seterusnya.id}/bayar`"
          class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-ink-800 transition-colors hover:border-brand-300 hover:bg-brand-50 sm:self-auto"
        >
          <CreditCard class="h-4 w-4 text-brand-600" aria-hidden="true" /> Bayar ansuran
        </router-link>
      </header>

      <div v-if="!akaunList.length" class="rounded-3xl border border-slate-200 bg-white">
        <PEmptyState :icon="Wallet" title="Tiada akaun pembiayaan" description="Akaun akan dipaparkan di sini selepas permohonan anda diluluskan dan dana dikeluarkan." />
      </div>

      <template v-else>
        <!-- Bento -->
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-[1fr_1fr_1.75fr_1fr]">
          <div class="relative rounded-3xl bg-brand-600 p-5 text-white shadow-lg shadow-brand-600/25">
            <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-white/70" aria-hidden="true" />
            <p class="text-[34px] font-bold leading-none">{{ aktifCount }}</p>
            <p class="mt-3 text-[15px] text-white/90">Akaun aktif</p>
            <p class="mt-2 text-xs text-white/75">{{ akaunList.length }} akaun keseluruhan</p>
          </div>
          <div class="relative rounded-3xl border border-slate-200 bg-white p-5">
            <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-slate-300" aria-hidden="true" />
            <p class="text-[24px] font-bold leading-none text-ink-950 sm:text-[26px]">{{ formatRM(totalBaki) }}</p>
            <p class="mt-3 text-[15px] text-ink-800">Jumlah baki</p>
            <p class="mt-2 text-xs text-slate-500">Pokok + keuntungan</p>
          </div>
          <div class="relative col-start-1 rounded-3xl border p-5 xl:row-start-2" :class="totalTunggakan > 0 ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-white'">
            <MoreVertical class="absolute right-4 top-5 h-5 w-5" :class="totalTunggakan > 0 ? 'text-rose-300' : 'text-slate-300'" aria-hidden="true" />
            <p class="text-[24px] font-bold leading-none sm:text-[26px]" :class="totalTunggakan > 0 ? 'text-rose-700' : 'text-ink-950'">{{ formatRM(totalTunggakan) }}</p>
            <p class="mt-3 text-[15px]" :class="totalTunggakan > 0 ? 'text-rose-800' : 'text-ink-800'">Tunggakan semasa</p>
            <p class="mt-2 text-xs" :class="totalTunggakan > 0 ? 'text-rose-700/80' : 'text-slate-500'">{{ totalTunggakan > 0 ? "Sila jelaskan segera" : "Tiada tunggakan" }}</p>
          </div>
          <div class="relative rounded-3xl bg-brand-50 p-5 xl:col-start-2 xl:row-start-2">
            <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-brand-300" aria-hidden="true" />
            <p class="text-[24px] font-bold leading-none text-brand-800 sm:text-[26px]">{{ formatRM(totalBulanan) }}</p>
            <p class="mt-3 text-[15px] text-brand-800">Ansuran bulanan</p>
            <p class="mt-2 text-xs text-brand-800/70">Semua akaun aktif</p>
          </div>

          <!-- Next payment -->
          <div class="col-span-2 flex flex-col justify-between rounded-3xl bg-[#e2edff] p-6 sm:p-8 xl:col-span-1 xl:col-start-3 xl:row-span-2 xl:row-start-1">
            <template v-if="seterusnya">
              <div>
                <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Bayaran seterusnya</p>
                <h2 class="mt-3 text-[30px] font-bold leading-tight tracking-tight text-ink-950 sm:text-[36px]">
                  {{ formatRM(seterusnya.bayaranBulanan) }}
                </h2>
                <p class="mt-1 text-sm text-ink-700">
                  {{ seterusnya.produk }} · <span class="font-mono text-xs">{{ seterusnya.id }}</span>
                </p>
                <p class="mt-3 text-[15px] text-ink-800">
                  Perlu dibayar sebelum <span class="font-semibold text-brand-700">{{ seterusnya.tarikhBayaranSeterusnya }}</span>
                </p>
                <p v-if="seterusnya.tunggakan > 0" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
                  <AlertTriangle class="h-3.5 w-3.5" aria-hidden="true" /> Termasuk tunggakan {{ formatRM(seterusnya.tunggakan) }}
                </p>
              </div>
              <router-link
                :to="`/pemohon/pembiayaan/${seterusnya.id}/bayar`"
                class="mt-6 inline-flex items-center gap-2 self-start text-sm font-bold uppercase tracking-wide text-ink-950 transition-all hover:gap-3"
              >
                Bayar sekarang <ArrowRight class="h-5 w-5" aria-hidden="true" />
              </router-link>
            </template>
            <template v-else>
              <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Bayaran seterusnya</p>
              <h2 class="mt-3 text-[28px] font-bold leading-tight text-ink-950">Semua pembiayaan telah dijelaskan</h2>
            </template>
          </div>

          <!-- Ring -->
          <div class="col-span-2 flex flex-col items-center justify-center rounded-3xl bg-[#eef3fb] p-6 text-center xl:col-span-1 xl:col-start-4 xl:row-span-2 xl:row-start-1">
            <div class="relative h-36 w-36">
              <svg viewBox="0 0 120 120" class="h-36 w-36 -rotate-90" aria-hidden="true">
                <circle cx="60" cy="60" :r="RADIUS" fill="none" stroke-width="10" class="stroke-white" />
                <circle
                  cx="60"
                  cy="60"
                  :r="RADIUS"
                  fill="none"
                  stroke-width="10"
                  stroke-linecap="round"
                  :stroke-dasharray="ringDash"
                  class="stroke-brand-600 transition-[stroke-dasharray] duration-700"
                />
              </svg>
              <span class="absolute inset-0 flex items-center justify-center text-[30px] font-bold text-ink-950">{{ peratusKeseluruhan }}%</span>
            </div>
            <p class="mt-4 text-[15px] leading-snug text-ink-800">ansuran keseluruhan<br />telah dibayar</p>
          </div>
        </div>

        <!-- Accounts -->
        <section class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="akaun-title">
          <div class="mb-4 flex items-center justify-between px-1">
            <h2 id="akaun-title" class="text-[17px] font-semibold text-ink-900">Akaun pembiayaan</h2>
            <span class="text-sm text-slate-500">{{ akaunList.length }} akaun</span>
          </div>

          <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <router-link
              v-for="akaun in akaunList"
              :key="akaun.id"
              :to="`/pemohon/pembiayaan/${akaun.id}`"
              class="group flex flex-col rounded-2xl bg-[#f3f6fc] p-5 transition-all hover:-translate-y-0.5 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 sm:p-6"
            >
              <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-brand-600">
                  <Wallet class="h-5 w-5" aria-hidden="true" />
                </span>
                <div class="min-w-0 flex-1">
                  <h3 class="truncate text-[16px] font-semibold text-ink-900">{{ akaun.produk }}</h3>
                  <p class="mt-0.5 font-mono text-xs text-slate-500">{{ akaun.id }}</p>
                </div>
                <PBadge :tone="statusTone(akaun.statusKey)" dot>{{ akaun.status }}</PBadge>
              </div>

              <div class="mt-5">
                <p class="text-xs font-medium text-slate-500">Baki tertunggak</p>
                <p class="mt-0.5 text-[28px] font-bold leading-tight tracking-tight text-ink-950">{{ formatRM(akaun.bakiAkhir) }}</p>
              </div>

              <div class="mt-4">
                <div class="h-5 w-full overflow-hidden rounded-full bg-white" role="progressbar" :aria-valuenow="progress(akaun.ansuranDibayar, akaun.jumlahAnsuran)" aria-valuemin="0" aria-valuemax="100">
                  <div
                    class="h-full rounded-full transition-all"
                    :style="{
                      width: `${Math.max(6, progress(akaun.ansuranDibayar, akaun.jumlahAnsuran))}%`,
                      backgroundImage:
                        akaun.statusKey === 'selesai'
                          ? 'repeating-linear-gradient(-45deg, #34d399 0 8px, #a7f3d0 8px 16px)'
                          : 'repeating-linear-gradient(-45deg, #82a9ff 0 8px, #b3cdff 8px 16px)',
                    }"
                  />
                </div>
                <div class="mt-1.5 flex items-center justify-between text-xs text-slate-500">
                  <span>{{ akaun.ansuranDibayar }} / {{ akaun.jumlahAnsuran }} ansuran dibayar</span>
                  <span class="font-semibold text-ink-800">{{ progress(akaun.ansuranDibayar, akaun.jumlahAnsuran) }}%</span>
                </div>
              </div>

              <div v-if="akaun.tunggakan > 0" class="mt-4 flex items-center gap-2 rounded-xl bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
                <AlertTriangle class="h-4 w-4 shrink-0" aria-hidden="true" />
                Tunggakan {{ formatRM(akaun.tunggakan) }} perlu dijelaskan
              </div>

              <div class="mt-5 flex items-end justify-between gap-3 border-t border-white pt-4">
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
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-slate-400 transition-colors group-hover:text-brand-600">
                  <ChevronRight class="h-4 w-4" aria-hidden="true" />
                </span>
              </div>
            </router-link>
          </div>
        </section>
      </template>
    </div>
  </PemohonLayout>
</template>
