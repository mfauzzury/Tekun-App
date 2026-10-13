<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch, type Component } from "vue";
import {
  AlertOctagon,
  CheckCircle2,
  Circle,
  Loader2,
  Network,
  Play,
  RotateCcw,
  Route,
  ScanFace,
  ShieldCheck,
  StepForward,
  TrendingUp,
  Workflow,
} from "lucide-vue-next";

import type { PpRun, PpStep } from "@/types";
import PpBadge from "./PpBadge.vue";

const props = withDefaults(
  defineProps<{
    run: PpRun | null;
    /** Step code (snake_case value) currently executing, if any. */
    activeKod?: string | null;
    /** Epoch ms when the active step started (local clock). */
    activeSince?: number | null;
    busy?: boolean;
    canResume?: boolean;
    polling?: boolean;
    readonly?: boolean;
  }>(),
  { activeKod: null, activeSince: null, busy: false, canResume: false, polling: false, readonly: false },
);

const emit = defineEmits<{ (e: "start"): void; (e: "resume"): void }>();

const STEPS: { kod: string; key: string; label: string; desc: string; icon: Component }[] = [
  { kod: "ekyc", key: "ekyc", label: "e-KYC & pengesahan identiti", desc: "OCR MyKad · padanan borang · liveness wajah", icon: ScanFace },
  { kod: "peraturan", key: "peraturan", label: "Semakan peraturan kelayakan", desc: "Peraturan keras TEKUN (umur, warganegara, had)", icon: ShieldCheck },
  { kod: "penipuan", key: "penipuan", label: "Analisis rangkaian penipuan", desc: "Graf entiti dikongsi · kluster permohonan", icon: Network },
  { kod: "skor_kredit", key: "skorKredit", label: "Skor kredit data alternatif", desc: "e-wallet · DuitNow QR · utiliti · platform", icon: TrendingUp },
  { kod: "cadangan", key: "cadangan", label: "Penghalaan & cadangan", desc: "Laluan pantas / semakan pegawai / cadang tolak", icon: Route },
];

function stepOf(key: string, kod: string): PpStep | null {
  const l = props.run?.langkah;
  return (l?.[key] ?? l?.[kod] ?? null) as PpStep | null;
}

const now = ref(Date.now());
let handle: number | null = null;
watch(
  () => props.activeKod,
  (kod) => {
    if (kod && handle === null) handle = window.setInterval(() => (now.value = Date.now()), 100);
    if (!kod && handle !== null) {
      window.clearInterval(handle);
      handle = null;
    }
  },
  { immediate: true },
);
onBeforeUnmount(() => handle !== null && window.clearInterval(handle));

const rows = computed(() =>
  STEPS.map((s) => {
    const step = stepOf(s.key, s.kod);
    const running = props.activeKod === s.kod;
    const status = running ? "berjalan" : (step?.status ?? "menunggu");
    return { ...s, step, status, running };
  }),
);

const doneCount = computed(() => rows.value.filter((r) => r.status === "selesai").length);

function fmtMs(ms?: number | null): string {
  if (typeof ms !== "number" || !Number.isFinite(ms)) return "";
  return ms < 1000 ? `${ms} ms` : `${(ms / 1000).toFixed(1)} s`;
}

function liveMs(): string {
  if (!props.activeSince) return "";
  return `${((now.value - props.activeSince) / 1000).toFixed(1)} s`;
}

function keputusanTone(k?: string | null) {
  return k === "lulus" ? "emerald" : k === "semak" ? "amber" : k === "gagal" ? "rose" : "slate";
}
function keputusanLabel(k?: string | null) {
  return k === "lulus" ? "Lulus" : k === "semak" ? "Perlu semak" : k === "gagal" ? "Gagal" : "—";
}
</script>

<template>
  <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <header class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-2.5">
      <Workflow class="h-4 w-4 text-violet-600" />
      <h3 class="text-sm font-semibold text-slate-900">Saluran penilaian AI</h3>
      <span class="text-xs text-slate-400">{{ doneCount }}/5 langkah</span>
      <span v-if="polling" class="inline-flex items-center gap-1 text-[11px] text-violet-600">
        <Loader2 class="h-3 w-3 animate-spin" /> Dijalankan dari portal pemohon — dikemas kini secara langsung
      </span>
      <div v-if="!readonly" class="ml-auto flex items-center gap-2">
        <button
          v-if="canResume"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50"
          :disabled="busy"
          @click="emit('resume')"
        >
          <StepForward class="h-3.5 w-3.5" /> Teruskan
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-violet-700 disabled:opacity-50"
          :disabled="busy || (run?.status === 'berjalan' && !canResume)"
          @click="emit('start')"
        >
          <Loader2 v-if="busy" class="h-3.5 w-3.5 animate-spin" />
          <RotateCcw v-else-if="run" class="h-3.5 w-3.5" />
          <Play v-else class="h-3.5 w-3.5" />
          {{ run ? "Nilai semula" : "Mulakan penilaian" }}
        </button>
      </div>
    </header>

    <ol class="relative px-4 py-3">
      <li v-for="(r, i) in rows" :key="r.kod" class="relative flex gap-3 pb-3 last:pb-0">
        <div
          v-if="i < rows.length - 1"
          class="absolute left-[17px] top-9 bottom-0 w-px"
          :class="r.status === 'selesai' ? 'bg-emerald-300' : 'bg-slate-200'"
        />
        <div
          class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full ring-4 ring-white"
          :class="{
            'bg-violet-600 text-white': r.status === 'berjalan',
            'bg-emerald-100 text-emerald-700': r.status === 'selesai' && r.step?.keputusan !== 'gagal' && r.step?.keputusan !== 'semak',
            'bg-amber-100 text-amber-700': r.status === 'selesai' && r.step?.keputusan === 'semak',
            'bg-rose-100 text-rose-700': r.status === 'ralat' || (r.status === 'selesai' && r.step?.keputusan === 'gagal'),
            'bg-slate-100 text-slate-400': r.status === 'menunggu',
          }"
        >
          <Loader2 v-if="r.status === 'berjalan'" class="h-4 w-4 animate-spin" />
          <component :is="r.icon" v-else class="h-4 w-4" />
        </div>
        <div class="min-h-9 min-w-0 flex-1 pt-0.5">
          <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <span class="text-[11px] font-semibold text-slate-400">{{ i + 1 }}</span>
            <p class="text-sm font-semibold" :class="r.status === 'menunggu' ? 'text-slate-500' : 'text-slate-900'">
              {{ r.step?.label || r.label }}
            </p>
            <PpBadge v-if="r.status === 'selesai'" :tone="keputusanTone(r.step?.keputusan)">
              <CheckCircle2 class="h-3 w-3" /> {{ keputusanLabel(r.step?.keputusan) }}
            </PpBadge>
            <PpBadge v-else-if="r.status === 'ralat'" tone="rose"><AlertOctagon class="h-3 w-3" /> Ralat</PpBadge>
            <PpBadge v-else-if="r.status === 'berjalan'" tone="violet">Sedang berjalan…</PpBadge>
            <PpBadge v-else tone="slate"><Circle class="h-2.5 w-2.5" /> Menunggu</PpBadge>
            <span class="ml-auto font-mono text-xs tabular-nums text-slate-500">
              {{ r.status === "berjalan" ? liveMs() : fmtMs(r.step?.ms) }}
            </span>
          </div>
          <p class="mt-0.5 text-xs" :class="r.status === 'ralat' ? 'text-rose-600' : 'text-slate-500'">
            {{ r.step?.ringkasan || r.desc }}
          </p>
        </div>
      </li>
    </ol>
  </article>
</template>
