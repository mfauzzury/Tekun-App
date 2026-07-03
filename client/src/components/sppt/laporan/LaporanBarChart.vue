<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";

const { t, tp } = useI18n();

defineProps<{
  data: { label: string; value: number; value2?: number }[];
  label1?: string;
  label2?: string;
  max?: number;
}>();
</script>

<template>
  <div class="space-y-2">
    <div v-for="(row, i) in data" :key="i" class="flex items-center gap-2">
      <span class="w-12 shrink-0 text-xs text-slate-600">{{ row.label }}</span>
      <div class="min-w-0 flex-1">
        <div class="flex gap-1">
          <div
            class="h-5 rounded bg-slate-400 transition-all"
            :style="{ width: `${Math.min(50, (row.value / (max ?? 350)) * 50)}%` }"
            :title="`${label1 ?? tp('Kutipan')}: ${row.value}`"
          />
          <div
            v-if="row.value2 != null"
            class="h-5 rounded bg-slate-600 transition-all"
            :style="{ width: `${Math.min(50, (row.value2 / (max ?? 350)) * 50)}%` }"
            :title="`${label2 ?? tp('Sasaran')}: ${row.value2}`"
          />
        </div>
      </div>
      <span class="w-20 shrink-0 text-right text-xs text-slate-600">{{ row.value }}{{ row.value2 != null ? ` / ${row.value2}` : "" }}</span>
    </div>
  </div>
</template>
