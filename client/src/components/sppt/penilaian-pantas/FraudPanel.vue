<script setup lang="ts">
import { computed } from "vue";
import { AlertTriangle, ListChecks, Network, ShieldAlert, ShieldCheck, Users } from "lucide-vue-next";

import type { PpFraudResult } from "@/types";
import DokumenJualanCheck from "./DokumenJualanCheck.vue";
import FraudNetworkGraph from "./FraudNetworkGraph.vue";
import PpBadge from "./PpBadge.vue";
import PpCard from "./PpCard.vue";

const props = defineProps<{ permohonanId: number; hasil: PpFraudResult | null }>();
const emit = defineEmits<{ (e: "select", permohonanId: number): void }>();

const skor = computed(() => Math.max(0, Math.min(100, Math.round(Number(props.hasil?.skorRisiko ?? 0)))));
const tahap = computed(() => String(props.hasil?.tahap ?? "rendah"));

function tone(t?: string | null) {
  return t === "tinggi" ? "rose" : t === "sederhana" ? "amber" : "emerald";
}
function tahapLabel(t?: string | null) {
  return t === "tinggi" ? "Tinggi" : t === "sederhana" ? "Sederhana" : "Rendah";
}
const ringColor = computed(() => (tahap.value === "tinggi" ? "#e11d48" : tahap.value === "sederhana" ? "#d97706" : "#059669"));
const C = 2 * Math.PI * 34;
</script>

<template>
  <PpCard title="Analisis rangkaian penipuan" subtitle="C3 · Graf entiti dikongsi (telefon, alamat, IC, e-mel, akaun bank)" :icon="Network" icon-class="text-rose-600">
    <template #actions>
      <PpBadge v-if="hasil" :tone="tone(tahap)">Risiko {{ tahapLabel(tahap) }}</PpBadge>
    </template>

    <p v-if="!hasil" class="rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-500">Analisis rangkaian akan dipaparkan selepas langkah 3 selesai.</p>

    <div v-else class="space-y-4">
      <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_260px]">
        <FraudNetworkGraph :nodes="hasil.rangkaian?.nodes" :edges="hasil.rangkaian?.edges" @select="emit('select', $event)" />

        <div class="space-y-3">
          <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
            <svg viewBox="0 0 80 80" class="h-20 w-20 shrink-0 -rotate-90">
              <circle cx="40" cy="40" r="34" fill="none" stroke="#f1f5f9" stroke-width="8" />
              <circle
                cx="40" cy="40" r="34" fill="none" :stroke="ringColor" stroke-width="8" stroke-linecap="round"
                :stroke-dasharray="`${(skor / 100) * C} ${C}`"
              />
              <text x="40" y="40" text-anchor="middle" dominant-baseline="central" transform="rotate(90 40 40)" class="fill-slate-900 text-[20px] font-bold">{{ skor }}</text>
            </svg>
            <div>
              <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Skor risiko penipuan</p>
              <p class="text-sm font-semibold text-slate-800">Tahap {{ tahapLabel(tahap) }}</p>
              <p v-if="hasil.amaran" class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-rose-700">
                <ShieldAlert class="h-3.5 w-3.5" /> Amaran penipuan aktif
              </p>
              <p v-else class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                <ShieldCheck class="h-3.5 w-3.5" /> Tiada amaran
              </p>
            </div>
          </div>

          <div v-if="hasil.cadanganTindakan?.length" class="rounded-lg border border-slate-200 p-3">
            <p class="mb-1.5 flex items-center gap-1 text-xs font-semibold text-slate-700"><ListChecks class="h-3.5 w-3.5" /> Cadangan tindakan</p>
            <ul class="space-y-1 text-xs text-slate-600">
              <li v-for="(t, i) in hasil.cadanganTindakan" :key="i" class="flex gap-1.5"><span class="text-slate-400">{{ i + 1 }}.</span> {{ t }}</li>
            </ul>
          </div>
        </div>
      </div>

      <div v-if="hasil.isyarat?.length" class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Isyarat dikesan ({{ hasil.isyarat.length }})</p>
        <article
          v-for="(s, i) in hasil.isyarat"
          :key="s?.kod ?? i"
          class="rounded-lg border px-3 py-2"
          :class="s?.tahap === 'tinggi' ? 'border-rose-200 bg-rose-50/50' : s?.tahap === 'sederhana' ? 'border-amber-200 bg-amber-50/50' : 'border-slate-200'"
        >
          <div class="flex flex-wrap items-center gap-2">
            <AlertTriangle class="h-3.5 w-3.5" :class="s?.tahap === 'tinggi' ? 'text-rose-600' : 'text-amber-600'" />
            <p class="text-sm font-semibold text-slate-800">{{ s?.tajuk }}</p>
            <PpBadge :tone="tone(s?.tahap)">{{ tahapLabel(s?.tahap) }}</PpBadge>
          </div>
          <p v-if="s?.penerangan" class="mt-0.5 text-xs text-slate-600">{{ s.penerangan }}</p>
          <ul v-if="s?.bukti?.length" class="mt-1 list-disc pl-5 text-[11px] text-slate-500">
            <li v-for="(b, j) in s.bukti" :key="j">{{ b }}</li>
          </ul>
        </article>
      </div>
      <p v-else class="flex items-center gap-1.5 rounded-md bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
        <ShieldCheck class="h-3.5 w-3.5" /> Tiada isyarat penipuan dikesan pada rangkaian entiti pemohon.
      </p>

      <div v-if="hasil.kluster?.permohonanBerkaitan?.length" class="overflow-hidden rounded-lg border border-slate-200">
        <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-3 py-2">
          <Users class="h-3.5 w-3.5 text-slate-500" />
          <p class="text-xs font-semibold text-slate-700">Kluster permohonan berkaitan</p>
          <span class="ml-auto text-xs text-slate-500">Saiz kluster: {{ hasil.kluster?.saiz ?? hasil.kluster.permohonanBerkaitan.length }}</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead class="text-left text-[10px] uppercase tracking-wider text-slate-400">
              <tr>
                <th class="px-3 py-1.5 font-semibold">No. rujukan</th>
                <th class="px-3 py-1.5 font-semibold">Nama</th>
                <th class="px-3 py-1.5 font-semibold">Status</th>
                <th class="px-3 py-1.5 font-semibold">Kaitan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="p in hasil.kluster.permohonanBerkaitan" :key="p.id">
                <td class="px-3 py-1.5">
                  <button type="button" class="font-mono font-medium text-violet-700 hover:underline" @click="emit('select', p.id)">
                    {{ p.noRujukan || `#${p.id}` }}
                  </button>
                </td>
                <td class="px-3 py-1.5 text-slate-700">{{ p.nama || "—" }}</td>
                <td class="px-3 py-1.5 text-slate-500">{{ p.status || "—" }}</td>
                <td class="px-3 py-1.5">
                  <div class="flex flex-wrap gap-1">
                    <PpBadge v-for="(k, i) in p.kaitan ?? []" :key="i" tone="rose">{{ k }}</PpBadge>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <DokumenJualanCheck class="mt-4" :permohonan-id="permohonanId" />
  </PpCard>
</template>
