<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { BadgeCheck, CircleSlash, FlaskConical, Loader2, RefreshCw, Scale, UserCheck, XCircle } from "lucide-vue-next";

import { getPenilaianPantasKeadilan } from "@/api/sppt";
import type { PpKeadilan } from "@/types";
import PpCard from "./PpCard.vue";

const data = ref<PpKeadilan | null>(null);
const loading = ref(true);
const error = ref("");

async function load() {
  loading.value = true;
  error.value = "";
  try {
    const res = await getPenilaianPantasKeadilan();
    data.value = res?.data ?? null;
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : "Gagal memuatkan laporan keadilan";
  } finally {
    loading.value = false;
  }
}
onMounted(load);

const LABEL: Record<string, string> = { jantina: "Jantina", bangsa: "Bangsa", negeri: "Negeri", umur: "Kumpulan umur" };
const label = (a?: string) => LABEL[a ?? ""] ?? (a ? a.charAt(0).toUpperCase() + a.slice(1) : "—");

const groups = computed(() => {
  const map = new Map<string, NonNullable<PpKeadilan["kumpulan"]>>();
  for (const k of data.value?.kumpulan ?? []) {
    if (!k) continue;
    const key = k.atribut ?? "lain";
    if (!map.has(key)) map.set(key, []);
    map.get(key)!.push(k);
  }
  return [...map.entries()].map(([atribut, rows]) => ({ atribut, rows }));
});

type Ratio = NonNullable<PpKeadilan["nisbahImpakBuruk"]>[number];
function lulus(r: Ratio): boolean {
  const v = r?.lulusPeraturan45 ?? r?.lulusPeraturan_4_5;
  return typeof v === "boolean" ? v : Number(r?.nisbah ?? 0) >= 0.8;
}
function pct(v?: number | null) {
  if (typeof v !== "number" || !Number.isFinite(v)) return 0;
  return Math.max(0, Math.min(100, v <= 1 ? v * 100 : v));
}
function fmtDate(iso?: string | null) {
  if (!iso) return "";
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? "" : d.toLocaleString("ms-MY", { dateStyle: "medium", timeStyle: "short" });
}
</script>

<template>
  <div class="space-y-4">
    <div class="grid gap-3 md:grid-cols-4">
      <div v-for="(b, i) in [
        { icon: CircleSlash, t: 'Atribut dilindungi dikecualikan', d: 'Jantina, bangsa, agama dan negeri tidak dimasukkan sebagai input model.' },
        { icon: FlaskConical, t: 'Ujian kontrafaktual setiap permohonan', d: 'Skor dikira semula dengan atribut dilindungi ditukar — perbezaan mesti sifar.' },
        { icon: Scale, t: 'Pemantauan portfolio (peraturan 4/5)', d: 'Nisbah impak buruk antara kumpulan mesti ≥ 0.80.' },
        { icon: UserCheck, t: 'Human-in-the-loop', d: 'AI hanya mencadang; keputusan akhir dan justifikasi direkod oleh pegawai.' },
      ]" :key="i" class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
        <component :is="b.icon" class="h-4 w-4 text-violet-600" />
        <p class="mt-1.5 text-sm font-semibold text-slate-800">{{ b.t }}</p>
        <p class="mt-0.5 text-xs text-slate-500">{{ b.d }}</p>
      </div>
    </div>

    <div v-if="loading" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500">
      <Loader2 class="h-4 w-4 animate-spin" /> Memuatkan laporan keadilan…
    </div>
    <p v-else-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
      {{ error }}
      <button type="button" class="ml-2 font-semibold underline" @click="load">Cuba semula</button>
    </p>

    <template v-else-if="data">
      <PpCard title="Nisbah impak buruk (adverse impact ratio)" :subtitle="`Berdasarkan ${data.jumlahPermohonan ?? 0} permohonan · dijana ${fmtDate(data.dijanaPada)}`" :icon="Scale" icon-class="text-violet-600" :collapsible="false">
        <template #actions>
          <button type="button" class="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50" @click="load">
            <RefreshCw class="h-3.5 w-3.5" /> Muat semula
          </button>
        </template>
        <div v-if="data.nisbahImpakBuruk?.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="(r, i) in data.nisbahImpakBuruk"
            :key="i"
            class="rounded-lg border p-3"
            :class="lulus(r) ? 'border-emerald-200 bg-emerald-50/50' : 'border-rose-200 bg-rose-50/50'"
          >
            <div class="flex items-center justify-between">
              <p class="text-sm font-semibold text-slate-800">{{ label(r?.atribut) }}</p>
              <span class="inline-flex items-center gap-1 text-xs font-semibold" :class="lulus(r) ? 'text-emerald-700' : 'text-rose-700'">
                <BadgeCheck v-if="lulus(r)" class="h-3.5 w-3.5" /><XCircle v-else class="h-3.5 w-3.5" />
                {{ lulus(r) ? "Lulus peraturan 4/5" : "Gagal peraturan 4/5" }}
              </span>
            </div>
            <p class="mt-1 font-mono text-3xl font-bold tabular-nums" :class="lulus(r) ? 'text-emerald-700' : 'text-rose-700'">
              {{ typeof r?.nisbah === "number" ? r.nisbah.toFixed(2) : "—" }}
            </p>
            <div class="relative mt-1 h-2 rounded-full bg-slate-200">
              <div class="h-full rounded-full" :class="lulus(r) ? 'bg-emerald-500' : 'bg-rose-500'" :style="{ width: `${pct(r?.nisbah)}%` }" />
              <div class="absolute -top-1 h-4 w-0.5 bg-slate-700" style="left: 80%" title="Ambang 0.80" />
            </div>
            <p class="mt-1.5 text-[11px] text-slate-500">
              Terendah: <span class="font-medium text-slate-700">{{ r?.kumpulanTerendah ?? "—" }}</span> · Tertinggi:
              <span class="font-medium text-slate-700">{{ r?.kumpulanTertinggi ?? "—" }}</span>
            </p>
          </div>
        </div>
        <p v-else class="text-xs text-slate-500">Belum cukup data untuk mengira nisbah.</p>
        <p v-if="data.nota" class="mt-3 text-xs text-slate-500">{{ data.nota }}</p>
      </PpCard>

      <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
        <PpCard v-for="g in groups" :key="g.atribut" :title="`Mengikut ${label(g.atribut).toLowerCase()}`" :collapsible="false">
          <table class="w-full text-xs">
            <thead class="text-left text-[10px] uppercase tracking-wider text-slate-400">
              <tr>
                <th class="pb-1.5 font-semibold">Kumpulan</th>
                <th class="pb-1.5 text-right font-semibold">n</th>
                <th class="pb-1.5 pl-3 font-semibold">Purata skor</th>
                <th class="pb-1.5 pl-3 font-semibold">% risiko rendah</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(row, i) in g.rows" :key="i">
                <td class="py-1.5 font-medium text-slate-700">{{ row.nilai || "—" }}</td>
                <td class="py-1.5 text-right font-mono tabular-nums text-slate-500">{{ row.bilangan ?? 0 }}</td>
                <td class="py-1.5 pl-3">
                  <div class="flex items-center gap-1.5">
                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-violet-500" :style="{ width: `${pct(row.purataSkor != null ? row.purataSkor / 100 : 0)}%` }" /></div>
                    <span class="font-mono tabular-nums text-slate-700">{{ row.purataSkor ?? "—" }}</span>
                  </div>
                </td>
                <td class="py-1.5 pl-3">
                  <div class="flex items-center gap-1.5">
                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-emerald-500" :style="{ width: `${pct(row.kadarRisikoRendah)}%` }" /></div>
                    <span class="font-mono tabular-nums text-slate-700">{{ pct(row.kadarRisikoRendah).toFixed(0) }}%</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </PpCard>
      </div>
    </template>
  </div>
</template>
