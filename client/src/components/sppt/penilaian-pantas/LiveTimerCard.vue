<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { CheckCircle2, Cpu, Info, Timer, TimerOff } from "lucide-vue-next";

import type { PpRun } from "@/types";

const props = withDefaults(
  defineProps<{
    run: PpRun | null;
    dihantarPada?: string | null;
    sasaranMs?: number;
  }>(),
  { dihantarPada: null, sasaranMs: 420000 },
);

const now = ref(Date.now());
let handle: number | null = null;

const ticking = computed(() => props.run?.status === "berjalan");

function startTick() {
  if (handle !== null) return;
  handle = window.setInterval(() => (now.value = Date.now()), 100);
}
function stopTick() {
  if (handle !== null) window.clearInterval(handle);
  handle = null;
  now.value = Date.now();
}

watch(ticking, (v) => (v ? startTick() : stopTick()), { immediate: true });
onBeforeUnmount(stopTick);

function ts(iso?: string | null): number | null {
  if (!iso) return null;
  const t = Date.parse(iso);
  return Number.isFinite(t) ? t : null;
}

const startMs = computed(() => ts(props.run?.dihantarPada) ?? ts(props.dihantarPada) ?? ts(props.run?.mulaPada));
const mulaMs = computed(() => ts(props.run?.mulaPada));

const e2eMs = computed<number | null>(() => {
  const run = props.run;
  if (!run) return null;
  if (run.status === "selesai") {
    if (typeof run.hujungKeHujungMs === "number") return run.hujungKeHujungMs;
    const siap = ts(run.siapPada);
    return siap !== null && startMs.value !== null ? Math.max(0, siap - startMs.value) : null;
  }
  return startMs.value !== null ? Math.max(0, now.value - startMs.value) : null;
});

const prosesMs = computed<number | null>(() => {
  const run = props.run;
  if (!run) return null;
  if (run.status === "selesai" && typeof run.jumlahMs === "number") return run.jumlahMs;
  const steps = Object.values(run.langkah ?? {});
  const done = steps.reduce((sum, s) => sum + (typeof s?.ms === "number" ? s.ms : 0), 0);
  return done;
});

const larianMs = computed<number | null>(() => {
  const run = props.run;
  if (!run || mulaMs.value === null) return null;
  const end = run.status === "selesai" ? ts(run.siapPada) : now.value;
  return end !== null ? Math.max(0, end - mulaMs.value) : null;
});

const sasaran = computed(() => props.sasaranMs || 420000);
const withinTarget = computed(() => e2eMs.value !== null && e2eMs.value <= sasaran.value);
const pct = computed(() => (e2eMs.value === null ? 0 : Math.min(100, (e2eMs.value / sasaran.value) * 100)));

/** Officer re-ran an older submission — E2E includes the waiting time before the run started. */
const rerunLama = computed(() => {
  const d = startMs.value;
  const m = mulaMs.value;
  return d !== null && m !== null && m - d > sasaran.value;
});

function fmt(ms: number | null): string {
  if (ms === null || !Number.isFinite(ms)) return "--:--.-";
  if (ms >= 86400000) {
    const d = Math.floor(ms / 86400000);
    const h = Math.floor((ms % 86400000) / 3600000);
    return `${d} hari ${h} jam`;
  }
  if (ms >= 3600000) {
    const h = Math.floor(ms / 3600000);
    const m = Math.floor((ms % 3600000) / 60000);
    return `${h} jam ${m} min`;
  }
  const totalTenths = Math.floor(ms / 100);
  const m = Math.floor(totalTenths / 600);
  const s = Math.floor((totalTenths % 600) / 10);
  const t = totalTenths % 10;
  return `${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}.${t}`;
}
</script>

<template>
  <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="grid gap-4 p-4 sm:grid-cols-[1fr_auto] sm:items-center">
      <div class="min-w-0">
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
          <Timer v-if="run?.status !== 'selesai'" class="h-4 w-4 text-violet-600" />
          <TimerOff v-else class="h-4 w-4 text-slate-500" />
          Masa hujung-ke-hujung (Hantar → cadangan)
        </div>
        <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
          <span
            class="font-mono text-4xl font-bold tabular-nums tracking-tight sm:text-5xl"
            :class="!run ? 'text-slate-300' : withinTarget ? 'text-emerald-600' : 'text-rose-600'"
          >
            {{ fmt(e2eMs) }}
          </span>
          <span class="text-sm text-slate-500">/ sasaran <span class="font-mono font-semibold text-slate-700">07:00</span></span>
          <span
            v-if="run?.status === 'selesai' && withinTarget"
            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200"
          >
            <CheckCircle2 class="h-3.5 w-3.5" /> Dalam sasaran
          </span>
          <span
            v-else-if="run?.status === 'berjalan'"
            class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-violet-200"
          >
            <span class="relative flex h-2 w-2">
              <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-violet-400 opacity-75" />
              <span class="relative inline-flex h-2 w-2 rounded-full bg-violet-600" />
            </span>
            Sedang dinilai
          </span>
        </div>
        <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
          <div
            class="h-full rounded-full transition-[width] duration-100"
            :class="withinTarget || !run ? 'bg-emerald-500' : 'bg-rose-500'"
            :style="{ width: `${pct}%` }"
          />
        </div>
        <p v-if="!run" class="mt-2 text-xs text-slate-500">Belum dinilai. Tekan “Mulakan penilaian” untuk memulakan enjin.</p>
        <p v-else-if="rerunLama" class="mt-2 flex items-start gap-1.5 text-xs text-slate-500">
          <Info class="mt-0.5 h-3.5 w-3.5 shrink-0" />
          Permohonan dihantar lebih awal dan dinilai semula oleh pegawai — masa ini termasuk tempoh menunggu sebelum penilaian.
        </p>
      </div>
      <div class="grid grid-cols-2 gap-2 sm:w-56 sm:grid-cols-1">
        <div class="rounded-lg bg-slate-50 px-3 py-2">
          <p class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
            <Cpu class="h-3 w-3" /> Masa pemprosesan AI
          </p>
          <p class="font-mono text-lg font-bold tabular-nums text-slate-900">{{ fmt(prosesMs) }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 px-3 py-2">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Tempoh larian enjin</p>
          <p class="font-mono text-lg font-bold tabular-nums text-slate-900">{{ fmt(larianMs) }}</p>
        </div>
      </div>
    </div>
  </article>
</template>
