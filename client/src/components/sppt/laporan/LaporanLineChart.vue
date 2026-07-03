<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  data: { label: string; value: number }[];
  max?: number;
  color?: string;
}>();

const points = computed(() => {
  const max = props.max ?? Math.max(...props.data.map((d) => d.value));
  const w = 100 / (props.data.length - 1 || 1);
  return props.data
    .map((d, i) => {
      const x = i * w;
      const y = 100 - (d.value / max) * 80;
      return `${x},${y}`;
    })
    .join(" ");
});
</script>

<template>
  <div class="relative h-40 w-full">
    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
      <polyline
        fill="none"
        :stroke="color ?? '#3b82f6'"
        stroke-width="1.5"
        :points="points"
      />
    </svg>
  </div>
</template>
