<script setup lang="ts">
import type { Component } from "vue";
import type { PTone } from "@/utils/pemohon-status";

withDefaults(defineProps<{ label: string; value: string | number; hint?: string; icon?: Component; tone?: PTone }>(), {
  tone: "neutral",
});
</script>

<template>
  <div class="rounded-3xl border border-slate-200 bg-white p-5">
    <div class="flex items-center gap-2.5">
      <span
        v-if="icon"
        class="flex h-9 w-9 items-center justify-center rounded-xl"
        :class="{
          neutral: 'bg-slate-100 text-slate-600',
          brand: 'bg-brand-50 text-brand-600',
          success: 'bg-emerald-50 text-emerald-600',
          warning: 'bg-amber-50 text-amber-600',
          danger: 'bg-rose-50 text-rose-600',
          info: 'bg-blue-50 text-blue-600',
        }[tone]"
      >
        <component :is="icon" class="h-[18px] w-[18px]" />
      </span>
      <p class="text-sm font-medium text-slate-500">{{ label }}</p>
    </div>
    <p class="mt-3 text-[28px] font-bold leading-none tracking-tight text-ink-950">{{ value }}</p>
    <p v-if="hint" class="mt-0.5 text-xs text-slate-500">{{ hint }}</p>
  </div>
</template>
