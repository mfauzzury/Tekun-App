<script setup lang="ts">
import type { Component } from "vue";

defineProps<{ icon?: Component; title: string; description?: string }>();
</script>

<template>
  <div class="flex flex-col items-center px-6 py-10 text-center">
    <span v-if="icon" class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
      <component :is="icon" class="h-6 w-6" />
    </span>
    <p class="text-[15px] font-semibold text-ink-900">{{ title }}</p>
    <p v-if="description" class="mt-1 max-w-sm text-sm text-slate-500">{{ description }}</p>
    <div v-if="$slots.default" class="mt-5"><slot /></div>
  </div>
</template>
