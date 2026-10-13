<script setup lang="ts">
import { computed } from "vue";
import { AlertTriangle, CheckCircle2, RefreshCw, ScanLine, ShieldAlert, Sparkles, Wand2 } from "lucide-vue-next";

import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PSkeleton from "@/components/pemohon/ui/PSkeleton.vue";
import type { PemohonMyKadScan } from "@/types";

const props = withDefaults(
  defineProps<{ scan: PemohonMyKadScan | null; scanning?: boolean; filledFields?: string[]; showAutofill?: boolean }>(),
  { scanning: false, filledFields: () => [], showAutofill: true },
);
defineEmits<{ autofill: []; rescan: [] }>();

const ocr = computed(() => props.scan?.ocr ?? null);
const ic = computed(() => props.scan?.ic ?? null);
const data = computed(() => ocr.value?.data ?? null);

const kualiti = computed(() => {
  const v = ocr.value?.kualiti?.skor;
  if (typeof v !== "number" || !Number.isFinite(v)) return null;
  return Math.max(0, Math.min(100, Math.round(v <= 1 ? v * 100 : v)));
});

const jantina = computed(() => {
  const j = (ic.value?.jantina || data.value?.jantina || "").toUpperCase();
  return j.startsWith("L") ? "Lelaki" : j.startsWith("P") || j.startsWith("W") ? "Perempuan" : "";
});

const rows = computed(() =>
  [
    { label: "No. Kad Pengenalan", value: ic.value?.noIc || data.value?.noIc, mono: true },
    { label: "Nama", value: data.value?.nama },
    { label: "Alamat", value: data.value?.alamat },
    { label: "Jantina", value: jantina.value },
    { label: "Tarikh lahir", value: ic.value?.tarikhLahir ? `${ic.value.tarikhLahir}${ic.value.umur != null ? ` · ${ic.value.umur} tahun` : ""}` : "" },
    { label: "Negeri lahir", value: ic.value?.negeriLahir },
    { label: "Agama", value: data.value?.agama },
    { label: "Warganegara", value: data.value?.warganegara },
  ].filter((r) => r.value),
);

const isu = computed(() => [...(ocr.value?.kualiti?.isu ?? []), ...(ic.value?.isu ?? [])]);
</script>

<template>
  <!-- Scanning -->
  <div v-if="scanning" class="rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50/70 to-white p-5" role="status" aria-live="polite">
    <div class="flex items-center gap-3">
      <span class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm">
        <ScanLine class="h-5 w-5 animate-pulse" />
      </span>
      <div>
        <p class="text-sm font-semibold text-ink-900">AI membaca MyKad anda…</p>
        <p class="text-xs text-slate-500">Mengekstrak nama, no. kad pengenalan dan alamat. Biasanya 5–15 saat.</p>
      </div>
    </div>
    <PSkeleton class="mt-4" :lines="3" />
  </div>

  <!-- Failed read -->
  <div v-else-if="scan && !ocr?.ok" class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
    <div class="flex items-start gap-3">
      <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-amber-900">MyKad tidak dapat dibaca sepenuhnya</p>
        <p class="mt-0.5 text-sm text-amber-800">{{ ocr?.message || "Pastikan gambar jelas, tidak silau dan keempat-empat bucu kad kelihatan." }}</p>
        <PButton class="mt-3" size="sm" variant="outline" @click="$emit('rescan')"><RefreshCw class="h-4 w-4" /> Imbas semula</PButton>
      </div>
    </div>
  </div>

  <!-- Result -->
  <div v-else-if="scan && ocr?.ok" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 px-4 py-3">
      <Sparkles class="h-4 w-4 text-brand-600" />
      <p class="text-sm font-semibold text-ink-900">Maklumat dibaca oleh AI</p>
      <PBadge v-if="ic?.valid" tone="success" dot>No. KP sah</PBadge>
      <PBadge v-else-if="ic" tone="warning" dot>No. KP perlu disemak</PBadge>
      <PBadge v-if="kualiti != null" :tone="kualiti >= 75 ? 'success' : kualiti >= 50 ? 'warning' : 'danger'" class="ml-auto">
        Kualiti imej {{ kualiti }}%
      </PBadge>
    </div>

    <dl class="divide-y divide-slate-100 px-4">
      <div v-for="r in rows" :key="r.label" class="grid grid-cols-[120px_minmax(0,1fr)] gap-3 py-2.5 text-sm sm:grid-cols-[160px_minmax(0,1fr)]">
        <dt class="text-slate-500">{{ r.label }}</dt>
        <dd class="break-words font-medium text-ink-900" :class="r.mono ? 'font-mono tracking-wide' : ''">{{ r.value }}</dd>
      </div>
    </dl>

    <div class="space-y-3 px-4 pb-4">
      <div v-if="ocr.adalahMykad === false" class="flex gap-2 rounded-xl bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
        <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" /> Imej ini mungkin bukan MyKad. Sila pastikan anda memuat naik MyKad asal.
      </div>
      <div v-if="ocr.tandaPalsu?.length" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5">
        <p class="flex items-center gap-1.5 text-sm font-semibold text-rose-800"><ShieldAlert class="h-4 w-4" /> Amaran: tanda pengubahsuaian dikesan</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs text-rose-700">
          <li v-for="(t, i) in ocr.tandaPalsu" :key="i">{{ t }}</li>
        </ul>
      </div>
      <div v-if="isu.length" class="rounded-xl bg-slate-50 px-3 py-2.5">
        <p class="text-xs font-semibold text-slate-600">Catatan kualiti</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs text-slate-600">
          <li v-for="(t, i) in isu" :key="i">{{ t }}</li>
        </ul>
      </div>

      <div v-if="showAutofill" class="flex flex-col gap-3 rounded-xl bg-brand-50/60 p-3 sm:flex-row sm:items-center">
        <p class="flex-1 text-sm text-ink-800">Jimat masa — isi maklumat diri anda terus daripada MyKad.</p>
        <PButton size="sm" @click="$emit('autofill')"><Wand2 class="h-4 w-4" /> Isi borang secara automatik</PButton>
      </div>
      <div v-if="filledFields.length" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5" role="status">
        <p class="flex items-center gap-1.5 text-sm font-semibold text-emerald-800"><CheckCircle2 class="h-4 w-4" /> {{ filledFields.length }} medan telah diisi</p>
        <div class="mt-2 flex flex-wrap gap-1.5">
          <PBadge v-for="f in filledFields" :key="f" tone="success">{{ f }}</PBadge>
        </div>
        <p class="mt-2 text-xs text-emerald-700">Sila semak semula maklumat ini dalam borang permohonan (langkah Maklumat Diri).</p>
      </div>
    </div>
  </div>
</template>
