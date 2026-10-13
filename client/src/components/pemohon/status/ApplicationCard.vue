<script setup lang="ts">
import { computed } from "vue";
import { ChevronRight, CloudOff, Sparkles } from "lucide-vue-next";

import PBadge from "@/components/pemohon/ui/PBadge.vue";
import type { PermohonanSaya } from "@/stores/pemohon";
import { formatRinggit } from "@/utils/pemohon-ui";
import StageTracker from "./StageTracker.vue";
import { deriveView, formatTarikh, type ApplicationSnapshot } from "./useApplicationStatus";

/** Rich card for a real (backend) application in the list. */
const props = defineProps<{ item: PermohonanSaya; snapshot?: ApplicationSnapshot | null; loading?: boolean }>();

const view = computed(() => deriveView(props.snapshot));
const pending = computed(() => props.loading && !props.snapshot);
const produk = computed(() => props.snapshot?.record?.kategoriPembiayaan || props.item.produk || "Pembiayaan");
const jumlah = computed(() => props.snapshot?.record?.jumlahPermohonan ?? props.item.jumlah);
</script>

<template>
  <router-link
    :to="`/pemohon/permohonan/${item.id}`"
    class="group block rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(16,24,40,0.04),0_4px_16px_-8px_rgba(16,24,40,0.08)] transition-all hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-[0_8px_24px_-12px_rgba(16,24,40,0.18)] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
    :aria-busy="pending"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="font-mono text-xs font-medium tracking-wide text-slate-500">{{ item.noRujukan }}</p>
        <p class="mt-0.5 truncate text-[15px] font-semibold text-ink-900">{{ produk }}</p>
      </div>
      <span v-if="pending" class="h-6 w-24 shrink-0 animate-pulse rounded-full bg-slate-200/70" aria-hidden="true" />
      <PBadge v-else :tone="view.tone" dot>{{ view.statusLabel }}</PBadge>
    </div>

    <div class="mt-3 flex items-end justify-between gap-3">
      <div>
        <p class="text-xl font-bold tracking-tight text-ink-900">{{ formatRinggit(jumlah) }}</p>
        <p class="mt-0.5 text-xs text-slate-500">Dihantar {{ formatTarikh(item.dihantarPada) }}</p>
      </div>
      <ChevronRight class="h-5 w-5 shrink-0 text-slate-300 transition-colors group-hover:text-brand-600" aria-hidden="true" />
    </div>

    <div class="mt-4 border-t border-slate-100 pt-4">
      <div v-if="pending" class="space-y-2" aria-hidden="true">
        <div class="h-1.5 animate-pulse rounded-full bg-slate-200/70" />
        <div class="h-3 w-32 animate-pulse rounded bg-slate-200/70" />
      </div>
      <StageTracker v-else :stage="view.stage" :outcome="view.keputusan" size="sm" />
      <p v-if="snapshot?.failed" class="mt-2 flex items-center gap-1.5 text-xs text-amber-700">
        <CloudOff class="h-3.5 w-3.5" aria-hidden="true" /> Status tidak dapat dikemas kini
      </p>
      <p v-else-if="view.aiState === 'berjalan'" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-brand-700">
        <Sparkles class="h-3.5 w-3.5 animate-pulse" aria-hidden="true" /> AI sedang menilai permohonan anda
      </p>
    </div>
  </router-link>
</template>
