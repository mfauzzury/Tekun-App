<script setup lang="ts">
import { computed } from "vue";
import { CheckCircle2, ScanFace, ShieldAlert, XCircle } from "lucide-vue-next";

import type { PpFaceResult } from "@/types";
import PpBadge from "./PpBadge.vue";

const props = defineProps<{ result: PpFaceResult | null }>();

function pct(v?: number | null): number {
  if (typeof v !== "number" || !Number.isFinite(v)) return 0;
  const n = v <= 1 ? v * 100 : v;
  return Math.max(0, Math.min(100, Math.round(n)));
}

const meters = computed(() => [
  { label: "Padanan wajah (MyKad ↔ swafoto)", value: pct(props.result?.padananWajah) },
  { label: "Liveness (bukan foto / skrin / deepfake)", value: pct(props.result?.liveness) },
]);

const tone = computed(() =>
  props.result?.keputusan === "lulus" ? "emerald" : props.result?.keputusan === "gagal" ? "rose" : "amber",
);
const label = computed(() =>
  props.result?.keputusan === "lulus" ? "Lulus" : props.result?.keputusan === "gagal" ? "Gagal" : "Perlu semak",
);

function barClass(v: number) {
  return v >= 80 ? "bg-emerald-500" : v >= 60 ? "bg-amber-500" : "bg-rose-500";
}

function fmtDate(iso?: string | null) {
  if (!iso) return "";
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? "" : d.toLocaleString("ms-MY", { dateStyle: "medium", timeStyle: "short" });
}
</script>

<template>
  <div v-if="result" class="space-y-3">
    <div class="flex flex-wrap items-center gap-2">
      <ScanFace class="h-4 w-4 text-violet-600" />
      <p class="text-sm font-semibold text-slate-800">Wajah & liveness</p>
      <PpBadge :tone="tone" size="sm">{{ label }}</PpBadge>
      <span v-if="result.latencyMs" class="text-[11px] text-slate-400">{{ (result.latencyMs / 1000).toFixed(1) }} s</span>
      <span v-if="result.disemakPada" class="ml-auto text-[11px] text-slate-400">{{ fmtDate(result.disemakPada) }}</span>
    </div>
    <p v-if="result.ringkasan || result.message" class="text-xs text-slate-600">{{ result.ringkasan || result.message }}</p>
    <div class="grid gap-3 sm:grid-cols-2">
      <div v-for="m in meters" :key="m.label">
        <div class="flex items-center justify-between text-xs">
          <span class="text-slate-500">{{ m.label }}</span>
          <span class="font-mono font-semibold tabular-nums text-slate-800">{{ m.value }}%</span>
        </div>
        <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
          <div class="h-full rounded-full transition-all" :class="barClass(m.value)" :style="{ width: `${m.value}%` }" />
        </div>
      </div>
    </div>
    <ul v-if="result.cabaran?.length" class="grid gap-1.5 sm:grid-cols-3">
      <li
        v-for="(c, i) in result.cabaran"
        :key="i"
        class="flex items-start gap-1.5 rounded-md border px-2 py-1.5 text-xs"
        :class="c?.lulus ? 'border-emerald-200 bg-emerald-50/60' : 'border-rose-200 bg-rose-50/60'"
      >
        <CheckCircle2 v-if="c?.lulus" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" />
        <XCircle v-else class="mt-0.5 h-3.5 w-3.5 shrink-0 text-rose-600" />
        <span>
          <span class="font-medium text-slate-800">{{ c?.arahan || `Cabaran ${i + 1}` }}</span>
          <span v-if="c?.catatan" class="block text-[11px] text-slate-500">{{ c.catatan }}</span>
        </span>
      </li>
    </ul>
    <div v-if="result.tandaDeepfake?.length" class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2">
      <p class="flex items-center gap-1 text-xs font-semibold text-rose-800"><ShieldAlert class="h-3.5 w-3.5" /> Tanda deepfake / spoofing</p>
      <ul class="mt-1 list-disc pl-5 text-xs text-rose-700">
        <li v-for="(t, i) in result.tandaDeepfake" :key="i">{{ t }}</li>
      </ul>
    </div>
  </div>
</template>
