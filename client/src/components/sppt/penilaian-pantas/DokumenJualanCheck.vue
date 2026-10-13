<script setup lang="ts">
import { ref } from "vue";
import { CheckCircle2, FileSearch, Loader2, Upload, XCircle } from "lucide-vue-next";

import { analyzePenilaianPantasDokumenJualan } from "@/api/sppt";
import { useToast } from "@/composables/useToast";
import type { PpDokumenJualanResult } from "@/types";
import PpBadge from "./PpBadge.vue";

const props = defineProps<{ permohonanId: number }>();
const toast = useToast();

const fileInput = ref<HTMLInputElement | null>(null);
const loading = ref(false);
const fileName = ref("");
const result = ref<PpDokumenJualanResult | null>(null);

async function onFile(ev: Event) {
  const input = ev.target as HTMLInputElement;
  const file = input.files?.[0];
  input.value = "";
  if (!file) return;
  fileName.value = file.name;
  loading.value = true;
  result.value = null;
  try {
    const res = await analyzePenilaianPantasDokumenJualan(props.permohonanId, file);
    result.value = res?.data ?? null;
  } catch (e: unknown) {
    toast.error("Analisis dokumen gagal", e instanceof Error ? e.message : undefined);
  } finally {
    loading.value = false;
  }
}

function tone(k?: string) {
  return k === "asli" ? "emerald" : k === "palsu" ? "rose" : "amber";
}
function label(k?: string) {
  return k === "asli" ? "Kelihatan asli" : k === "palsu" ? "Disyaki palsu" : "Meragukan";
}
</script>

<template>
  <section class="rounded-lg border border-slate-200 p-3">
    <div class="flex flex-wrap items-center gap-2">
      <FileSearch class="h-4 w-4 text-slate-500" />
      <p class="text-sm font-semibold text-slate-800">Semak dokumen jualan (forensik AI)</p>
      <input ref="fileInput" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.csv,.txt" class="hidden" @change="onFile" />
      <button
        type="button"
        class="ml-auto inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50"
        :disabled="loading"
        @click="fileInput?.click()"
      >
        <Loader2 v-if="loading" class="h-3.5 w-3.5 animate-spin" />
        <Upload v-else class="h-3.5 w-3.5" />
        {{ loading ? "Menganalisis…" : "Muat naik resit / penyata jualan" }}
      </button>
    </div>
    <p class="mt-1 text-[11px] text-slate-500">
      Mengesan suntingan digital, fon tidak konsisten dan ralat aritmetik pada resit, invois atau penyata jualan (JPG/PNG/PDF, maks 10MB).
    </p>

    <div v-if="result" class="mt-3 space-y-2">
      <div class="flex flex-wrap items-center gap-3">
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Skor keaslian</p>
          <p class="font-mono text-2xl font-bold tabular-nums" :class="(result.skorKeaslian ?? 0) >= 80 ? 'text-emerald-600' : (result.skorKeaslian ?? 0) >= 50 ? 'text-amber-600' : 'text-rose-600'">
            {{ result.skorKeaslian ?? "—" }}<span class="text-sm text-slate-400">/100</span>
          </p>
        </div>
        <PpBadge :tone="tone(result.keputusan)" size="sm">{{ label(result.keputusan) }}</PpBadge>
        <span class="text-[11px] text-slate-400">{{ fileName }}<template v-if="result.latencyMs"> · {{ (result.latencyMs / 1000).toFixed(1) }} s</template></span>
      </div>
      <p v-if="result.ringkasan || result.message" class="text-xs text-slate-600">{{ result.ringkasan || result.message }}</p>
      <div
        v-if="result.semakanAritmetik"
        class="flex items-start gap-1.5 rounded-md px-2.5 py-1.5 text-xs"
        :class="result.semakanAritmetik.lulus ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800'"
      >
        <CheckCircle2 v-if="result.semakanAritmetik.lulus" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
        <XCircle v-else class="mt-0.5 h-3.5 w-3.5 shrink-0" />
        <span><span class="font-semibold">Semakan aritmetik:</span> {{ result.semakanAritmetik.catatan || (result.semakanAritmetik.lulus ? "Jumlah konsisten" : "Jumlah tidak konsisten") }}</span>
      </div>
      <ul v-if="result.tandaPalsu?.length" class="list-disc space-y-0.5 pl-5 text-xs text-rose-700">
        <li v-for="(t, i) in result.tandaPalsu" :key="i">{{ t }}</li>
      </ul>
    </div>
  </section>
</template>
