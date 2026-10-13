<script setup lang="ts">
import { computed } from "vue";
import { ArrowRight, CloudOff, FileText, Plus, RefreshCw } from "lucide-vue-next";

import AiProgressTracker from "@/components/pemohon/AiProgressTracker.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { formatRinggit } from "@/utils/pemohon-ui";
import DecisionBanner from "./DecisionBanner.vue";
import StageTracker from "./StageTracker.vue";
import { formatTarikh, useApplicationStatus } from "./useApplicationStatus";

const pemohon = usePemohonStore();
const latest = computed(() => pemohon.permohonanSaya[0] ?? null);
const { snapshot, loading, failed, view, refresh } = useApplicationStatus(latest);

const firstLoad = computed(() => loading.value && !snapshot.value);
const produk = computed(() => snapshot.value?.record?.kategoriPembiayaan || latest.value?.produk || "Pembiayaan");
const jumlah = computed(() => snapshot.value?.record?.jumlahPermohonan ?? latest.value?.jumlah ?? 0);
</script>

<template>
  <PCard title="Permohonan terkini" :subtitle="latest ? undefined : 'Jejak status permohonan anda di sini'">
    <template v-if="latest" #actions>
      <router-link
        :to="`/pemohon/permohonan/${latest.id}`"
        class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800"
      >
        Lihat butiran <ArrowRight class="h-4 w-4" aria-hidden="true" />
      </router-link>
    </template>

    <PEmptyState
      v-if="!latest"
      :icon="FileText"
      title="Belum ada permohonan"
      description="Mohon pembiayaan TEKUN dalam beberapa minit. Penilaian awal dibuat secara automatik oleh AI."
    >
      <PButton to="/pemohon/permohonan/baru"><Plus class="h-4 w-4" /> Mohon pembiayaan</PButton>
    </PEmptyState>

    <div v-else class="space-y-5" :aria-busy="loading">
      <!-- Summary -->
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="font-mono text-xs font-medium tracking-wide text-slate-500">{{ latest.noRujukan }}</p>
          <p class="mt-0.5 truncate text-base font-semibold text-ink-900">{{ produk }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight text-ink-900">{{ formatRinggit(jumlah) }}</p>
          <p class="mt-0.5 text-xs text-slate-500">Dihantar {{ formatTarikh(latest.dihantarPada) }}</p>
        </div>
        <span v-if="firstLoad" class="h-6 w-24 animate-pulse rounded-full bg-slate-200/70" aria-hidden="true" />
        <PBadge v-else :tone="view.tone" dot size="md">{{ view.statusLabel }}</PBadge>
      </div>

      <!-- Progress -->
      <div class="rounded-2xl bg-slate-50/80 px-2 py-4 sm:px-4">
        <div v-if="firstLoad" class="flex animate-pulse items-start justify-between px-4" aria-hidden="true">
          <span v-for="i in 4" :key="i" class="h-8 w-8 rounded-full bg-slate-200/80" />
        </div>
        <StageTracker v-else :stage="view.stage" :outcome="view.keputusan" />
      </div>

      <div v-if="failed" class="flex items-center justify-between gap-3 rounded-xl bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800" role="status">
        <span class="flex items-center gap-2"><CloudOff class="h-4 w-4 shrink-0" aria-hidden="true" /> Status tidak dapat dikemas kini</span>
        <button type="button" class="inline-flex items-center gap-1 font-semibold hover:text-amber-950" @click="refresh">
          <RefreshCw class="h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" aria-hidden="true" /> Cuba lagi
        </button>
      </div>

      <div v-if="view.aiState === 'berjalan'" class="rounded-2xl border border-brand-100 bg-brand-50/40 p-4">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-brand-700">Penilaian AI sedang berjalan</p>
        <AiProgressTracker :permohonan-id="latest.id" :token="latest.token" compact @done="refresh" />
      </div>

      <DecisionBanner v-if="view.keputusan" :keputusan="view.keputusan" :mesej="view.mesej" compact />
      <p v-else-if="view.aiState === 'selesai'" class="text-sm text-slate-500">
        Penilaian AI selesai. Permohonan anda kini disemak oleh pegawai TEKUN.
      </p>
    </div>
  </PCard>
</template>
