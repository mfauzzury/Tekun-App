<script setup lang="ts">
import { computed } from "vue";
import { CheckCircle2, CircleAlert, ShieldAlert, XCircle } from "lucide-vue-next";

import type { PpFaceResult } from "@/types";

const props = withDefaults(defineProps<{ result: PpFaceResult; compact?: boolean }>(), { compact: false });

function pct(v?: number | null): number | null {
  if (typeof v !== "number" || !Number.isFinite(v)) return null;
  return Math.max(0, Math.min(100, Math.round(v <= 1 ? v * 100 : v)));
}

const meta = computed(() => {
  const k = props.result.keputusan;
  if (k === "lulus") return { icon: CheckCircle2, title: "Identiti anda disahkan", tone: "emerald", box: "border-emerald-200 bg-emerald-50", text: "text-emerald-800", iconCls: "text-emerald-600" };
  if (k === "gagal") return { icon: XCircle, title: "Pengesahan wajah tidak berjaya", tone: "rose", box: "border-rose-200 bg-rose-50", text: "text-rose-800", iconCls: "text-rose-600" };
  return { icon: CircleAlert, title: "Perlu semakan pegawai", tone: "amber", box: "border-amber-200 bg-amber-50", text: "text-amber-900", iconCls: "text-amber-600" };
});

const meters = computed(() =>
  [
    { label: "Padanan wajah dengan MyKad", value: pct(props.result.padananWajah) },
    { label: "Liveness (orang sebenar)", value: pct(props.result.liveness) },
  ].filter((m) => m.value != null) as { label: string; value: number }[],
);

function barClass(v: number) {
  return v >= 80 ? "bg-emerald-500" : v >= 60 ? "bg-amber-500" : "bg-rose-500";
}
</script>

<template>
  <div class="overflow-hidden rounded-2xl border" :class="meta.box">
    <div class="flex items-start gap-3 p-4">
      <component :is="meta.icon" class="mt-0.5 h-6 w-6 shrink-0" :class="meta.iconCls" />
      <div class="min-w-0 flex-1">
        <p class="text-[15px] font-semibold" :class="meta.text">{{ meta.title }}</p>
        <p v-if="result.ringkasan || result.message" class="mt-0.5 text-sm" :class="meta.text">{{ result.ringkasan || result.message }}</p>
      </div>
    </div>
    <div v-if="meters.length || (!compact && (result.cabaran?.length || result.tandaDeepfake?.length))" class="space-y-4 border-t border-black/5 bg-white/70 p-4">
      <div v-if="meters.length" class="grid gap-4 sm:grid-cols-2">
        <div v-for="m in meters" :key="m.label">
          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-600">{{ m.label }}</span>
            <span class="font-semibold tabular-nums text-ink-900">{{ m.value }}%</span>
          </div>
          <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-200/70" role="progressbar" :aria-valuenow="m.value" aria-valuemin="0" aria-valuemax="100" :aria-label="m.label">
            <div class="h-full rounded-full transition-all duration-700" :class="barClass(m.value)" :style="{ width: `${m.value}%` }" />
          </div>
        </div>
      </div>
      <ul v-if="!compact && result.cabaran?.length" class="flex flex-wrap gap-2">
        <li
          v-for="(c, i) in result.cabaran"
          :key="i"
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
          :class="c?.lulus ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
          :title="c?.catatan || undefined"
        >
          <CheckCircle2 v-if="c?.lulus" class="h-3.5 w-3.5" />
          <XCircle v-else class="h-3.5 w-3.5" />
          {{ c?.arahan || `Cabaran ${i + 1}` }}
        </li>
      </ul>
      <div v-if="!compact && result.tandaDeepfake?.length" class="rounded-xl bg-rose-50 px-3 py-2.5">
        <p class="flex items-center gap-1.5 text-xs font-semibold text-rose-800"><ShieldAlert class="h-3.5 w-3.5" /> Tanda spoofing / deepfake dikesan</p>
        <ul class="mt-1 list-disc pl-5 text-xs text-rose-700">
          <li v-for="(t, i) in result.tandaDeepfake" :key="i">{{ t }}</li>
        </ul>
      </div>
    </div>
  </div>
</template>
