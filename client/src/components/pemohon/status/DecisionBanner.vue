<script setup lang="ts">
import { ArrowRight, Info, PartyPopper } from "lucide-vue-next";

withDefaults(defineProps<{ keputusan: "lulus" | "tolak"; mesej?: string | null; compact?: boolean }>(), { mesej: null, compact: false });
</script>

<template>
  <div
    role="status"
    class="flex items-start gap-3 rounded-2xl border"
    :class="[
      compact ? 'p-3.5' : 'p-4 sm:p-5',
      keputusan === 'lulus' ? 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-white' : 'border-slate-200 bg-slate-50',
    ]"
  >
    <span
      class="flex shrink-0 items-center justify-center rounded-xl"
      :class="[compact ? 'h-9 w-9' : 'h-11 w-11', keputusan === 'lulus' ? 'bg-emerald-500 text-white shadow-sm shadow-emerald-500/30' : 'bg-rose-50 text-rose-600']"
    >
      <PartyPopper v-if="keputusan === 'lulus'" :class="compact ? 'h-[18px] w-[18px]' : 'h-5 w-5'" aria-hidden="true" />
      <Info v-else :class="compact ? 'h-[18px] w-[18px]' : 'h-5 w-5'" aria-hidden="true" />
    </span>
    <div class="min-w-0 flex-1">
      <p class="font-semibold" :class="[compact ? 'text-sm' : 'text-[15px]', keputusan === 'lulus' ? 'text-emerald-900' : 'text-ink-900']">
        {{ keputusan === "lulus" ? "Tahniah! Permohonan diluluskan" : "Permohonan tidak berjaya kali ini" }}
      </p>
      <p class="mt-0.5 text-sm" :class="keputusan === 'lulus' ? 'text-emerald-800/80' : 'text-slate-600'">
        {{
          keputusan === "lulus"
            ? "Pegawai TEKUN akan menghubungi anda untuk surat tawaran dan langkah seterusnya."
            : (mesej ?? "Pegawai TEKUN akan menghubungi anda untuk penjelasan lanjut.")
        }}
      </p>
      <router-link
        v-if="keputusan === 'tolak' && !compact"
        to="/pemohon/bantuan"
        class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-ink-800 hover:text-brand-700"
      >
        Hubungi kami <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
      </router-link>
    </div>
  </div>
</template>
