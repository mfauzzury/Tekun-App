<script setup lang="ts">
import { computed, ref } from "vue";
import { Camera, CameraOff, Check, ImageUp, Loader2, RotateCcw, ScanFace, Send, Smile, TriangleAlert, X } from "lucide-vue-next";

import EkycFaceResultCard from "@/components/pemohon/ekyc/EkycFaceResultCard.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import { verifyPemohonWajah } from "@/api/pemohonPermohonan";
import { useLivenessCapture } from "@/composables/useLivenessCapture";
import { useToast } from "@/composables/useToast";
import { errorMessage } from "@/components/pemohon/permohonan/permohonan-form";
import type { PpFaceResult } from "@/types";

/** e-KYC part 2 — active liveness (3 on-screen challenges) + AI face match against the MyKad photo. */
const props = withDefaults(
  defineProps<{ ensureRecord: () => Promise<{ id: number; token: string } | null>; result: PpFaceResult | null; hasMyKad?: boolean }>(),
  { hasMyKad: true },
);
const emit = defineEmits<{ verified: [result: PpFaceResult] }>();

const toast = useToast();
const { CABARAN, videoEl, stream, cameraError, starting, phase, currentIdx, countdown, frames, startCamera, stopCamera, reset, runChallenges, framesFromFiles, payload } =
  useLivenessCapture();

const active = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const instruction = computed(() => {
  if (phase.value === "cabaran") return CABARAN[currentIdx.value];
  if (phase.value === "semak") return "Semak gambar anda";
  if (phase.value === "hantar") return "AI sedang mengesahkan wajah anda…";
  return "Letakkan wajah anda di dalam bingkai";
});

async function open() {
  reset();
  active.value = true;
  await startCamera();
}

function close() {
  reset();
  stopCamera();
  active.value = false;
}

function retake() {
  reset();
  if (!stream.value) void startCamera();
}

function pickFiles() {
  fileInput.value?.click();
}

async function onFiles(ev: Event) {
  const input = ev.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);
  input.value = "";
  if (!files.length) return;
  try {
    active.value = true;
    await framesFromFiles(files);
    stopCamera();
  } catch {
    toast.error("Gambar tidak dapat dibaca", "Sila pilih fail JPG atau PNG.");
  }
}

async function submit() {
  if (!frames.value.length) return;
  phase.value = "hantar";
  try {
    const record = await props.ensureRecord();
    if (!record) {
      phase.value = "semak";
      return;
    }
    const res = await verifyPemohonWajah(record.id, record.token, payload());
    emit("verified", res.data);
    close();
  } catch (e) {
    phase.value = "semak";
    toast.error("Pengesahan wajah gagal", errorMessage(e, "Sila cuba sekali lagi."));
  }
}
</script>

<template>
  <div class="space-y-4">
    <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only" tabindex="-1" aria-hidden="true" @change="onFiles" />

    <!-- Result (when not re-capturing) -->
    <template v-if="result && !active">
      <EkycFaceResultCard :result="result" />
      <PButton variant="outline" size="sm" @click="open"><RotateCcw class="h-4 w-4" /> Ulang pengesahan wajah</PButton>
    </template>

    <!-- Intro -->
    <div v-else-if="!active" class="rounded-2xl border border-slate-200 bg-white p-5">
      <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600"><ScanFace class="h-7 w-7" /></span>
        <div class="min-w-0 flex-1">
          <p class="text-[15px] font-semibold text-ink-900">Pengesahan wajah langsung</p>
          <p class="mt-0.5 text-sm text-slate-500">Ikut 3 arahan ringkas di skrin. Mengambil masa kurang 15 saat.</p>
          <ol class="mt-3 flex flex-wrap gap-2">
            <li v-for="(c, i) in CABARAN" :key="c" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
              <span class="flex h-4 w-4 items-center justify-center rounded-full bg-white text-[10px] font-bold text-ink-700">{{ i + 1 }}</span>{{ c }}
            </li>
          </ol>
        </div>
      </div>
      <p v-if="!hasMyKad" class="mt-4 flex gap-2 rounded-xl bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
        <TriangleAlert class="mt-0.5 h-4 w-4 shrink-0" /> Muat naik MyKad depan dahulu supaya wajah anda dapat dipadankan dengan foto MyKad.
      </p>
      <div class="mt-5 flex flex-col gap-2 sm:flex-row">
        <PButton :loading="starting" @click="open"><Camera class="h-4 w-4" /> Mula pengesahan wajah</PButton>
        <PButton variant="ghost" @click="pickFiles"><ImageUp class="h-4 w-4" /> Tiada kamera? Muat naik gambar</PButton>
      </div>
    </div>

    <!-- Capture -->
    <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
      <div class="relative mx-auto aspect-[3/4] max-h-[460px] w-full bg-ink-950 sm:aspect-[4/3]">
        <video v-show="stream && phase !== 'semak' && phase !== 'hantar'" ref="videoEl" class="h-full w-full -scale-x-100 object-cover" autoplay muted playsinline />

        <!-- Captured frames review -->
        <div v-if="phase === 'semak' || phase === 'hantar'" class="grid h-full grid-cols-3 gap-1 bg-ink-950 p-1">
          <img v-for="f in frames" :key="f.arahan" :src="f.preview" :alt="f.arahan" class="h-full w-full rounded-lg object-cover" :class="f.mirror ? '-scale-x-100' : ''" />
        </div>

        <div v-if="!stream && phase !== 'semak' && phase !== 'hantar'" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center text-sm text-ink-200">
          <Loader2 v-if="starting" class="h-7 w-7 animate-spin" />
          <CameraOff v-else class="h-7 w-7" />
          <span>{{ starting ? "Membuka kamera…" : cameraError || "Kamera tidak aktif" }}</span>
          <PButton v-if="!starting" size="sm" variant="outline" @click="pickFiles"><ImageUp class="h-4 w-4" /> Muat naik 3 gambar wajah</PButton>
        </div>

        <!-- Face guide -->
        <div
          v-if="stream && (phase === 'sedia' || phase === 'cabaran')"
          class="pointer-events-none absolute left-1/2 top-[45%] aspect-[3/4] h-[62%] -translate-x-1/2 -translate-y-1/2 rounded-[50%] border-[3px] transition-colors"
          :class="phase === 'cabaran' ? 'border-brand-400 shadow-[0_0_0_9999px_rgba(11,17,32,0.45)]' : 'border-white/80 shadow-[0_0_0_9999px_rgba(11,17,32,0.35)]'"
        />

        <!-- Instruction overlay -->
        <div v-if="stream || phase === 'hantar'" class="absolute inset-x-0 top-0 flex justify-center p-3">
          <span v-if="phase === 'cabaran'" class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
            Arahan {{ currentIdx + 1 }} daripada {{ CABARAN.length }}
          </span>
        </div>
        <div v-if="(stream && phase !== 'semak') || phase === 'hantar'" class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink-950/90 via-ink-950/50 to-transparent px-4 pb-5 pt-12 text-center" aria-live="assertive">
          <p class="flex items-center justify-center gap-2 text-xl font-bold text-white sm:text-2xl">
            <Smile v-if="phase === 'cabaran' && currentIdx === 2" class="h-6 w-6" />
            <Loader2 v-if="phase === 'hantar'" class="h-6 w-6 animate-spin" />
            {{ instruction }}
          </p>
        </div>
        <div
          v-if="phase === 'cabaran' && countdown > 0"
          class="pointer-events-none absolute left-1/2 top-[45%] flex h-20 w-20 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-brand-600 text-4xl font-bold text-white shadow-xl"
          aria-hidden="true"
        >
          <span class="absolute inset-0 animate-ping rounded-full bg-brand-500/40" />
          <span class="relative tabular-nums">{{ countdown }}</span>
        </div>

        <button
          type="button"
          class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-black/40 text-white hover:bg-black/60 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-white/40"
          aria-label="Tutup kamera"
          @click="close"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <!-- Thumbnails -->
      <ol class="grid grid-cols-3 gap-2 p-3">
        <li
          v-for="(c, i) in CABARAN"
          :key="c"
          class="flex items-center gap-2 rounded-xl border px-2 py-1.5"
          :class="phase === 'cabaran' && i === currentIdx ? 'border-brand-300 bg-brand-50' : frames[i] ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200'"
        >
          <span class="relative h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-slate-100">
            <img v-if="frames[i]" :src="frames[i].preview" alt="" class="h-full w-full object-cover" :class="frames[i].mirror ? '-scale-x-100' : ''" />
            <span v-if="frames[i]" class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-white"><Check class="h-2.5 w-2.5" /></span>
            <span v-else class="flex h-full w-full items-center justify-center text-xs font-semibold text-slate-400">{{ i + 1 }}</span>
          </span>
          <span class="min-w-0 text-[11px] font-medium leading-tight text-slate-600 sm:text-xs">{{ c }}</span>
        </li>
      </ol>

      <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/60 p-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-2">
          <PButton v-if="phase === 'semak'" variant="outline" size="sm" @click="retake"><RotateCcw class="h-4 w-4" /> Ambil semula</PButton>
          <PButton v-if="phase === 'sedia' || phase === 'semak'" variant="ghost" size="sm" @click="pickFiles"><ImageUp class="h-4 w-4" /> Muat naik gambar</PButton>
        </div>
        <PButton v-if="phase === 'sedia'" :disabled="!stream" @click="runChallenges"><Camera class="h-4 w-4" /> Saya sedia — mula</PButton>
        <PButton v-else-if="phase === 'cabaran'" disabled><Loader2 class="h-4 w-4 animate-spin" /> Ikut arahan di skrin…</PButton>
        <PButton v-else-if="phase === 'semak' || phase === 'hantar'" :loading="phase === 'hantar'" :disabled="!frames.length" @click="submit">
          <Send v-if="phase !== 'hantar'" class="h-4 w-4" /> Hantar untuk pengesahan
        </PButton>
      </div>
    </div>
  </div>
</template>
