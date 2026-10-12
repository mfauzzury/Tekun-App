<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from "vue";
import { ExternalLink, Loader2, Maximize2, Minimize2, RefreshCw } from "lucide-vue-next";

import { POWERBI_REPORT_URL } from "@/env";

const props = withDefaults(
  defineProps<{
    /** Base Power BI embed URL (defaults to VITE_POWERBI_REPORT_URL). */
    reportUrl?: string;
    /** Optional report title for accessibility. */
    title?: string;
  }>(),
  {
    reportUrl: undefined,
    title: "Power BI Report",
  },
);

const containerRef = useTemplateRef<HTMLDivElement>("containerRef");
const loading = ref(true);
const loadError = ref(false);
const isFullscreen = ref(false);
const reloadKey = ref(0);

const baseUrl = computed(() => props.reportUrl?.trim() || POWERBI_REPORT_URL);

const iframeSrc = computed(() => baseUrl.value);

function onFullscreenChange() {
  isFullscreen.value = Boolean(document.fullscreenElement);
}

onMounted(() => {
  document.addEventListener("fullscreenchange", onFullscreenChange);
});

onUnmounted(() => {
  document.removeEventListener("fullscreenchange", onFullscreenChange);
});

function onIframeLoad() {
  loading.value = false;
  loadError.value = false;
}

function onIframeError() {
  loading.value = false;
  loadError.value = true;
}

function reloadReport() {
  loading.value = true;
  loadError.value = false;
  reloadKey.value += 1;
}

async function toggleFullscreen() {
  const el = containerRef.value;
  if (!el) return;

  if (!document.fullscreenElement) {
    await el.requestFullscreen();
  } else {
    await document.exitFullscreen();
  }
}
</script>

<template>
  <div
    ref="containerRef"
    class="relative flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
    :class="isFullscreen ? 'rounded-none border-0' : ''"
  >
    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-3 py-2">
      <p class="text-xs text-slate-600">
        Gunakan panel penapis, slicer dan parameter dalam laporan untuk menyesuaikan paparan.
      </p>
      <div class="flex shrink-0 items-center gap-1">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50"
          title="Muat semula laporan"
          @click="reloadReport"
        >
          <RefreshCw class="h-3.5 w-3.5" />
          Muat semula
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50"
          :title="isFullscreen ? 'Keluar skrin penuh' : 'Skrin penuh'"
          @click="toggleFullscreen"
        >
          <Minimize2 v-if="isFullscreen" class="h-3.5 w-3.5" />
          <Maximize2 v-else class="h-3.5 w-3.5" />
          {{ isFullscreen ? "Keluar" : "Skrin penuh" }}
        </button>
        <a
          v-if="iframeSrc"
          :href="iframeSrc"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50"
        >
          <ExternalLink class="h-3.5 w-3.5" />
          Buka tab baharu
        </a>
      </div>
    </div>

    <div class="relative min-h-[calc(100dvh-11rem)] flex-1">
      <div
        v-if="loading"
        class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 bg-white/90"
      >
        <Loader2 class="h-8 w-8 animate-spin text-[var(--accent-600)]" />
        <p class="text-sm text-slate-600">Memuatkan laporan Power BI…</p>
      </div>

      <div
        v-if="loadError"
        class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-white p-6 text-center"
      >
        <p class="text-sm font-medium text-slate-800">Laporan tidak dapat dimuatkan.</p>
        <p class="max-w-md text-xs text-slate-500">
          Pastikan pautan Power BI sah dan akses "Publish to web" diaktifkan untuk laporan ini.
        </p>
        <button
          type="button"
          class="rounded-md bg-[var(--accent-600)] px-3 py-1.5 text-xs font-medium text-white hover:bg-[var(--accent-700)]"
          @click="reloadReport"
        >
          Cuba lagi
        </button>
      </div>

      <iframe
        v-if="iframeSrc"
        :key="reloadKey"
        :src="iframeSrc"
        :title="title"
        class="h-full w-full border-0"
        allowfullscreen
        @load="onIframeLoad"
        @error="onIframeError"
      />
    </div>
  </div>
</template>
