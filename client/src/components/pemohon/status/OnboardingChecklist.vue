<script setup lang="ts">
import { computed } from "vue";
import { Check, ChevronRight } from "lucide-vue-next";

import PCard from "@/components/pemohon/ui/PCard.vue";
import { usePemohonStore } from "@/stores/pemohon";

const pemohon = usePemohonStore();

const steps = computed(() => {
  const ob = pemohon.onboarding;
  const eligibilityDone = ob.eligibilityChecked && !!ob.eligibilityResult;
  return [
    {
      key: "kelayakan",
      label: "Semakan kelayakan",
      to: "/pemohon/semakan-kelayakan",
      done: eligibilityDone,
      hint: !eligibilityDone ? "2 minit · tanpa dokumen" : ob.eligibilityResult === "lulus" ? "Anda layak memohon" : "Semak semula kelayakan anda",
      warn: eligibilityDone && ob.eligibilityResult === "gagal",
    },
    {
      key: "ekyc",
      label: "Pengesahan identiti e-KYC",
      to: "/pemohon/ekyc",
      done: ob.ekycVerified,
      hint: ob.ekycVerified ? "Identiti disahkan" : "MyKad + swafoto",
      warn: false,
    },
    {
      key: "hantar",
      label: "Hantar permohonan",
      to: pemohon.permohonanSaya.length ? "/pemohon/permohonan" : "/pemohon/permohonan/baru",
      done: pemohon.permohonanSaya.length > 0,
      hint: pemohon.permohonanSaya.length ? `${pemohon.permohonanSaya.length} permohonan dihantar` : "Lengkapkan borang dalam talian",
      warn: false,
    },
  ];
});

const doneCount = computed(() => steps.value.filter((s) => s.done).length);
const total = computed(() => steps.value.length);
const RADIUS = 20;
const CIRC = 2 * Math.PI * RADIUS;
const dash = computed(() => `${(doneCount.value / total.value) * CIRC} ${CIRC}`);
</script>

<template>
  <PCard>
    <template #header>
      <div class="flex w-full items-center gap-3.5">
        <div class="relative h-12 w-12 shrink-0" role="img" :aria-label="`${doneCount} daripada ${total} langkah selesai`">
          <svg viewBox="0 0 48 48" class="h-12 w-12 -rotate-90">
            <circle cx="24" cy="24" :r="RADIUS" fill="none" stroke-width="5" class="stroke-slate-100" />
            <circle
              cx="24"
              cy="24"
              :r="RADIUS"
              fill="none"
              stroke-width="5"
              stroke-linecap="round"
              :stroke-dasharray="dash"
              class="transition-[stroke-dasharray] duration-700"
              :class="doneCount === total ? 'stroke-emerald-500' : 'stroke-brand-600'"
            />
          </svg>
          <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-ink-900">{{ doneCount }}/{{ total }}</span>
        </div>
        <div class="min-w-0">
          <h2 class="text-[15px] font-semibold text-ink-900">Langkah persediaan</h2>
          <p class="text-sm text-slate-500">{{ doneCount === total ? "Semua langkah selesai" : `${doneCount}/${total} selesai` }}</p>
        </div>
      </div>
    </template>

    <ul class="-mx-2 space-y-1">
      <li v-for="(s, i) in steps" :key="s.key">
        <router-link :to="s.to" class="group flex items-center gap-3 rounded-xl px-2 py-2.5 transition-colors hover:bg-slate-50">
          <span
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
            :class="s.done ? (s.warn ? 'bg-amber-500 text-white' : 'bg-emerald-500 text-white') : 'border border-slate-300 bg-white text-slate-500'"
          >
            <Check v-if="s.done" class="h-3.5 w-3.5" aria-hidden="true" />
            <span v-else aria-hidden="true">{{ i + 1 }}</span>
          </span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium" :class="s.done ? 'text-ink-700' : 'text-ink-900'">
              {{ s.label }}<span class="sr-only">{{ s.done ? " — selesai" : " — belum selesai" }}</span>
            </span>
            <span class="block truncate text-xs" :class="s.warn ? 'text-amber-700' : 'text-slate-500'">{{ s.hint }}</span>
          </span>
          <ChevronRight class="h-4 w-4 shrink-0 text-slate-300 transition-colors group-hover:text-slate-500" aria-hidden="true" />
        </router-link>
      </li>
    </ul>
  </PCard>
</template>
