<script setup lang="ts">
import { Search } from "lucide-vue-next";

import { useI18n } from "@/composables/useI18n";

defineProps<{
  searchPlaceholder?: string;
  filterOptions?: { value: string; label: string }[];
  filterLabel?: string;
  q?: string;
  status?: string;
}>();

const emit = defineEmits<{
  "update:q": [value: string];
  "update:status": [value: string];
  filter: [];
}>();

const { t, tp } = useI18n();

function onQInput(e: Event) {
  emit("update:q", (e.target as HTMLInputElement).value);
}

function onStatusChange(e: Event) {
  emit("update:status", (e.target as HTMLSelectElement).value);
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-3">
    <div class="relative">
      <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
      <input
        :value="q ?? ''"
        :placeholder="searchPlaceholder ?? t('common.search')"
        class="w-56 rounded-lg border border-slate-300 py-1.5 pl-9 pr-3 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
        @input="onQInput"
      />
    </div>
    <select
      v-if="filterOptions?.length"
      :value="status ?? ''"
      class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
      @change="onStatusChange"
    >
      <option value="">{{ filterLabel ?? t("common.allStatus") }}</option>
      <option v-for="opt in filterOptions" :key="opt.value" :value="opt.value">{{ tp(opt.label) }}</option>
    </select>
    <button
      class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
      @click="emit('filter')"
    >
      {{ t("common.filter") }}
    </button>
  </div>
</template>
