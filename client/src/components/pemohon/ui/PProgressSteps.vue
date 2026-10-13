<script setup lang="ts">
import { Check } from "lucide-vue-next";

const props = withDefaults(
  defineProps<{ steps: readonly { id: string; label: string }[]; current: number; errors?: number[]; clickable?: boolean }>(),
  { errors: () => [], clickable: true },
);
const emit = defineEmits<{ select: [index: number] }>();

function onSelect(i: number) {
  if (props.clickable && i <= props.current) emit("select", i);
}
</script>

<template>
  <div>
    <!-- Mobile: compact -->
    <div class="sm:hidden">
      <div class="flex items-center justify-between text-sm">
        <span class="font-medium text-slate-500">Langkah {{ current + 1 }} daripada {{ steps.length }}</span>
        <span class="font-semibold text-ink-900">{{ steps[current]?.label }}</span>
      </div>
      <div class="mt-2 flex gap-1">
        <span
          v-for="(s, i) in steps"
          :key="s.id"
          class="h-1.5 flex-1 rounded-full transition-colors"
          :class="errors.includes(i) ? 'bg-rose-400' : i <= current ? 'bg-brand-600' : 'bg-slate-200'"
        />
      </div>
    </div>

    <!-- Desktop: full -->
    <ol class="hidden items-start sm:flex">
      <li v-for="(s, i) in steps" :key="s.id" class="flex flex-1 items-start last:flex-none">
        <button
          type="button"
          class="group flex flex-col items-center gap-2 text-center"
          :class="clickable && i <= current ? 'cursor-pointer' : 'cursor-default'"
          @click="onSelect(i)"
        >
          <span
            class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold ring-4 transition-colors"
            :class="
              errors.includes(i)
                ? 'bg-rose-600 text-white ring-rose-100'
                : i < current
                  ? 'bg-brand-600 text-white ring-brand-100'
                  : i === current
                    ? 'bg-white text-brand-700 ring-brand-200 border-2 border-brand-600'
                    : 'bg-white text-slate-400 ring-slate-100 border border-slate-200'
            "
          >
            <Check v-if="i < current && !errors.includes(i)" class="h-4 w-4" />
            <template v-else>{{ i + 1 }}</template>
          </span>
          <span
            class="max-w-[7.5rem] text-xs font-medium leading-tight"
            :class="i === current ? 'text-ink-900' : i < current ? 'text-ink-700' : 'text-slate-400'"
          >
            {{ s.label }}
          </span>
        </button>
        <span v-if="i < steps.length - 1" class="mx-2 mt-[18px] h-0.5 flex-1 rounded-full" :class="i < current ? 'bg-brand-600' : 'bg-slate-200'" />
      </li>
    </ol>
  </div>
</template>
