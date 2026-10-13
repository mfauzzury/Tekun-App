<script lang="ts">
/** Generic milestone. `{ label, done }` (StatusMilestone from portal-dummy) is a valid subset. */
export interface TimelineMilestone {
  label: string;
  done: boolean;
  /** Slot name for custom content under this milestone (e.g. the live AI tracker). */
  key?: string;
  /** Force this milestone as the active one; otherwise the first not-done milestone is active. */
  current?: boolean;
  description?: string | null;
  date?: string | null;
  tone?: "success" | "danger";
}
</script>

<script setup lang="ts">
import { computed } from "vue";
import { Check, X } from "lucide-vue-next";

const props = defineProps<{ milestones: TimelineMilestone[] }>();

const currentIndex = computed(() => {
  const forced = props.milestones.findIndex((m) => m.current);
  return forced >= 0 ? forced : props.milestones.findIndex((m) => !m.done);
});

function state(m: TimelineMilestone, i: number): "done" | "current" | "todo" | "success" | "danger" {
  if (m.done && m.tone) return m.tone;
  if (m.done) return "done";
  return i === currentIndex.value ? "current" : "todo";
}
</script>

<template>
  <ol class="relative" aria-label="Garis masa permohonan">
    <li v-for="(m, i) in milestones" :key="m.key ?? m.label" class="relative flex gap-4 pb-7 last:pb-0">
      <span
        v-if="i < milestones.length - 1"
        class="absolute left-[15px] top-9 bottom-1 w-0.5 rounded-full"
        :class="m.done ? 'bg-brand-200' : 'bg-slate-200'"
        aria-hidden="true"
      />
      <span
        class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
        :class="{
          'bg-brand-600 text-white': state(m, i) === 'done',
          'bg-emerald-500 text-white ring-4 ring-emerald-100': state(m, i) === 'success',
          'bg-rose-500 text-white ring-4 ring-rose-100': state(m, i) === 'danger',
          'border-2 border-brand-600 bg-white ring-4 ring-brand-100': state(m, i) === 'current',
          'border border-slate-200 bg-white text-slate-400': state(m, i) === 'todo',
        }"
      >
        <X v-if="state(m, i) === 'danger'" class="h-4 w-4" aria-hidden="true" />
        <Check v-else-if="m.done" class="h-4 w-4" aria-hidden="true" />
        <span v-else-if="state(m, i) === 'current'" class="h-2 w-2 animate-pulse rounded-full bg-brand-600" aria-hidden="true" />
        <span v-else aria-hidden="true">{{ i + 1 }}</span>
      </span>

      <div class="min-w-0 flex-1 pt-1">
        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
          <p
            class="text-sm font-semibold"
            :class="state(m, i) === 'todo' ? 'text-slate-400' : state(m, i) === 'danger' ? 'text-rose-700' : state(m, i) === 'success' ? 'text-emerald-700' : 'text-ink-900'"
          >
            {{ m.label }}
            <span class="sr-only">— {{ m.done ? "selesai" : i === currentIndex ? "sedang berlangsung" : "belum bermula" }}</span>
          </p>
          <p v-if="m.date" class="text-xs tabular-nums text-slate-400">{{ m.date }}</p>
        </div>
        <p v-if="m.description" class="mt-0.5 text-sm" :class="state(m, i) === 'todo' ? 'text-slate-400' : 'text-slate-500'">{{ m.description }}</p>
        <div v-if="m.key && $slots[m.key]" class="mt-3">
          <slot :name="m.key" />
        </div>
      </div>
    </li>
  </ol>
</template>
