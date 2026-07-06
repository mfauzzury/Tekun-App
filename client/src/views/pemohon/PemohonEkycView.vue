<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { AlertTriangle, ArrowRight, Camera, CheckCircle2, Loader2, Upload, XCircle } from "lucide-vue-next";

import PemohonMiniStepper from "@/components/pemohon/PemohonMiniStepper.vue";
import AppToastRegion from "@/components/AppToastRegion.vue";
import { usePemohonStore } from "@/stores/pemohon";
import type { EkycSimulationResult } from "@/data/portal-dummy";

const router = useRouter();
const pemohon = usePemohonStore();

const STEPS = [
  { id: "dokumen", label: "Muat Naik Dokumen" },
  { id: "wajah", label: "Pengesahan Wajah" },
  { id: "keputusan", label: "Keputusan" },
];
const currentStep = ref(0);

const idFrontPreview = ref<string | null>(null);
const idBackPreview = ref<string | null>(null);
const selfieCaptured = ref(false);
const selfiePreview = ref<string | null>(null);
const processing = ref(false);
const result = ref<EkycSimulationResult | null>(null);

const videoEl = ref<HTMLVideoElement | null>(null);
const cameraStream = ref<MediaStream | null>(null);
const cameraError = ref("");
const cameraReady = ref(false);

function onFileSelected(event: Event, side: "front" | "back") {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) return;
  const url = URL.createObjectURL(file);
  if (side === "front") idFrontPreview.value = url;
  else idBackPreview.value = url;
}

const canProceedToFace = computed(() => Boolean(idFrontPreview.value && idBackPreview.value));

function goToFaceStep() {
  currentStep.value = 1;
}

function stopCamera() {
  cameraStream.value?.getTracks().forEach((track) => track.stop());
  cameraStream.value = null;
  cameraReady.value = false;
}

async function startCamera() {
  cameraError.value = "";
  cameraReady.value = false;
  if (!navigator.mediaDevices?.getUserMedia) {
    cameraError.value = "Kamera tidak disokong pada pelayar ini.";
    return;
  }
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" }, audio: false });
    cameraStream.value = stream;
    await nextTick();
    if (videoEl.value) {
      videoEl.value.srcObject = stream;
      videoEl.value.onloadeddata = () => {
        cameraReady.value = true;
      };
    }
  } catch {
    cameraError.value = "Tidak dapat mengakses kamera. Sila benarkan kebenaran kamera pada pelayar anda.";
  }
}

function captureSelfie() {
  if (videoEl.value && cameraStream.value && cameraReady.value) {
    const canvas = document.createElement("canvas");
    canvas.width = videoEl.value.videoWidth || 320;
    canvas.height = videoEl.value.videoHeight || 320;
    const ctx = canvas.getContext("2d");
    ctx?.drawImage(videoEl.value, 0, 0, canvas.width, canvas.height);
    selfiePreview.value = canvas.toDataURL("image/png");
  }
  selfieCaptured.value = true;
  stopCamera();
}

function retakeSelfie() {
  selfieCaptured.value = false;
  selfiePreview.value = null;
  startCamera();
}

watch(currentStep, (step) => {
  if (step === 1) startCamera();
  else stopCamera();
});

onBeforeUnmount(() => stopCamera());

function runVerification() {
  currentStep.value = 2;
  processing.value = true;
  result.value = null;
  window.setTimeout(() => {
    result.value = pemohon.runEkyc({
      idFrontUploaded: Boolean(idFrontPreview.value),
      idBackUploaded: Boolean(idBackPreview.value),
      selfieCaptured: selfieCaptured.value,
    });
    processing.value = false;
  }, 1400);
}

function proceed() {
  router.push({ name: "pemohon-dashboard" });
}
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-[#f6f9fc] px-4 py-10">
    <div class="w-full max-w-[520px]">
      <div class="mb-6 flex justify-center">
        <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-9 w-auto" />
      </div>

      <div class="rounded-lg border border-[#e3e8ee] bg-white px-8 pb-8 pt-6 shadow-[0_2px_4px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.06)]">
        <h1 class="mb-1 text-center text-xl font-semibold tracking-tight text-[#1a1f36]">Pengesahan Identiti (eKYC)</h1>
        <p class="mb-6 text-center text-[13px] text-[#697386]">Simulasi imbasan dokumen dan padanan wajah.</p>

        <div class="mb-6">
          <PemohonMiniStepper :steps="STEPS" :current-step="currentStep" />
        </div>

        <div v-if="currentStep === 0" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 p-4 text-center transition-colors hover:border-blue-400 hover:bg-blue-50">
              <img v-if="idFrontPreview" :src="idFrontPreview" alt="Pratonton hadapan" class="h-20 w-full rounded object-cover" />
              <Upload v-else class="h-6 w-6 text-slate-400" />
              <span class="text-xs font-medium text-slate-600">Kad Pengenalan (Hadapan)</span>
              <input type="file" accept="image/*" class="hidden" @change="onFileSelected($event, 'front')" />
            </label>
            <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 p-4 text-center transition-colors hover:border-blue-400 hover:bg-blue-50">
              <img v-if="idBackPreview" :src="idBackPreview" alt="Pratonton belakang" class="h-20 w-full rounded object-cover" />
              <Upload v-else class="h-6 w-6 text-slate-400" />
              <span class="text-xs font-medium text-slate-600">Kad Pengenalan (Belakang)</span>
              <input type="file" accept="image/*" class="hidden" @change="onFileSelected($event, 'back')" />
            </label>
          </div>

          <button
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-50"
            :disabled="!canProceedToFace"
            @click="goToFaceStep"
          >
            Seterusnya
            <ArrowRight class="h-4 w-4" />
          </button>
        </div>

        <div v-else-if="currentStep === 1" class="space-y-4 text-center">
          <div
            class="mx-auto flex h-48 w-48 items-center justify-center overflow-hidden rounded-full border-4 bg-slate-100"
            :class="selfieCaptured ? 'border-emerald-400' : 'border-slate-200'"
          >
            <img v-if="selfiePreview" :src="selfiePreview" alt="Imej wajah ditangkap" class="h-full w-full object-cover" />
            <video
              v-else-if="cameraStream"
              ref="videoEl"
              autoplay
              muted
              playsinline
              class="h-full w-full scale-x-[-1] object-cover"
            />
            <CheckCircle2 v-else-if="selfieCaptured" class="h-14 w-14 text-emerald-600" />
            <Camera v-else class="h-14 w-14 text-slate-400" />
          </div>

          <div v-if="cameraError" class="flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-left text-[13px] text-amber-700">
            <AlertTriangle class="h-4 w-4 shrink-0" />
            {{ cameraError }}
          </div>
          <p v-else-if="cameraStream && !cameraReady" class="flex items-center justify-center gap-1.5 text-sm text-slate-500">
            <Loader2 class="h-3.5 w-3.5 animate-spin" />
            Memulakan kamera...
          </p>
          <p v-else class="text-sm text-slate-500">
            {{ selfieCaptured ? "Imej wajah berjaya ditangkap." : "Letakkan wajah anda dalam bulatan dan tangkap imej." }}
          </p>

          <button
            v-if="!selfieCaptured"
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-50"
            :disabled="!cameraReady"
            @click="captureSelfie"
          >
            <Camera class="h-4 w-4" />
            Tangkap Imej Wajah
          </button>
          <template v-else>
            <button
              type="button"
              class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700"
              @click="runVerification"
            >
              Sahkan Identiti
              <ArrowRight class="h-4 w-4" />
            </button>
            <button
              type="button"
              class="w-full rounded-md border border-slate-300 px-4 py-[9px] text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
              @click="retakeSelfie"
            >
              Tangkap Semula
            </button>
          </template>
        </div>

        <div v-else class="space-y-4 text-center">
          <div v-if="processing" class="flex flex-col items-center gap-3 py-6">
            <Loader2 class="h-10 w-10 animate-spin text-blue-600" />
            <p class="text-sm text-slate-500">Memproses pengesahan identiti...</p>
          </div>
          <div v-else-if="result" class="flex flex-col items-center gap-2">
            <div class="flex h-14 w-14 items-center justify-center rounded-full" :class="result.status === 'lulus' ? 'bg-emerald-50' : 'bg-rose-50'">
              <CheckCircle2 v-if="result.status === 'lulus'" class="h-8 w-8 text-emerald-600" />
              <XCircle v-else class="h-8 w-8 text-rose-600" />
            </div>
            <h2 class="text-lg font-semibold" :class="result.status === 'lulus' ? 'text-emerald-700' : 'text-rose-700'">
              {{ result.status === 'lulus' ? "Pengesahan Berjaya" : "Pengesahan Gagal" }}
            </h2>
            <p class="text-sm text-slate-500">Tahap keyakinan: {{ Math.round(result.confidence * 100) }}%</p>
            <ul class="w-full space-y-1 text-left text-sm text-slate-600">
              <li v-for="reason in result.reasons" :key="reason">• {{ reason }}</li>
            </ul>

            <button
              v-if="result.status === 'lulus'"
              type="button"
              class="mt-2 flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700"
              @click="proceed"
            >
              Teruskan ke Dashboard
              <ArrowRight class="h-4 w-4" />
            </button>
            <button
              v-else
              type="button"
              class="mt-2 w-full rounded-md border border-slate-300 px-4 py-[9px] text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
              @click="currentStep = 0"
            >
              Cuba Semula
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="fixed right-4 top-4 z-50 h-16">
      <AppToastRegion />
    </div>
  </div>
</template>
