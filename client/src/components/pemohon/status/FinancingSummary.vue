<script setup lang="ts">
import { computed } from "vue";
import { ArrowRight, CalendarDays, CreditCard, LifeBuoy, TriangleAlert, UserRound, Wallet } from "lucide-vue-next";

import PCard from "@/components/pemohon/ui/PCard.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { formatRinggit } from "@/utils/pemohon-ui";

const pemohon = usePemohonStore();

const active = computed(() => pemohon.pembiayaanList.filter((a) => a.status !== "Selesai"));
const totalBaki = computed(() => active.value.reduce((sum, a) => sum + a.bakiAkhir, 0));
const totalTunggakan = computed(() => active.value.reduce((sum, a) => sum + a.tunggakan, 0));
/** Accounts in arrears come first — that's the instalment the user should act on. */
const next = computed(() => active.value.find((a) => a.tunggakan > 0) ?? active.value[0] ?? null);

const actions = computed(() => [
  { label: "Bayar ansuran", icon: CreditCard, to: next.value ? `/pemohon/pembiayaan/${next.value.id}/bayar` : "/pemohon/pembiayaan" },
  { label: "Jadual bayaran", icon: CalendarDays, to: next.value ? `/pemohon/pembiayaan/${next.value.id}?tab=jadual` : "/pemohon/pembiayaan" },
  { label: "Bantuan", icon: LifeBuoy, to: "/pemohon/bantuan" },
  { label: "Profil", icon: UserRound, to: "/pemohon/profil" },
]);
</script>

<template>
  <PCard title="Pembiayaan saya" subtitle="Ringkasan akaun pembiayaan anda">
    <template #actions>
      <router-link to="/pemohon/pembiayaan" class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800">
        Lihat semua <ArrowRight class="h-4 w-4" aria-hidden="true" />
      </router-link>
    </template>

    <div v-if="active.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <div class="rounded-xl bg-slate-50 p-3.5">
        <p class="text-xs font-medium text-slate-500">Akaun aktif</p>
        <p class="mt-1 text-xl font-bold tracking-tight text-ink-900">{{ active.length }}</p>
      </div>
      <div class="rounded-xl bg-slate-50 p-3.5">
        <p class="text-xs font-medium text-slate-500">Jumlah baki</p>
        <p class="mt-1 truncate text-xl font-bold tracking-tight text-ink-900">{{ formatRinggit(totalBaki) }}</p>
      </div>
      <router-link
        v-if="next"
        :to="`/pemohon/pembiayaan/${next.id}`"
        class="col-span-2 rounded-xl border p-3.5 transition-colors sm:col-span-1"
        :class="next.tunggakan > 0 ? 'border-amber-200 bg-amber-50/70 hover:bg-amber-50' : 'border-slate-200 bg-white hover:bg-slate-50'"
      >
        <p class="text-xs font-medium text-slate-500">Ansuran seterusnya</p>
        <p class="mt-1 text-xl font-bold tracking-tight text-ink-900">{{ formatRinggit(next.bayaranBulanan) }}</p>
        <p class="mt-0.5 truncate text-xs text-slate-500">{{ next.tarikhBayaranSeterusnya }} · {{ next.produk }}</p>
      </router-link>
    </div>
    <div v-else class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
      <Wallet class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
      Tiada pembiayaan aktif buat masa ini.
    </div>

    <p v-if="totalTunggakan > 0" class="mt-3 flex items-center gap-2 rounded-xl bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800" role="status">
      <TriangleAlert class="h-4 w-4 shrink-0" aria-hidden="true" />
      Tunggakan {{ formatRinggit(totalTunggakan) }} — sila jelaskan secepat mungkin.
    </p>

    <nav class="mt-5 grid grid-cols-4 gap-2" aria-label="Tindakan pantas">
      <router-link
        v-for="a in actions"
        :key="a.label"
        :to="a.to"
        class="group flex flex-col items-center gap-2 rounded-xl px-1 py-3 text-center transition-colors hover:bg-slate-50"
      >
        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 transition-colors group-hover:bg-brand-100">
          <component :is="a.icon" class="h-5 w-5" aria-hidden="true" />
        </span>
        <span class="text-[11px] font-medium leading-tight text-ink-700 sm:text-xs">{{ a.label }}</span>
      </router-link>
    </nav>
  </PCard>
</template>
