<script setup lang="ts">
import { computed, ref } from "vue";
import { AlertTriangle, Camera, CheckCircle2, CreditCard, FileScan, Info, ScanFace, XCircle } from "lucide-vue-next";

import type { PpEkycHasil, PpFaceResult } from "@/types";
import FaceResultCard from "./FaceResultCard.vue";
import LivenessCameraModal from "./LivenessCameraModal.vue";
import PpBadge from "./PpBadge.vue";
import PpCard from "./PpCard.vue";

const props = withDefaults(
  defineProps<{
    permohonanId: number;
    hasil: PpEkycHasil | null;
    ekycWajah?: PpFaceResult | null;
    adaMykad?: boolean;
    keputusan?: string | null;
  }>(),
  { ekycWajah: null, adaMykad: false, keputusan: null },
);
const emit = defineEmits<{ (e: "face-verified", result: PpFaceResult): void }>();

const cameraOpen = ref(false);
const verifiedNow = ref(false);

const face = computed<PpFaceResult | null>(() => props.ekycWajah ?? props.hasil?.wajah ?? null);
const ic = computed(() => props.hasil?.ic ?? null);
const ocr = computed(() => props.hasil?.ocr ?? null);
const padanan = computed(() => props.hasil?.padanan ?? null);

function maskIc(v?: string | null): string {
  const d = String(v ?? "").replace(/\D/g, "");
  if (d.length < 8) return v || "—";
  return `${d.slice(0, 6)}-**-**${d.slice(-2)}`;
}

function onVerified(r: PpFaceResult) {
  verifiedNow.value = true;
  emit("face-verified", r);
}

function tone(k?: string | null) {
  return k === "lulus" ? "emerald" : k === "gagal" ? "rose" : k === "semak" ? "amber" : "slate";
}
function pct(v?: number | null) {
  return typeof v === "number" && Number.isFinite(v) ? `${Math.round(v <= 1 ? v * 100 : v)}%` : "—";
}
</script>

<template>
  <PpCard title="e-KYC & pengesahan identiti" subtitle="C2 · OCR MyKad, padanan borang, liveness & padanan wajah" :icon="ScanFace" icon-class="text-violet-600">
    <template #actions>
      <PpBadge v-if="keputusan" :tone="tone(keputusan)">{{ keputusan === "lulus" ? "Lulus" : keputusan === "gagal" ? "Gagal" : "Perlu semak" }}</PpBadge>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-violet-700"
        @click="cameraOpen = true"
      >
        <Camera class="h-3.5 w-3.5" /> Pengesahan wajah langsung
      </button>
    </template>

    <div class="space-y-4">
      <p v-if="!hasil" class="flex items-center gap-1.5 rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-500">
        <Info class="h-3.5 w-3.5" /> Keputusan e-KYC akan dipaparkan selepas langkah 1 selesai.
      </p>

      <div v-if="hasil" class="grid gap-4 lg:grid-cols-2">
        <!-- IC structural -->
        <section class="rounded-lg border border-slate-200 p-3">
          <div class="mb-2 flex items-center gap-2">
            <CreditCard class="h-4 w-4 text-slate-500" />
            <p class="text-sm font-semibold text-slate-800">Struktur No. MyKad</p>
            <PpBadge :tone="ic?.valid ? 'emerald' : 'rose'" class="ml-auto">{{ ic?.valid ? "Sah" : "Tidak sah" }}</PpBadge>
          </div>
          <dl class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs">
            <dt class="text-slate-500">No. IC</dt>
            <dd class="font-mono font-medium text-slate-800">{{ maskIc(ic?.noIc) }}</dd>
            <dt class="text-slate-500">Tarikh lahir</dt>
            <dd class="font-medium text-slate-800">{{ ic?.tarikhLahir || "—" }}</dd>
            <dt class="text-slate-500">Umur</dt>
            <dd class="font-medium text-slate-800">{{ ic?.umur ?? "—" }}{{ ic?.umur != null ? " tahun" : "" }}</dd>
            <dt class="text-slate-500">Jantina</dt>
            <dd class="font-medium text-slate-800">{{ ic?.jantina || "—" }}</dd>
            <dt class="text-slate-500">Negeri lahir</dt>
            <dd class="font-medium text-slate-800">{{ ic?.negeriLahir || "—" }}<span v-if="ic?.kodNegeri" class="text-slate-400"> ({{ ic.kodNegeri }})</span></dd>
          </dl>
          <ul v-if="ic?.isu?.length || hasil?.isu?.length" class="mt-2 space-y-1">
            <li v-for="(i, idx) in [...(ic?.isu ?? []), ...(hasil?.isu ?? [])]" :key="idx" class="flex items-start gap-1.5 text-xs text-amber-800">
              <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-600" /> {{ i }}
            </li>
          </ul>
        </section>

        <!-- OCR -->
        <section class="rounded-lg border border-slate-200 p-3">
          <div class="mb-2 flex items-center gap-2">
            <FileScan class="h-4 w-4 text-slate-500" />
            <p class="text-sm font-semibold text-slate-800">OCR MyKad (AI)</p>
            <template v-if="ocr">
              <PpBadge :tone="ocr.adalahMykad === false ? 'rose' : 'emerald'" class="ml-auto">
                {{ ocr.adalahMykad === false ? "Bukan MyKad" : "MyKad dikesan" }}
              </PpBadge>
            </template>
          </div>
          <p v-if="!ocr" class="text-xs text-slate-500">
            {{ adaMykad ? "OCR tidak dijalankan untuk penilaian ini." : "Tiada imej MyKad dimuat naik — OCR dilangkau." }}
          </p>
          <template v-else>
            <dl class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs">
              <dt class="text-slate-500">Kualiti imej</dt>
              <dd class="font-medium text-slate-800">{{ pct(ocr.kualiti?.skor) }}</dd>
              <dt class="text-slate-500">Sumber</dt>
              <dd class="font-medium text-slate-800">{{ ocr.sumber || "—" }}</dd>
              <dt class="text-slate-500">Masa OCR</dt>
              <dd class="font-medium text-slate-800">{{ ocr.latencyMs ? `${(ocr.latencyMs / 1000).toFixed(1)} s` : "—" }}</dd>
            </dl>
            <p v-if="ocr.message" class="mt-2 text-[11px] text-slate-500">{{ ocr.message }}</p>
            <ul v-if="ocr.kualiti?.isu?.length" class="mt-2 list-disc pl-5 text-[11px] text-slate-500">
              <li v-for="(i, idx) in ocr.kualiti.isu" :key="idx">{{ i }}</li>
            </ul>
            <div v-if="ocr.tandaPalsu?.length" class="mt-2 rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1.5">
              <p class="text-xs font-semibold text-rose-800">Tanda pemalsuan dokumen</p>
              <ul class="list-disc pl-4 text-xs text-rose-700">
                <li v-for="(t, idx) in ocr.tandaPalsu" :key="idx">{{ t }}</li>
              </ul>
            </div>
          </template>
        </section>
      </div>

      <!-- Form vs MyKad comparison -->
      <section v-if="padanan?.padanan?.length" class="overflow-hidden rounded-lg border border-slate-200">
        <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-3 py-2">
          <p class="text-xs font-semibold text-slate-700">Padanan borang ↔ MyKad</p>
          <span class="ml-auto text-xs text-slate-500">Skor keseluruhan</span>
          <PpBadge :tone="(padanan.skorKeseluruhan ?? 0) >= 85 ? 'emerald' : (padanan.skorKeseluruhan ?? 0) >= 60 ? 'amber' : 'rose'" size="sm">
            {{ pct(padanan.skorKeseluruhan) }}
          </PpBadge>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead class="text-left text-[10px] uppercase tracking-wider text-slate-400">
              <tr>
                <th class="px-3 py-1.5 font-semibold">Medan</th>
                <th class="px-3 py-1.5 font-semibold">Borang</th>
                <th class="px-3 py-1.5 font-semibold">MyKad (OCR)</th>
                <th class="px-3 py-1.5 text-right font-semibold">Padanan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(p, idx) in padanan.padanan" :key="p?.medan ?? idx">
                <td class="px-3 py-1.5 font-medium text-slate-700">{{ p?.label || p?.medan }}</td>
                <td class="max-w-[200px] truncate px-3 py-1.5 text-slate-600" :title="p?.nilaiBorang ?? ''">{{ p?.nilaiBorang || "—" }}</td>
                <td class="max-w-[200px] truncate px-3 py-1.5 text-slate-600" :title="p?.nilaiMykad ?? ''">{{ p?.nilaiMykad || "—" }}</td>
                <td class="px-3 py-1.5 text-right">
                  <span class="inline-flex items-center gap-1 font-mono font-semibold" :class="p?.padan ? 'text-emerald-700' : 'text-rose-700'">
                    <CheckCircle2 v-if="p?.padan" class="h-3.5 w-3.5" />
                    <XCircle v-else class="h-3.5 w-3.5" />
                    {{ pct(p?.skor) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Face -->
      <section class="rounded-lg border border-slate-200 p-3">
        <FaceResultCard v-if="face" :result="face" />
        <p v-else class="flex items-center gap-1.5 text-xs text-slate-500">
          <ScanFace class="h-3.5 w-3.5" /> Belum ada pengesahan wajah. Klik “Pengesahan wajah langsung” untuk menjalankan cabaran liveness.
        </p>
        <p v-if="verifiedNow" class="mt-3 flex items-center gap-1.5 rounded-md bg-violet-50 px-3 py-2 text-xs font-medium text-violet-800">
          <Info class="h-3.5 w-3.5" /> Jalankan semula penilaian untuk mengambil kira keputusan wajah.
        </p>
      </section>
    </div>

    <LivenessCameraModal :open="cameraOpen" :permohonan-id="permohonanId" @close="cameraOpen = false" @verified="onVerified" />
  </PpCard>
</template>
