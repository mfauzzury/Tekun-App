<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  data: { label: string; value: number; warna?: string }[];
}>();

const total = computed(() => props.data.reduce((s, d) => s + d.value, 0));
const conicGradient = computed(() => {
  let acc = 0;
  return props.data
    .map((d) => {
      const pct = (d.value / total.value) * 100;
      const start = acc;
      acc += pct;
      return `${d.warna ?? "#64748b"} ${start}% ${acc}%`;
    })
    .join(", ");
});
</script>

<template>
  <div class="flex flex-wrap items-center gap-4">
    <div
      class="h-32 w-32 shrink-0 rounded-full"
      :style="{ background: `conic-gradient(${conicGradient})` }"
    />
    <div class="min-w-0 flex-1 space-y-1">
      <div v-for="(d, i) in data" :key="i" class="flex items-center gap-2">
        <span class="h-2 w-2 shrink-0 rounded-full" :style="{ background: d.warna ?? '#64748b' }" />
        <span class="text-xs text-slate-600">{{ d.label }}: {{ d.value }}</span>
      </div>
    </div>
  </div>
</template>
