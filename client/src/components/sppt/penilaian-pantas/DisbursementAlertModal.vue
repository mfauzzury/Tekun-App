<script setup lang="ts">
import { ref, watch } from "vue";
import { AlertOctagon, ListChecks, Loader2, ShieldAlert, X } from "lucide-vue-next";

import type { PpDisbursementCheck } from "@/types";
import PpBadge from "./PpBadge.vue";

const props = defineProps<{ open: boolean; check: PpDisbursementCheck | null; submitting?: boolean; error?: string }>();
const emit = defineEmits<{ (e: "close"): void; (e: "confirm", catatan: string): void }>();

const akui = ref(false);
const catatan = ref("");
const localError = ref("");

watch(
  () => props.open,
  (o) => {
    if (o) {
      akui.value = false;
      catatan.value = "";
      localError.value = "";
    }
  },
);

function confirm() {
  if (!akui.value) {
    localError.value = "Sila tandakan pengakuan amaran terlebih dahulu.";
    return;
  }
  if (!catatan.value.trim()) {
    localError.value = "Catatan justifikasi diperlukan untuk meluluskan kes beramaran.";
    return;
  }
  localError.value = "";
  emit("confirm", catatan.value.trim());
}

function tone(t?: string | null) {
  return t === "tinggi" ? "rose" : t === "sederhana" ? "amber" : "slate";
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm">
      <div class="flex max-h-[92vh] w-full max-w-xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-2 ring-rose-500">
        <header class="flex items-start gap-3 bg-rose-600 px-5 py-4 text-white">
          <AlertOctagon class="mt-0.5 h-6 w-6 shrink-0" />
          <div class="min-w-0">
            <h3 class="text-base font-bold">Amaran Sebelum Pengeluaran Dana</h3>
            <p class="text-xs text-rose-100">Isyarat penipuan dikesan semasa semakan terakhir sebelum akaun dibuka dan dana disalurkan.</p>
          </div>
          <button type="button" class="ml-auto rounded-md p-1 text-rose-100 hover:bg-rose-700" :disabled="submitting" @click="emit('close')">
            <X class="h-4 w-4" />
          </button>
        </header>

        <div class="space-y-4 overflow-y-auto p-5">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-slate-500">Tahap risiko:</span>
            <PpBadge :tone="tone(check?.tahap)" size="sm">{{ check?.tahap ?? "—" }}</PpBadge>
            <span v-if="check?.analisis?.skorRisiko != null" class="text-xs text-slate-500">· Skor risiko {{ check.analisis.skorRisiko }}/100</span>
            <PpBadge v-if="check?.bolehSalur === false" tone="rose">Penyaluran disekat</PpBadge>
          </div>

          <section v-if="check?.sebab?.length">
            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-rose-700">Sebab amaran</p>
            <ul class="space-y-1">
              <li v-for="(s, i) in check.sebab" :key="i" class="flex items-start gap-1.5 text-sm text-slate-800">
                <ShieldAlert class="mt-0.5 h-4 w-4 shrink-0 text-rose-600" /> {{ s }}
              </li>
            </ul>
          </section>

          <section v-if="check?.analisis?.isyarat?.length" class="space-y-1.5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Isyarat penipuan</p>
            <article v-for="(s, i) in check.analisis.isyarat" :key="i" class="rounded-md border border-rose-200 bg-rose-50/60 px-3 py-2">
              <div class="flex items-center gap-2">
                <p class="text-sm font-semibold text-slate-800">{{ s?.tajuk }}</p>
                <PpBadge :tone="tone(s?.tahap)">{{ s?.tahap }}</PpBadge>
              </div>
              <p v-if="s?.penerangan" class="text-xs text-slate-600">{{ s.penerangan }}</p>
            </article>
          </section>

          <section v-if="check?.analisis?.cadanganTindakan?.length" class="rounded-md bg-slate-50 px-3 py-2">
            <p class="mb-1 flex items-center gap-1 text-xs font-semibold text-slate-700"><ListChecks class="h-3.5 w-3.5" /> Tindakan disyorkan</p>
            <ol class="list-decimal space-y-0.5 pl-5 text-xs text-slate-600">
              <li v-for="(t, i) in check.analisis.cadanganTindakan" :key="i">{{ t }}</li>
            </ol>
          </section>

          <label class="flex items-start gap-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-900">
            <input v-model="akui" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-rose-300 text-rose-600" />
            Saya telah menyemak amaran di atas dan mengesahkan bahawa tindakan susulan telah diambil. Saya bertanggungjawab ke atas
            kelulusan ini.
          </label>

          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600">Catatan justifikasi <span class="text-rose-600">*</span></span>
            <textarea
              v-model="catatan"
              rows="3"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
              placeholder="cth. Pemohon disahkan melalui panggilan video; alamat dikongsi dengan ahli keluarga yang berdaftar."
            />
          </label>

          <p v-if="localError || error" class="rounded-md bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">{{ localError || error }}</p>
        </div>

        <footer class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
          <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" :disabled="submitting" @click="emit('close')">
            Batal
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50"
            :disabled="submitting || !akui"
            @click="confirm"
          >
            <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
            Akui amaran & luluskan
          </button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>
