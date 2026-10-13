<script setup lang="ts">
import type { PTone } from "@/utils/pemohon-status";

withDefaults(defineProps<{ tone?: PTone; dot?: boolean; size?: "sm" | "md" }>(), { tone: "neutral", dot: false, size: "sm" });
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full font-medium ring-1 ring-inset"
    :class="[
      size === 'sm' ? 'px-2.5 py-0.5 text-xs' : 'px-3 py-1 text-sm',
      {
        neutral: 'bg-slate-100 text-slate-700 ring-slate-200',
        brand: 'bg-brand-50 text-brand-700 ring-brand-200',
        success: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        warning: 'bg-amber-50 text-amber-800 ring-amber-200',
        danger: 'bg-rose-50 text-rose-700 ring-rose-200',
        info: 'bg-blue-50 text-blue-700 ring-blue-200',
      }[tone],
    ]"
  >
    <span
      v-if="dot"
      class="h-1.5 w-1.5 rounded-full"
      :class="{
        neutral: 'bg-slate-400',
        brand: 'bg-brand-500',
        success: 'bg-emerald-500',
        warning: 'bg-amber-500',
        danger: 'bg-rose-500',
        info: 'bg-blue-500',
      }[tone]"
    />
    <slot />
  </span>
</template>
