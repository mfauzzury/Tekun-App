<script setup lang="ts">
import { Check } from "lucide-vue-next";

defineProps<{
  steps: { id: string; label: string }[];
  currentStep: number;
  /** Indices of steps with validation errors */
  stepsWithErrors?: number[];
}>();

defineEmits<{
  (e: "step-click", index: number): void;
}>();
</script>

<template>
  <nav class="w-full min-w-[720px]" aria-label="Progress">
    <div class="flex items-center justify-between">
      <template v-for="(step, index) in steps" :key="step.id">
        <button
          type="button"
          class="flex flex-col items-center gap-1.5 rounded-lg px-1 py-2 -m-1 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 cursor-pointer hover:opacity-90"
          @click="$emit('step-click', index)"
        >
          <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors"
            :class="[
              stepsWithErrors?.includes(index) && 'border-2 border-rose-500 bg-rose-50 text-rose-600',
              !stepsWithErrors?.includes(index) && index < currentStep && 'bg-emerald-600 text-white',
              !stepsWithErrors?.includes(index) && index === currentStep && 'border-2 border-slate-900 bg-white text-slate-900',
              !stepsWithErrors?.includes(index) && index > currentStep && 'border border-slate-200 bg-slate-50 text-slate-400',
            ]"
          >
            <Check v-if="index < currentStep && !stepsWithErrors?.includes(index)" class="h-4 w-4" />
            <span v-else>{{ index + 1 }}</span>
          </span>
          <span
            class="text-center text-xs font-medium max-w-[4.5rem] leading-tight"
            :class="[
              index < currentStep && 'text-emerald-700',
              index === currentStep && 'text-slate-900',
              index > currentStep && 'text-slate-400',
            ]"
          >
            {{ step.label }}
          </span>
        </button>
        <div
          v-if="index < steps.length - 1"
          class="mx-0.5 h-px flex-1 min-w-[4px] transition-colors"
          :class="[
            index < currentStep ? 'bg-emerald-300' : 'bg-slate-200',
          ]"
          aria-hidden="true"
        />
      </template>
    </div>
  </nav>
</template>
