<script setup lang="ts">
import { computed } from "vue";
import { ArrowRight, Check, ClipboardCheck, ScanFace, Send } from "lucide-vue-next";

import { usePemohonStore } from "@/stores/pemohon";

const pemohon = usePemohonStore();

const steps = computed(() => {
  const ob = pemohon.onboarding;
  const eligibilityDone = ob.eligibilityChecked && !!ob.eligibilityResult;
  return [
    {
      key: "kelayakan",
      icon: ClipboardCheck,
      label: "Semakan kelayakan",
      to: "/pemohon/semakan-kelayakan",
      done: eligibilityDone,
      hint: !eligibilityDone ? "2 minit · tanpa dokumen" : ob.eligibilityResult === "lulus" ? "Anda layak memohon" : "Semak semula kelayakan anda",
      warn: eligibilityDone && ob.eligibilityResult === "gagal",
    },
    {
      key: "ekyc",
      icon: ScanFace,
      label: "Pengesahan identiti e-KYC",
      to: "/pemohon/ekyc",
      done: ob.ekycVerified,
      hint: ob.ekycVerified ? "Identiti disahkan" : "MyKad + swafoto",
      warn: false,
    },
    {
      key: "hantar",
      icon: Send,
      label: "Hantar permohonan",
      to: pemohon.permohonanSaya.length ? "/pemohon/permohonan" : "/pemohon/permohonan/baru",
      done: pemohon.permohonanSaya.length > 0,
      hint: pemohon.permohonanSaya.length ? `${pemohon.permohonanSaya.length} permohonan dihantar` : "Lengkapkan borang dalam talian",
      warn: false,
    },
  ];
});

const doneCount = computed(() => steps.value.filter((s) => s.done).length);
const total = computed(() => steps.value.length);
const nextIndex = computed(() => steps.value.findIndex((s) => !s.done));
</script>

<template>
  <section class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="persediaan-title">
    <div v-if="doneCount === total" class="flex items-center gap-3">
      <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500 text-white"><Check class="h-4 w-4" aria-hidden="true" /></span>
      <p id="persediaan-title" class="text-sm font-semibold text-ink-900">Semua langkah persediaan selesai</p>
      <span class="text-sm text-slate-500">— anda boleh menjejak permohonan di halaman Permohonan.</span>
    </div>

    <div v-else class="flex flex-col gap-4 lg:flex-row lg:items-center lg:gap-6">
      <div class="shrink-0 lg:w-52">
        <p id="persediaan-title" class="text-xs font-semibold uppercase tracking-wider text-brand-600">Langkah persediaan</p>
        <p class="mt-1 text-lg font-bold text-ink-950">{{ doneCount }} daripada {{ total }} selesai</p>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" :aria-valuenow="doneCount" aria-valuemin="0" :aria-valuemax="total">
          <div class="h-full rounded-full bg-brand-600 transition-all duration-700" :style="{ width: `${(doneCount / total) * 100}%` }" />
        </div>
      </div>

      <ol class="grid flex-1 gap-3 sm:grid-cols-3">
        <li v-for="(s, i) in steps" :key="s.key">
          <router-link
            :to="s.to"
            class="group flex h-full items-center gap-3 rounded-2xl p-3.5 transition-all"
            :class="
              i === nextIndex
                ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/25 hover:bg-brand-700'
                : s.done
                  ? s.warn
                    ? 'bg-amber-50 ring-1 ring-amber-100 hover:ring-amber-200'
                    : 'bg-emerald-50/70 ring-1 ring-emerald-100 hover:ring-emerald-200'
                  : 'bg-slate-50 ring-1 ring-slate-100 hover:ring-slate-200'
            "
          >
            <span
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
              :class="i === nextIndex ? 'bg-white/15 text-white' : s.done ? (s.warn ? 'bg-amber-500 text-white' : 'bg-emerald-500 text-white') : 'bg-white text-slate-400'"
            >
              <Check v-if="s.done" class="h-5 w-5" aria-hidden="true" />
              <component :is="s.icon" v-else class="h-5 w-5" aria-hidden="true" />
            </span>
            <span class="min-w-0 flex-1">
              <span class="block text-sm font-semibold" :class="i === nextIndex ? 'text-white' : 'text-ink-900'">
                {{ s.label }}<span class="sr-only">{{ s.done ? " — selesai" : " — belum selesai" }}</span>
              </span>
              <span class="block truncate text-xs" :class="i === nextIndex ? 'text-white/80' : s.warn ? 'text-amber-700' : 'text-slate-500'">
                {{ i === nextIndex ? "Mulakan sekarang" : s.hint }}
              </span>
            </span>
            <ArrowRight v-if="i === nextIndex" class="h-4 w-4 shrink-0 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
          </router-link>
        </li>
      </ol>
    </div>
  </section>
</template>
