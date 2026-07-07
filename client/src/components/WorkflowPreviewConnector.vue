<script setup lang="ts">
defineProps<{
  label?: string;
  variant?: "default" | "positive" | "negative";
}>();
</script>

<template>
  <div class="flex flex-col items-center">
    <div
      class="h-6 w-px"
      :class="{
        'bg-slate-300': !variant || variant === 'default',
        'bg-green-400': variant === 'positive',
        'bg-red-400':   variant === 'negative',
      }"
    />
    <span
      v-if="label"
      class="rounded px-1.5 py-0.5 text-xs font-medium"
      :class="{
        'bg-slate-100 text-slate-600':  !variant || variant === 'default',
        'bg-green-100 text-green-700':  variant === 'positive',
        'bg-red-100 text-red-600':      variant === 'negative',
      }"
    >
      {{ label }}
    </span>
    <svg width="12" height="8" viewBox="0 0 12 8" class="mt-0.5">
      <path
        d="M6 8 L0 0 L12 0 Z"
        :fill="variant === 'positive' ? '#4ade80' : variant === 'negative' ? '#f87171' : '#94a3b8'"
      />
    </svg>
  </div>
</template>
