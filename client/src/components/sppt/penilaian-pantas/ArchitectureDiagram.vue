<script setup lang="ts">
import { computed, type Component } from "vue";
import {
  ArrowDown,
  BrainCircuit,
  Database,
  Landmark,
  Layers,
  MonitorSmartphone,
  Network,
  Route,
  ScanFace,
  Send,
  Server,
  ShieldAlert,
  ShieldCheck,
  Sparkles,
  TrendingUp,
  UserCheck,
  Zap,
} from "lucide-vue-next";

import type { PpRun } from "@/types";

const props = withDefaults(defineProps<{ run?: PpRun | null }>(), { run: null });

const STEPS: { key: string; kod: string; n: number; title: string; desc: string; icon: Component; c: string }[] = [
  { key: "ekyc", kod: "ekyc", n: 1, title: "e-KYC", desc: "OCR MyKad + liveness + padanan wajah", icon: ScanFace, c: "C2" },
  { key: "peraturan", kod: "peraturan", n: 2, title: "Peraturan kelayakan", desc: "Peraturan keras TEKUN", icon: ShieldCheck, c: "C1" },
  { key: "penipuan", kod: "penipuan", n: 3, title: "Rangkaian penipuan", desc: "Analisis graf entiti", icon: Network, c: "C3" },
  { key: "skorKredit", kod: "skor_kredit", n: 4, title: "Skor kredit", desc: "Data alternatif, boleh-jelas", icon: TrendingUp, c: "C1" },
  { key: "cadangan", kod: "cadangan", n: 5, title: "Penghalaan", desc: "Laluan & cadangan had", icon: Route, c: "C1" },
];

const LAYERS: { icon: Component; t: string; items: string[]; cls: string }[] = [
  { icon: MonitorSmartphone, t: "Aplikasi", items: ["Vue 3 SPA (portal pemohon & pegawai)", "Laravel 13 API · Sanctum"], cls: "border-sky-200 bg-sky-50/60" },
  { icon: Database, t: "Data", items: ["MySQL (permohonan, log audit)", "Storan dokumen (MyKad, penyata)"], cls: "border-slate-200 bg-slate-50" },
  { icon: BrainCircuit, t: "AI / ML", items: ["Scorecard boleh-jelas (data alternatif)", "Analisis graf rangkaian penipuan"], cls: "border-emerald-200 bg-emerald-50/60" },
  { icon: Sparkles, t: "LLM", items: ["Claude melalui modul Penyedia AI", "Boleh diganti model on-premise"], cls: "border-violet-200 bg-violet-50/60" },
  { icon: ShieldCheck, t: "Tadbir urus", items: ["Log audit setiap keputusan", "Human-in-the-loop · kawalan bias", "Patuh PDPA 2010"], cls: "border-amber-200 bg-amber-50/60" },
];

function ms(key: string, kod: string): string {
  const v = props.run?.langkah?.[key]?.ms ?? props.run?.langkah?.[kod]?.ms;
  if (typeof v !== "number") return "";
  return v < 1000 ? `${v} ms` : `${(v / 1000).toFixed(1)} s`;
}

const e2e = computed(() => {
  const v = props.run?.hujungKeHujungMs;
  if (typeof v !== "number") return "";
  const s = Math.round(v / 100) / 10;
  return s >= 60 ? `${Math.floor(s / 60)} min ${Math.round(s % 60)} s` : `${s.toFixed(1)} s`;
});
const laluan = computed(() => props.run?.laluan ?? null);
</script>

<template>
  <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_300px]">
    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex flex-wrap items-center gap-2">
        <Layers class="h-4 w-4 text-violet-600" />
        <h3 class="text-sm font-semibold text-slate-900">Aliran C1 — dari “Hantar” ke cadangan keputusan</h3>
        <span v-if="run" class="ml-auto rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-700 ring-1 ring-violet-200">
          Anotasi: penilaian #{{ run.id }}<template v-if="e2e"> · E2E {{ e2e }}</template>
        </span>
      </div>

      <div class="flex flex-col items-center">
        <div class="flex items-center gap-2 rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow">
          <Send class="h-4 w-4" /> Pemohon tekan Hantar
        </div>
        <ArrowDown class="my-1 h-5 w-5 text-slate-300" />
        <div class="flex items-center gap-2 rounded-lg border border-sky-300 bg-sky-50 px-5 py-2 text-sm font-semibold text-sky-900">
          <Server class="h-4 w-4" /> API Gateway (Laravel)
        </div>
        <ArrowDown class="my-1 h-5 w-5 text-slate-300" />

        <div class="w-full rounded-xl border-2 border-dashed border-violet-300 bg-violet-50/40 p-3">
          <p class="mb-2 text-center text-xs font-bold uppercase tracking-wider text-violet-700">Enjin Penilaian Pantas</p>
          <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
            <div v-for="s in STEPS" :key="s.key" class="relative rounded-lg border border-violet-200 bg-white p-2.5 shadow-sm">
              <div class="flex items-center gap-1.5">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-violet-600 text-[10px] font-bold text-white">{{ s.n }}</span>
                <component :is="s.icon" class="h-4 w-4 text-violet-600" />
                <span class="ml-auto text-[9px] font-semibold text-slate-400">{{ s.c }}</span>
              </div>
              <p class="mt-1 text-xs font-semibold text-slate-800">{{ s.title }}</p>
              <p class="text-[10px] leading-tight text-slate-500">{{ s.desc }}</p>
              <p v-if="ms(s.key, s.kod)" class="mt-1 inline-block rounded bg-emerald-50 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-emerald-700">
                {{ ms(s.key, s.kod) }}
              </p>
            </div>
          </div>
        </div>
        <ArrowDown class="my-1 h-5 w-5 text-slate-300" />

        <div class="relative my-2 flex h-28 w-28 items-center justify-center">
          <div class="absolute inset-3 rotate-45 rounded-md border-2 border-amber-400 bg-amber-50" />
          <p class="relative px-2 text-center text-[11px] font-semibold leading-tight text-amber-900">Risiko rendah &amp; tiada isu?</p>
        </div>

        <div class="grid w-full max-w-xl grid-cols-2 gap-3">
          <div class="flex flex-col items-center">
            <span class="text-[11px] font-bold text-emerald-700">Ya</span>
            <ArrowDown class="my-0.5 h-4 w-4 text-emerald-400" />
            <div class="w-full rounded-lg border-2 px-3 py-2 text-center" :class="laluan === 'pantas' ? 'border-emerald-500 bg-emerald-100 shadow-md' : 'border-emerald-300 bg-emerald-50'">
              <p class="flex items-center justify-center gap-1 text-xs font-bold text-emerald-900"><Zap class="h-3.5 w-3.5" /> Laluan pantas</p>
              <p class="text-[10px] text-emerald-800">Kelulusan satu klik oleh pegawai</p>
            </div>
          </div>
          <div class="flex flex-col items-center">
            <span class="text-[11px] font-bold text-amber-700">Tidak</span>
            <ArrowDown class="my-0.5 h-4 w-4 text-amber-400" />
            <div
              class="w-full rounded-lg border-2 px-3 py-2 text-center"
              :class="laluan && laluan !== 'pantas' ? 'border-amber-500 bg-amber-100 shadow-md' : 'border-amber-300 bg-amber-50'"
            >
              <p class="flex items-center justify-center gap-1 text-xs font-bold text-amber-900"><UserCheck class="h-3.5 w-3.5" /> Semakan pegawai</p>
              <p class="text-[10px] text-amber-800">Catatan justifikasi wajib</p>
            </div>
          </div>
        </div>
        <ArrowDown class="my-1 h-5 w-5 text-slate-300" />
        <div class="flex items-center gap-2 rounded-lg border-2 border-rose-300 bg-rose-50 px-5 py-2 text-sm font-semibold text-rose-900">
          <ShieldAlert class="h-4 w-4" /> Amaran sebelum pengeluaran dana
        </div>
        <ArrowDown class="my-1 h-5 w-5 text-slate-300" />
        <div class="flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow">
          <Landmark class="h-4 w-4" /> Buka akaun &amp; salur dana
        </div>
      </div>
    </article>

    <aside class="space-y-2">
      <p class="px-1 text-xs font-semibold uppercase tracking-wider text-slate-500">Lapisan seni bina (on-premise)</p>
      <div v-for="l in LAYERS" :key="l.t" class="rounded-lg border p-3" :class="l.cls">
        <p class="flex items-center gap-1.5 text-sm font-semibold text-slate-800"><component :is="l.icon" class="h-4 w-4 text-slate-600" /> {{ l.t }}</p>
        <ul class="mt-1 space-y-0.5 text-xs text-slate-600">
          <li v-for="it in l.items" :key="it">• {{ it }}</li>
        </ul>
      </div>
    </aside>
  </div>
</template>
