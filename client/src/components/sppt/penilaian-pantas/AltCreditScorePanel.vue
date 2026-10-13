<script setup lang="ts">
import { computed, ref } from "vue";
import { BadgeCheck, Box, CheckCircle2, CircleSlash, Scale, Sparkles, TrendingUp, XCircle } from "lucide-vue-next";

import CreditScoreSpeedometer from "@/components/sppt/CreditScoreSpeedometer.vue";
import type { PpSkorKreditHasil } from "@/types";
import PpBadge from "./PpBadge.vue";
import PpCard from "./PpCard.vue";

const props = defineProps<{ hasil: PpSkorKreditHasil | null }>();

const rm = new Intl.NumberFormat("ms-MY", { style: "currency", currency: "MYR", minimumFractionDigits: 2 });
function fmtRM(v?: number | null) {
  return typeof v === "number" && Number.isFinite(v) ? rm.format(v).replace("MYR", "RM").replace(/ /g, " ") : "—";
}

const skor = computed(() => Math.round(Number(props.hasil?.skor ?? 0)));
const band = computed(() => (skor.value >= 80 ? "green" : skor.value >= 60 ? "amber" : "red"));

const faktor = computed(() =>
  [...(props.hasil?.faktor ?? [])].filter(Boolean).sort((a, b) => Number(b?.sumbangan ?? 0) - Number(a?.sumbangan ?? 0)),
);
const maxAbs = computed(() => Math.max(1, ...faktor.value.map((f) => Math.abs(Number(f?.sumbangan ?? 0)))));
const expanded = ref<number | null>(null);

const atribut = computed(() =>
  (props.hasil?.bias?.atributDikecualikan ?? []).map((a) => (typeof a === "string" ? a : a?.label || a?.kod || "")).filter(Boolean),
);

const dsr = computed(() => {
  const v = props.hasil?.nisbahKhidmatHutang;
  return typeof v === "number" && Number.isFinite(v) ? `${(v * 100).toFixed(1)}%` : "—";
});

function sign(n?: number | null) {
  const v = Number(n ?? 0);
  return `${v > 0 ? "+" : ""}${Number.isInteger(v) ? v : v.toFixed(1)}`;
}
</script>

<template>
  <PpCard title="Skor kredit data alternatif" subtitle="C1 · Boleh-jelas (explainable) — sesuai untuk usahawan fail nipis" :icon="TrendingUp" icon-class="text-emerald-600">
    <template #actions>
      <PpBadge v-if="hasil?.failNipis" tone="sky">Fail nipis — dinilai dengan data alternatif</PpBadge>
    </template>

    <p v-if="!hasil" class="rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-500">Skor kredit akan dipaparkan selepas langkah 4 selesai.</p>

    <div v-else class="space-y-4">
      <div class="grid gap-4 md:grid-cols-[auto_minmax(0,1fr)]">
        <div class="flex flex-col items-center rounded-lg border border-slate-200 px-3 pb-3 pt-2">
          <CreditScoreSpeedometer :score="skor" :band-color="band" :size="220" :category="`Gred ${hasil.gred ?? '—'} · Risiko ${hasil.tahapRisiko ?? '—'}`" />
          <p class="mt-1 text-[11px] text-slate-500">
            Keyakinan data: <span class="font-semibold text-slate-700">{{ hasil.keyakinan ?? "—" }}%</span>
            <template v-if="hasil.skorAsas != null"> · Skor asas {{ hasil.skorAsas }}</template>
          </p>
        </div>
        <div class="space-y-3">
          <div class="grid grid-cols-2 gap-2 lg:grid-cols-4">
            <div class="rounded-lg bg-slate-50 px-3 py-2">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Pendapatan disahkan / bln</p>
              <p class="mt-0.5 text-sm font-bold text-slate-900">{{ fmtRM(hasil.pendapatanDisahkanBulanan) }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 px-3 py-2">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Ansuran dicadang</p>
              <p class="mt-0.5 text-sm font-bold text-slate-900">{{ fmtRM(hasil.ansuranDicadang) }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 px-3 py-2">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Nisbah khidmat hutang</p>
              <p class="mt-0.5 text-sm font-bold text-slate-900">{{ dsr }}</p>
            </div>
            <div class="rounded-lg bg-emerald-50 px-3 py-2">
              <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-700">Cadangan had</p>
              <p class="mt-0.5 text-sm font-bold text-emerald-900">{{ fmtRM(hasil.cadanganHad) }}</p>
            </div>
          </div>
          <div v-if="hasil.sumberData?.length">
            <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Sumber data</p>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="(s, i) in hasil.sumberData"
                :key="s?.kod ?? i"
                class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px]"
                :class="s?.tersedia ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-400'"
                :title="s?.nilaiRingkas ?? ''"
              >
                <CheckCircle2 v-if="s?.tersedia" class="h-3 w-3" />
                <CircleSlash v-else class="h-3 w-3" />
                <span class="font-medium">{{ s?.label }}</span>
                <span v-if="s?.tersedia && s?.nilaiRingkas" class="text-emerald-700/80">· {{ s.nilaiRingkas }}</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Diverging factor chart -->
      <section v-if="faktor.length" class="rounded-lg border border-slate-200 p-3">
        <div class="mb-2 flex items-center justify-between text-[10px] font-semibold uppercase tracking-wider text-slate-400">
          <span class="text-rose-500">← Menurunkan skor</span>
          <span>Sumbangan faktor (mata)</span>
          <span class="text-emerald-600">Menaikkan skor →</span>
        </div>
        <ul class="space-y-1">
          <li v-for="(f, i) in faktor" :key="f?.kod ?? i">
            <button type="button" class="grid w-full grid-cols-[minmax(0,10rem)_minmax(0,1fr)_3rem] items-center gap-2 rounded px-1 py-1 text-left hover:bg-slate-50 sm:grid-cols-[minmax(0,13rem)_minmax(0,1fr)_3rem]" :title="f?.penerangan ?? ''" @click="expanded = expanded === i ? null : i">
              <span class="min-w-0">
                <span class="block truncate text-xs font-medium text-slate-700">{{ f?.label }}</span>
                <span class="block truncate text-[10px] text-slate-400">{{ f?.nilai ?? "" }}</span>
              </span>
              <span class="relative h-4">
                <span class="absolute inset-y-0 left-1/2 w-px bg-slate-300" />
                <span
                  v-if="Number(f?.sumbangan ?? 0) > 0"
                  class="absolute inset-y-0.5 left-1/2 rounded-r bg-emerald-500"
                  :style="{ width: `${(Math.abs(Number(f?.sumbangan ?? 0)) / maxAbs) * 50}%` }"
                />
                <span
                  v-else-if="Number(f?.sumbangan ?? 0) < 0"
                  class="absolute inset-y-0.5 right-1/2 rounded-l bg-rose-500"
                  :style="{ width: `${(Math.abs(Number(f?.sumbangan ?? 0)) / maxAbs) * 50}%` }"
                />
                <span v-else class="absolute left-1/2 top-1/2 h-1.5 w-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-slate-400" />
              </span>
              <span class="text-right font-mono text-xs font-semibold tabular-nums" :class="Number(f?.sumbangan ?? 0) > 0 ? 'text-emerald-700' : Number(f?.sumbangan ?? 0) < 0 ? 'text-rose-700' : 'text-slate-500'">
                {{ sign(f?.sumbangan) }}
              </span>
            </button>
            <p v-if="expanded === i && f?.penerangan" class="mx-1 mb-1 rounded bg-slate-50 px-2 py-1 text-[11px] text-slate-600">{{ f.penerangan }}</p>
          </li>
        </ul>
      </section>

      <!-- AI explanation -->
      <section v-if="hasil.penjelasanAi" class="rounded-lg border border-violet-200 bg-violet-50/50 p-3">
        <div class="mb-1 flex items-center gap-2">
          <Sparkles class="h-4 w-4 text-violet-600" />
          <p class="text-sm font-semibold text-slate-800">Penjelasan untuk pegawai</p>
          <PpBadge :tone="hasil.penjelasanSumber === 'ai' ? 'violet' : 'slate'">{{ hasil.penjelasanSumber === "ai" ? "Dijana AI" : "Templat" }}</PpBadge>
        </div>
        <p class="whitespace-pre-line text-xs leading-relaxed text-slate-700">{{ hasil.penjelasanAi }}</p>
      </section>

      <div class="grid gap-3 lg:grid-cols-2">
        <!-- Model card -->
        <section v-if="hasil.model" class="rounded-lg border border-slate-200 p-3">
          <p class="mb-1 flex items-center gap-1.5 text-sm font-semibold text-slate-800"><Box class="h-4 w-4 text-slate-500" /> Kad model</p>
          <dl class="grid grid-cols-[6rem_1fr] gap-y-1 text-xs">
            <dt class="text-slate-500">Nama</dt>
            <dd class="font-medium text-slate-800">{{ hasil.model.nama || "—" }}</dd>
            <dt class="text-slate-500">Jenis</dt>
            <dd class="font-medium text-slate-800">{{ hasil.model.jenis || "—" }}</dd>
            <template v-if="hasil.model.versiData">
              <dt class="text-slate-500">Versi data</dt>
              <dd class="font-medium text-slate-800">{{ hasil.model.versiData }}</dd>
            </template>
          </dl>
          <div v-if="hasil.model.ciriUtama?.length" class="mt-2 flex flex-wrap gap-1">
            <PpBadge v-for="(c, i) in hasil.model.ciriUtama" :key="i">{{ c }}</PpBadge>
          </div>
        </section>

        <!-- Bias controls -->
        <section v-if="hasil.bias" class="rounded-lg border border-slate-200 p-3">
          <p class="mb-1 flex items-center gap-1.5 text-sm font-semibold text-slate-800"><Scale class="h-4 w-4 text-slate-500" /> Kawalan bias</p>
          <div v-if="atribut.length" class="mb-2">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Atribut dilindungi — dikecualikan daripada model</p>
            <div class="mt-1 flex flex-wrap gap-1">
              <PpBadge v-for="a in atribut" :key="a" tone="slate"><CircleSlash class="h-3 w-3" /> {{ a }}</PpBadge>
            </div>
          </div>
          <div v-if="hasil.bias.ujianKontrafaktual?.length" class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead class="text-left text-[10px] uppercase tracking-wider text-slate-400">
                <tr>
                  <th class="py-1 pr-2 font-semibold">Atribut</th>
                  <th class="py-1 pr-2 font-semibold">Asal → uji</th>
                  <th class="py-1 pr-2 text-right font-semibold">Skor</th>
                  <th class="py-1 text-right font-semibold">Beza</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(u, i) in hasil.bias.ujianKontrafaktual" :key="i">
                  <td class="py-1 pr-2 font-medium text-slate-700">{{ u?.atribut }}</td>
                  <td class="py-1 pr-2 text-slate-500">{{ u?.nilaiAsal ?? "—" }} → {{ u?.nilaiUji ?? "—" }}</td>
                  <td class="py-1 pr-2 text-right font-mono tabular-nums text-slate-700">{{ u?.skorAsal ?? "—" }} → {{ u?.skorUji ?? "—" }}</td>
                  <td class="py-1 text-right font-mono font-semibold tabular-nums" :class="Number(u?.beza ?? 0) === 0 ? 'text-emerald-700' : 'text-amber-700'">{{ sign(u?.beza) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p
            class="mt-2 flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium"
            :class="hasil.bias.lulus === false ? 'bg-rose-50 text-rose-800' : 'bg-emerald-50 text-emerald-800'"
          >
            <XCircle v-if="hasil.bias.lulus === false" class="h-3.5 w-3.5" />
            <BadgeCheck v-else class="h-3.5 w-3.5" />
            {{ hasil.bias.lulus === false ? "Ujian kontrafaktual gagal — semakan pegawai diperlukan" : "Lulus ujian kontrafaktual — skor tidak berubah apabila atribut dilindungi ditukar" }}
          </p>
          <p v-if="hasil.bias.nota" class="mt-1 text-[11px] text-slate-500">{{ hasil.bias.nota }}</p>
        </section>
      </div>
    </div>
  </PpCard>
</template>
