<script setup lang="ts">
import { ref, watch } from "vue";
import { Camera, CameraOff, ImageUp, Loader2, RotateCcw, Send, X } from "lucide-vue-next";

import { verifyPenilaianPantasWajah } from "@/api/sppt";
import { useLivenessCapture } from "@/composables/useLivenessCapture";
import { useToast } from "@/composables/useToast";
import type { PpFaceResult } from "@/types";
import FaceResultCard from "./FaceResultCard.vue";

const props = defineProps<{ open: boolean; permohonanId: number }>();
const emit = defineEmits<{ (e: "close"): void; (e: "verified", result: PpFaceResult): void }>();

const toast = useToast();
const {
  CABARAN,
  videoEl,
  stream,
  cameraError,
  starting,
  phase,
  currentIdx,
  countdown,
  frames,
  startCamera,
  stopCamera,
  reset: resetCapture,
  runChallenges,
  framesFromFiles,
  payload,
} = useLivenessCapture();

const fileInput = ref<HTMLInputElement | null>(null);
const result = ref<PpFaceResult | null>(null);

function reset() {
  resetCapture();
  result.value = null;
}

function close() {
  reset();
  stopCamera();
  emit("close");
}

watch(
  () => props.open,
  (open) => {
    if (open) {
      reset();
      void startCamera();
    } else {
      stopCamera();
    }
  },
  { immediate: true },
);

function pickFiles() {
  fileInput.value?.click();
}

async function onFiles(ev: Event) {
  const input = ev.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);
  input.value = "";
  if (!files.length) return;
  try {
    await framesFromFiles(files);
  } catch {
    toast.error("Gambar tidak dapat dibaca", "Sila pilih fail JPG atau PNG.");
  }
}

async function submit() {
  if (!frames.value.length) return;
  phase.value = "hantar";
  try {
    const res = await verifyPenilaianPantasWajah(props.permohonanId, payload());
    result.value = res?.data ?? null;
    phase.value = "keputusan";
    stopCamera();
    if (result.value) emit("verified", result.value);
  } catch (e: unknown) {
    phase.value = "semak";
    toast.error("Pengesahan wajah gagal", e instanceof Error ? e.message : undefined);
  }
}

function retake() {
  reset();
  if (!stream.value) void startCamera();
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
      <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
        <header class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
          <Camera class="h-4 w-4 text-violet-600" />
          <h3 class="text-sm font-semibold text-slate-900">Pengesahan wajah langsung (liveness)</h3>
          <button type="button" class="ml-auto rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="close">
            <X class="h-4 w-4" />
          </button>
        </header>

        <div class="space-y-4 overflow-y-auto p-5">
          <template v-if="phase !== 'keputusan'">
            <div class="relative aspect-video w-full overflow-hidden rounded-lg bg-slate-900">
              <video
                v-show="stream"
                ref="videoEl"
                class="h-full w-full -scale-x-100 object-cover"
                autoplay
                muted
                playsinline
              />
              <div v-if="!stream" class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-6 text-center text-sm text-slate-300">
                <Loader2 v-if="starting" class="h-6 w-6 animate-spin" />
                <CameraOff v-else class="h-6 w-6" />
                <span>{{ starting ? "Membuka kamera…" : cameraError || "Kamera tidak aktif" }}</span>
              </div>
              <div
                v-if="stream && (phase === 'sedia' || phase === 'cabaran')"
                class="pointer-events-none absolute left-1/2 top-1/2 h-[70%] aspect-[3/4] -translate-x-1/2 -translate-y-1/2 rounded-[50%] border-2 border-dashed border-white/70"
              />
              <div v-if="phase === 'cabaran'" class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-900/90 to-transparent p-4 text-center">
                <p class="text-xs font-medium uppercase tracking-wider text-violet-200">Cabaran {{ currentIdx + 1 }} / {{ CABARAN.length }}</p>
                <p class="mt-1 text-lg font-semibold text-white">{{ CABARAN[currentIdx] }}</p>
              </div>
              <div v-if="phase === 'cabaran' && countdown > 0" class="absolute right-4 top-4 flex h-14 w-14 items-center justify-center rounded-full bg-violet-600 text-2xl font-bold text-white shadow-lg">
                {{ countdown }}
              </div>
              <div v-if="phase === 'hantar'" class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-slate-900/70 text-sm text-white">
                <Loader2 class="h-7 w-7 animate-spin" />
                AI sedang menyemak padanan wajah & liveness…
              </div>
            </div>

            <ol class="grid grid-cols-3 gap-2">
              <li
                v-for="(c, i) in CABARAN"
                :key="c"
                class="overflow-hidden rounded-lg border text-center"
                :class="phase === 'cabaran' && i === currentIdx ? 'border-violet-400 ring-2 ring-violet-200' : 'border-slate-200'"
              >
                <div class="flex aspect-video items-center justify-center bg-slate-50">
                  <img
                    v-if="frames[i]"
                    :src="frames[i].preview"
                    alt=""
                    class="h-full w-full object-cover"
                    :class="frames[i].mirror ? '-scale-x-100' : ''"
                  />
                  <span v-else class="text-[11px] text-slate-400">Menunggu</span>
                </div>
                <p class="px-1.5 py-1 text-[11px] font-medium text-slate-600">{{ c }}</p>
              </li>
            </ol>

            <p class="text-xs text-slate-500">
              Ikut arahan pada skrin. Setiap cabaran mengambil gambar selepas kiraan 3 saat. Gambar dihantar ke enjin AI untuk
              padanan dengan foto MyKad dan pengesanan spoofing / deepfake.
            </p>
          </template>

          <template v-else>
            <FaceResultCard :result="result" />
            <p class="rounded-md bg-violet-50 px-3 py-2 text-xs text-violet-800">
              Jalankan semula penilaian untuk mengambil kira keputusan wajah dalam langkah e-KYC.
            </p>
          </template>
        </div>

        <footer class="flex flex-wrap items-center gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
          <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="onFiles" />
          <button
            v-if="phase === 'sedia' || phase === 'semak'"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
            @click="pickFiles"
          >
            <ImageUp class="h-3.5 w-3.5" /> Muat naik gambar
          </button>
          <button
            v-if="phase === 'semak'"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
            @click="retake"
          >
            <RotateCcw class="h-3.5 w-3.5" /> Ambil semula
          </button>
          <div class="ml-auto flex gap-2">
            <button
              v-if="phase === 'sedia'"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-violet-700 disabled:opacity-50"
              :disabled="!stream"
              @click="runChallenges"
            >
              <Camera class="h-3.5 w-3.5" /> Mula cabaran
            </button>
            <button
              v-if="phase === 'semak' || phase === 'hantar'"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-violet-700 disabled:opacity-50"
              :disabled="phase === 'hantar' || !frames.length"
              @click="submit"
            >
              <Loader2 v-if="phase === 'hantar'" class="h-3.5 w-3.5 animate-spin" />
              <Send v-else class="h-3.5 w-3.5" />
              Hantar untuk pengesahan
            </button>
            <button
              v-if="phase === 'keputusan'"
              type="button"
              class="rounded-lg bg-slate-900 px-4 py-1.5 text-xs font-semibold text-white hover:bg-slate-800"
              @click="close"
            >
              Tutup
            </button>
          </div>
        </footer>
      </div>
    </div>
  </Teleport>
</template>
