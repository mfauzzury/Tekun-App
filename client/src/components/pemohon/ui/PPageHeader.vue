<script setup lang="ts">
import { ArrowLeft } from "lucide-vue-next";
import type { RouteLocationRaw } from "vue-router";

defineProps<{ title: string; subtitle?: string; eyebrow?: string; back?: RouteLocationRaw; backLabel?: string }>();
</script>

<template>
  <div class="flex flex-col gap-4 pb-1 pt-6 sm:flex-row sm:items-end sm:justify-between lg:pt-2">
    <div class="min-w-0">
      <router-link
        v-if="back"
        :to="back"
        class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-ink-900"
      >
        <ArrowLeft class="h-4 w-4" />
        {{ backLabel ?? "Kembali" }}
      </router-link>
      <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-wider text-brand-600">{{ eyebrow }}</p>
      <h1 class="text-[28px] font-bold tracking-tight text-ink-950 sm:text-[32px]">{{ title }}</h1>
      <p v-if="subtitle" class="mt-1 max-w-2xl text-sm text-slate-500 sm:text-[15px]">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2"><slot name="actions" /></div>
  </div>
</template>
