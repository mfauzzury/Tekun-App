<script setup lang="ts">
import { Inbox, Loader2, RefreshCw, Search } from "lucide-vue-next";

import type { PpPermohonanRow } from "@/types";

defineProps<{
  rows: PpPermohonanRow[];
  loading: boolean;
  error: string;
  selectedId: number | null;
  q: string;
  total?: number | null;
  refreshing?: boolean;
}>();
const emit = defineEmits<{ (e: "select", id: number): void; (e: "update:q", v: string): void; (e: "refresh"): void }>();

const rm = new Intl.NumberFormat("ms-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function fmtRM(v?: number | string | null) {
  const n = Number(v);
  return v !== null && v !== undefined && v !== "" && Number.isFinite(n) ? `RM ${rm.format(n)}` : "—";
}
function mmss(ms?: number | null) {
  if (typeof ms !== "number" || !Number.isFinite(ms)) return "";
  if (ms >= 3600000) return `${Math.floor(ms / 3600000)}j+`;
  const s = Math.floor(ms / 1000);
  return `${String(Math.floor(s / 60)).padStart(2, "0")}:${String(s % 60).padStart(2, "0")}`;
}
const LALUAN: Record<string, { label: string; cls: string }> = {
  pantas: { label: "Pantas", cls: "bg-emerald-50 text-emerald-700 ring-emerald-200" },
  semakan_pegawai: { label: "Semakan pegawai", cls: "bg-amber-50 text-amber-800 ring-amber-200" },
  cadang_tolak: { label: "Cadang tolak", cls: "bg-rose-50 text-rose-700 ring-rose-200" },
};
const KEPUTUSAN: Record<string, string> = { lulus: "Lulus", tolak: "Tolak", rujuk: "Dirujuk" };
</script>

<template>
  <article class="flex min-h-0 flex-col rounded-lg border border-slate-200 bg-white shadow-sm">
    <header class="space-y-2 border-b border-slate-100 px-3 py-2.5">
      <div class="flex items-center gap-2">
        <Inbox class="h-4 w-4 text-violet-600" />
        <h2 class="text-sm font-semibold text-slate-900">Permohonan</h2>
        <span v-if="total != null" class="text-xs text-slate-400">{{ total }}</span>
        <span class="ml-auto inline-flex items-center gap-1 text-[10px] text-slate-400" title="Dikemas kini automatik setiap 10 saat">
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" /> Langsung
        </span>
        <button type="button" class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Muat semula" @click="emit('refresh')">
          <RefreshCw class="h-3.5 w-3.5" :class="refreshing ? 'animate-spin' : ''" />
        </button>
      </div>
      <div class="relative">
        <Search class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
        <input
          :value="q"
          type="search"
          placeholder="Cari nama atau no. rujukan…"
          class="w-full rounded-lg border border-slate-300 py-1.5 pl-8 pr-3 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          @input="emit('update:q', ($event.target as HTMLInputElement).value)"
        />
      </div>
    </header>

    <div class="min-h-0 flex-1 overflow-y-auto">
      <div v-if="loading && !rows.length" class="flex items-center gap-2 p-4 text-sm text-slate-500">
        <Loader2 class="h-4 w-4 animate-spin" /> Memuatkan…
      </div>
      <p v-else-if="error && !rows.length" class="m-3 rounded-md bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ error }}</p>
      <p v-else-if="!rows.length" class="p-4 text-center text-xs text-slate-500">Tiada permohonan ditemui.</p>
      <ul v-else class="divide-y divide-slate-100">
        <li v-for="r in rows" :key="r.id">
          <button
            type="button"
            class="w-full border-l-[3px] px-3 py-2.5 text-left transition-colors"
            :class="selectedId === r.id ? 'border-violet-600 bg-violet-50/70' : 'border-transparent hover:bg-slate-50'"
            @click="emit('select', r.id)"
          >
            <div class="flex items-center gap-2">
              <span class="truncate font-mono text-[11px] font-medium text-slate-500">{{ r.noRujukan || `#${r.id}` }}</span>
              <span class="ml-auto shrink-0 text-xs font-semibold tabular-nums text-slate-800">{{ fmtRM(r.jumlahPermohonan) }}</span>
            </div>
            <p class="truncate text-sm font-semibold text-slate-900">{{ r.nama || "—" }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-1">
              <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">{{ r.status || "—" }}</span>
              <template v-if="r.penilaianTerkini">
                <span
                  v-if="r.penilaianTerkini.status === 'berjalan'"
                  class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-1.5 py-0.5 text-[10px] font-medium text-violet-700 ring-1 ring-inset ring-violet-200"
                >
                  <Loader2 class="h-2.5 w-2.5 animate-spin" /> Sedang dinilai
                </span>
                <span
                  v-else-if="r.penilaianTerkini.laluan"
                  class="rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset"
                  :class="LALUAN[r.penilaianTerkini.laluan]?.cls ?? 'bg-slate-50 text-slate-600 ring-slate-200'"
                >
                  {{ LALUAN[r.penilaianTerkini.laluan]?.label ?? r.penilaianTerkini.laluan }}
                  <template v-if="mmss(r.penilaianTerkini.hujungKeHujungMs)"> · {{ mmss(r.penilaianTerkini.hujungKeHujungMs) }}</template>
                </span>
                <span v-if="r.penilaianTerkini.keputusanPegawai" class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-medium text-white">
                  {{ KEPUTUSAN[r.penilaianTerkini.keputusanPegawai] ?? r.penilaianTerkini.keputusanPegawai }}
                </span>
              </template>
              <span v-else class="text-[10px] text-slate-400">Belum dinilai</span>
            </div>
          </button>
        </li>
      </ul>
    </div>
  </article>
</template>
