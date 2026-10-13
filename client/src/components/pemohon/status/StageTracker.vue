<script setup lang="ts">
import { computed } from "vue";
import { Check, X } from "lucide-vue-next";

import { TIMELINE_STAGES } from "@/utils/pemohon-status";

/**
 * Horizontal 4-stage tracker: Dihantar → Penilaian AI → Semakan pegawai → Keputusan.
 * `size="sm"` renders a slim segmented bar for list cards.
 */
const props = withDefaults(
  defineProps<{ stage: number; outcome?: "lulus" | "tolak" | null; size?: "sm" | "md" }>(),
  { outcome: null, size: "md" },
);

const last = TIMELINE_STAGES.length - 1;

type State = "done" | "current" | "todo" | "lulus" | "tolak";

const items = computed(() =>
  TIMELINE_STAGES.map((label, i) => {
    let state: State = i < props.stage ? "done" : i === props.stage ? "current" : "todo";
    if (i === last && props.outcome) state = props.outcome;
    return { label, state };
  }),
);

const currentLabel = computed(() => {
  if (props.outcome === "lulus") return "Keputusan: Diluluskan";
  if (props.outcome === "tolak") return "Keputusan: Tidak berjaya";
  return TIMELINE_STAGES[Math.min(Math.max(props.stage, 0), last)];
});

const SR: Record<State, string> = { done: "selesai", current: "sedang berlangsung", todo: "belum bermula", lulus: "diluluskan", tolak: "tidak berjaya" };
</script>

<template>
  <!-- Slim: segmented bar + current label -->
  <div v-if="size === 'sm'">
    <div class="flex gap-1" aria-hidden="true">
      <span
        v-for="(s, i) in items"
        :key="s.label"
        class="h-1.5 flex-1 rounded-full transition-colors"
        :class="{
          'bg-brand-600': s.state === 'done',
          'bg-brand-300': s.state === 'current' && i !== last,
          'bg-emerald-500': s.state === 'lulus',
          'bg-rose-400': s.state === 'tolak',
          'bg-slate-200': s.state === 'todo' || (s.state === 'current' && i === last),
        }"
      />
    </div>
    <p class="mt-1.5 text-xs text-slate-500">
      <span class="sr-only">Peringkat semasa: </span>
      <span class="font-medium text-ink-700">{{ currentLabel }}</span>
      <span class="text-slate-400"> · {{ Math.min(stage + 1, TIMELINE_STAGES.length) }}/{{ TIMELINE_STAGES.length }}</span>
    </p>
  </div>

  <!-- Full: nodes + connectors + labels -->
  <ol v-else class="flex items-start" aria-label="Kemajuan permohonan">
    <li v-for="(s, i) in items" :key="s.label" class="relative flex flex-1 flex-col items-center text-center">
      <span
        v-if="i > 0"
        class="absolute right-1/2 top-4 mr-5 h-0.5 rounded-full"
        :class="s.state === 'todo' || s.state === 'current' ? (s.state === 'current' ? 'bg-brand-300' : 'bg-slate-200') : 'bg-brand-600'"
        style="width: calc(100% - 2.5rem)"
        aria-hidden="true"
      />
      <span
        class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold transition-colors"
        :class="{
          'bg-brand-600 text-white': s.state === 'done',
          'border-2 border-brand-600 bg-white text-brand-700 ring-4 ring-brand-100': s.state === 'current',
          'border border-slate-200 bg-white text-slate-400': s.state === 'todo',
          'bg-emerald-500 text-white ring-4 ring-emerald-100': s.state === 'lulus',
          'bg-rose-500 text-white ring-4 ring-rose-100': s.state === 'tolak',
        }"
      >
        <Check v-if="s.state === 'done' || s.state === 'lulus'" class="h-4 w-4" aria-hidden="true" />
        <X v-else-if="s.state === 'tolak'" class="h-4 w-4" aria-hidden="true" />
        <span v-else-if="s.state === 'current'" class="h-2 w-2 animate-pulse rounded-full bg-brand-600" aria-hidden="true" />
        <span v-else aria-hidden="true">{{ i + 1 }}</span>
      </span>
      <span
        class="mt-2 max-w-[6.5rem] px-0.5 text-[11px] font-medium leading-tight sm:text-xs"
        :class="s.state === 'todo' ? 'text-slate-400' : s.state === 'current' ? 'text-ink-900' : 'text-ink-700'"
      >
        {{ s.label }}
      </span>
      <span class="sr-only">— {{ SR[s.state] }}</span>
    </li>
  </ol>
</template>
