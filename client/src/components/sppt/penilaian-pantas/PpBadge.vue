<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    tone?: "emerald" | "amber" | "rose" | "slate" | "violet" | "sky";
    size?: "xs" | "sm";
  }>(),
  { tone: "slate", size: "xs" },
);

const toneClass = computed(
  () =>
    ({
      emerald: "bg-emerald-50 text-emerald-700 ring-emerald-200",
      amber: "bg-amber-50 text-amber-800 ring-amber-200",
      rose: "bg-rose-50 text-rose-700 ring-rose-200",
      slate: "bg-slate-100 text-slate-600 ring-slate-200",
      violet: "bg-violet-50 text-violet-700 ring-violet-200",
      sky: "bg-sky-50 text-sky-700 ring-sky-200",
    })[props.tone],
);
</script>

<template>
  <span
    class="inline-flex items-center gap-1 whitespace-nowrap rounded-full font-medium ring-1 ring-inset"
    :class="[toneClass, size === 'sm' ? 'px-2.5 py-0.5 text-xs' : 'px-2 py-0.5 text-[11px]']"
  >
    <slot />
  </span>
</template>
