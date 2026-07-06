<script setup lang="ts">
import { Check } from "lucide-vue-next";

defineProps<{
  steps: { id: string; label: string }[];
  currentStep: number;
}>();
</script>

<template>
  <div class="flex w-full items-start">
    <template v-for="(step, index) in steps" :key="step.id">
      <div class="flex flex-col items-center gap-1.5">
        <span
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors"
          :class="[
            index < currentStep && 'bg-emerald-600 text-white',
            index === currentStep && 'border-2 border-blue-600 text-blue-600',
            index > currentStep && 'border border-slate-200 text-slate-400',
          ]"
        >
          <Check v-if="index < currentStep" class="h-3.5 w-3.5" />
          <span v-else>{{ index + 1 }}</span>
        </span>
        <span
          class="max-w-[6rem] text-center text-[11px] font-medium leading-tight"
          :class="index === currentStep ? 'text-slate-900' : 'text-slate-400'"
        >
          {{ step.label }}
        </span>
      </div>
      <div
        v-if="index < steps.length - 1"
        class="mx-2 mt-3.5 h-px flex-1"
        :class="index < currentStep ? 'bg-emerald-300' : 'bg-slate-200'"
      />
    </template>
  </div>
</template>
