<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Check, CircleAlert, Loader2, Sparkles } from "lucide-vue-next";

import { getPemohonPenilaianPantas, runPemohonPenilaianLangkah, startPemohonPenilaianPantas } from "@/api/pemohonPermohonan";
import type { PemohonAiProgress } from "@/types";

/**
 * Applicant-facing live view of the AI assessment (progress only — no scores or routing).
 * With `autoStart`, it starts the pipeline (right after Hantar) and polls until done.
 */
const props = withDefaults(
  defineProps<{ permohonanId: number; token: string; autoStart?: boolean; compact?: boolean }>(),
  { autoStart: false, compact: false },
);
const emit = defineEmits<{ done: [progress: PemohonAiProgress] }>();

const LANGKAH_PEMOHON: Record<string, string> = {
  ekyc: "Pengesahan identiti (e-KYC)",
  peraturan: "Semakan kelayakan",
  penipuan: "Semakan keselamatan",
  skor_kredit: "Penilaian kemampuan bayar",
  cadangan: "Penyediaan cadangan kepada pegawai",
};

const progress = ref<PemohonAiProgress | null>(null);
const error = ref("");
const startedAt = ref(Date.now());
const now = ref(Date.now());
let poll: number | undefined;
let tick: number | undefined;

const steps = computed(() => {
  const fromServer = progress.value?.langkah ?? [];
  return Object.entries(LANGKAH_PEMOHON).map(([kod, label]) => {
    const s = fromServer.find((l) => l.kod === kod);
    return { kod, label, status: s?.status ?? "menunggu", ms: s?.ms ?? null };
  });
});

const running = computed(() =>
  progress.value ? progress.value.status === "berjalan" || (props.autoStart && progress.value.status === "tiada") : props.autoStart,
);
const doneCount = computed(() => steps.value.filter((s) => s.status === "selesai").length);
const activeIndex = computed(() => steps.value.findIndex((s) => s.status === "menunggu"));
const elapsed = computed(() => {
  const ms = progress.value?.status === "selesai" && progress.value.jumlahMs != null ? progress.value.jumlahMs : now.value - startedAt.value;
  return (ms / 1000).toFixed(1);
});

function fmtMs(ms: number | null) {
  if (ms == null) return "";
  return ms < 1000 ? `${ms} ms` : `${(ms / 1000).toFixed(1)} s`;
}

async function refresh() {
  try {
    const res = await getPemohonPenilaianPantas(props.permohonanId, props.token);
    progress.value = res.data;
    if (res.data.status === "selesai") finish();
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Status tidak dapat dimuatkan";
  }
}

function finish() {
  window.clearInterval(poll);
  window.clearInterval(tick);
  poll = tick = undefined;
  if (progress.value) emit("done", progress.value);
}

let driving = false;
let unmounted = false;

/** Drive the pipeline one step at a time so each tick appears as it really completes. */
async function drive() {
  driving = true;
  startedAt.value = Date.now();
  try {
    for (const kod of Object.keys(LANGKAH_PEMOHON)) {
      if (unmounted) return;
      const res = await runPemohonPenilaianLangkah(props.permohonanId, props.token, kod);
      progress.value = res.data;
      if (res.data.langkah.find((l) => l.kod === kod)?.status === "ralat") break;
    }
    if (progress.value?.status === "selesai") finish();
  } catch {
    // Fall back to the single server-side run, then poll for its result.
    startPemohonPenilaianPantas(props.permohonanId, props.token).catch(() => undefined);
    poll = window.setInterval(refresh, 2000);
  } finally {
    driving = false;
  }
}

onMounted(async () => {
  tick = window.setInterval(() => (now.value = Date.now()), 100);
  if (props.autoStart) {
    void drive();
    return;
  }
  await refresh();
  if (progress.value?.status === "berjalan") {
    poll = window.setInterval(refresh, 2000);
  } else {
    window.clearInterval(tick);
    tick = undefined;
  }
});

onBeforeUnmount(() => {
  unmounted = true;
  window.clearInterval(poll);
  window.clearInterval(tick);
  // Applicant navigated away mid-run: let the server finish the remaining steps.
  if (driving && progress.value?.status !== "selesai") {
    startPemohonPenilaianPantas(props.permohonanId, props.token).catch(() => undefined);
  }
});
</script>

<template>
  <div>
    <div v-if="!compact" class="mb-4 flex items-center justify-between gap-3">
      <div class="flex items-center gap-2.5">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
          <Loader2 v-if="running" class="h-[18px] w-[18px] animate-spin" />
          <Sparkles v-else class="h-[18px] w-[18px]" />
        </span>
        <div>
          <p class="text-sm font-semibold text-ink-900">{{ running ? "Penilaian AI sedang berjalan" : "Penilaian AI selesai" }}</p>
          <p class="text-xs text-slate-500">{{ doneCount }} / {{ steps.length }} langkah · {{ elapsed }} s</p>
        </div>
      </div>
    </div>

    <ol class="space-y-2.5">
      <li v-for="(s, i) in steps" :key="s.kod" class="flex items-center gap-3">
        <span
          class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
          :class="
            s.status === 'selesai'
              ? 'bg-emerald-500 text-white'
              : s.status === 'ralat'
                ? 'bg-amber-500 text-white'
                : i === activeIndex && running
                  ? 'bg-brand-50 text-brand-600 ring-2 ring-brand-200'
                  : 'bg-slate-100 text-slate-400'
          "
        >
          <Check v-if="s.status === 'selesai'" class="h-3.5 w-3.5" />
          <CircleAlert v-else-if="s.status === 'ralat'" class="h-3.5 w-3.5" />
          <Loader2 v-else-if="i === activeIndex && running" class="h-3.5 w-3.5 animate-spin" />
          <span v-else class="text-[11px] font-semibold">{{ i + 1 }}</span>
        </span>
        <span class="flex-1 text-sm" :class="s.status === 'selesai' ? 'text-ink-800' : 'text-slate-500'">{{ s.label }}</span>
        <span v-if="!compact && s.ms != null" class="text-xs tabular-nums text-slate-400">{{ fmtMs(s.ms) }}</span>
      </li>
    </ol>

    <p v-if="progress?.mesej && !running && !compact" class="mt-4 rounded-xl bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-800">
      {{ progress.mesej }}
    </p>
    <p v-if="error && !progress" class="mt-3 text-xs text-rose-600">{{ error }}</p>
  </div>
</template>
