<script setup lang="ts">
import { computed, type Component } from "vue";
import { CalendarCheck, ChevronDown, FileUp, Loader2, Sparkles } from "lucide-vue-next";

import PBadge from "@/components/pemohon/ui/PBadge.vue";
import type { PpEkstrakResult } from "@/types";

/** Collapsible section shell for one data-alternatif source, with "Muat naik penyata (AI)". */
const props = defineProps<{
  id: string;
  title: string;
  description: string;
  icon: Component;
  filled: boolean;
  extracting: boolean;
  ekstrak: PpEkstrakResult | null;
}>();
const emit = defineEmits<{ upload: [file: File] }>();
const open = defineModel<boolean>("open", { default: false });

const panelId = computed(() => `da-panel-${props.id}`);

function onPick(ev: Event) {
  const input = ev.target as HTMLInputElement;
  const file = input.files?.[0];
  input.value = "";
  if (file) emit("upload", file);
}
</script>

<template>
  <div class="rounded-2xl border transition-colors" :class="open ? 'border-slate-300 bg-white' : 'border-slate-200 bg-white hover:border-slate-300'">
    <button
      type="button"
      class="flex w-full items-center gap-3 rounded-2xl p-4 text-left focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
      :aria-expanded="open"
      :aria-controls="panelId"
      @click="open = !open"
    >
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="filled ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'">
        <component :is="icon" class="h-5 w-5" />
      </span>
      <span class="min-w-0 flex-1">
        <span class="flex items-center gap-2">
          <span class="text-sm font-semibold text-ink-900">{{ title }}</span>
          <PBadge v-if="filled" tone="success" dot>Diisi</PBadge>
        </span>
        <span class="block truncate text-xs text-slate-500">{{ description }}</span>
      </span>
      <ChevronDown class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" />
    </button>

    <div v-show="open" :id="panelId" class="space-y-4 border-t border-slate-100 p-4">
      <div class="flex flex-col gap-3 rounded-xl bg-gradient-to-r from-brand-50 to-white p-3 sm:flex-row sm:items-center">
        <p class="flex flex-1 items-start gap-2 text-sm text-ink-800">
          <Sparkles class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
          Muat naik penyata / tangkapan skrin — AI akan mengira purata bulanan untuk anda.
        </p>
        <label
          class="inline-flex h-9 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-white px-3.5 text-sm font-semibold text-brand-700 shadow-sm ring-1 ring-brand-200 transition-colors hover:bg-brand-50 focus-within:ring-4 focus-within:ring-brand-100"
          :class="extracting ? 'pointer-events-none opacity-70' : ''"
        >
          <Loader2 v-if="extracting" class="h-4 w-4 animate-spin" />
          <FileUp v-else class="h-4 w-4" />
          {{ extracting ? "AI sedang membaca…" : "Muat naik penyata (AI)" }}
          <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf,text/csv,.csv" class="sr-only" :disabled="extracting" @change="onPick" />
        </label>
      </div>

      <div v-if="ekstrak?.ok" class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-3 py-2.5 text-sm" role="status">
        <p class="font-medium text-emerald-800">Data diekstrak oleh AI — sila semak dan betulkan jika perlu.</p>
        <p v-if="ekstrak.bulanDikesan?.length" class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs text-emerald-800">
          <CalendarCheck class="h-3.5 w-3.5" /> Bulan dikesan:
          <PBadge v-for="b in ekstrak.bulanDikesan" :key="b" tone="success">{{ b }}</PBadge>
        </p>
        <p v-if="ekstrak.catatan" class="mt-1.5 text-xs text-emerald-700">{{ ekstrak.catatan }}</p>
      </div>

      <slot />
    </div>
  </div>
</template>
