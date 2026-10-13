<script setup lang="ts">
import { Check } from "lucide-vue-next";

defineProps<{
  steps: { id: string; label: string }[];
  currentStep: number;
}>();
</script>

<template>
  <ol class="flex w-full items-start" aria-label="Langkah pendaftaran">
    <template v-for="(step, index) in steps" :key="step.id">
      <li class="flex flex-col items-center gap-2" :aria-current="index === currentStep ? 'step' : undefined">
        <span
          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold ring-4 transition-colors"
          :class="[
            index < currentStep && 'bg-emerald-500 text-white ring-emerald-50',
            index === currentStep && 'bg-brand-600 text-white ring-brand-100',
            index > currentStep && 'border border-slate-200 bg-white text-slate-400 ring-transparent',
          ]"
        >
          <Check v-if="index < currentStep" class="h-4 w-4" stroke-width="3" />
          <span v-else>{{ index + 1 }}</span>
        </span>
        <span
          class="max-w-[7rem] text-center text-xs font-medium leading-tight"
          :class="index === currentStep ? 'text-ink-900' : index < currentStep ? 'text-emerald-700' : 'text-slate-400'"
        >
          {{ step.label }}
        </span>
      </li>
      <li
        v-if="index < steps.length - 1"
        aria-hidden="true"
        class="mx-3 mt-4 h-0.5 flex-1 rounded-full transition-colors"
        :class="index < currentStep ? 'bg-emerald-400' : 'bg-slate-200'"
      />
    </template>
  </ol>
</template>
