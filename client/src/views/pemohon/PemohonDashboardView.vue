<script setup lang="ts">
import { computed } from "vue";
import { AlertTriangle, ArrowRight, FileText, PlusCircle, ShieldCheck, Wallet } from "lucide-vue-next";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore } from "@/stores/pemohon";

const pemohon = usePemohonStore();

const activeCount = computed(
  () => pemohon.permohonanList.filter((p) => p.statusKey === "dalam_semakan" || p.statusKey === "dokumen_tambahan").length,
);
const approvedCount = computed(() => pemohon.permohonanList.filter((p) => p.statusKey === "lulus").length);

const onboardingIncomplete = computed(
  () => !pemohon.onboarding.eligibilityChecked || pemohon.onboarding.ekycResult !== "lulus",
);
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="rounded-2xl bg-gradient-to-br from-blue-700 to-blue-900 p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-200">Selamat Datang</p>
        <h1 class="mt-2 text-2xl font-semibold text-white">{{ pemohon.profil.nama }}</h1>
        <p class="mt-1 text-sm text-blue-100">{{ pemohon.profil.perniagaan }}</p>
      </div>

      <div v-if="onboardingIncomplete" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
        <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
        <div class="flex-1">
          <p class="text-sm font-medium text-amber-800">Lengkapkan proses onboarding anda</p>
          <p class="mt-0.5 text-sm text-amber-700">Semak kelayakan dan sahkan identiti (eKYC) sebelum menghantar permohonan baharu.</p>
        </div>
        <router-link
          to="/pemohon/semakan-kelayakan"
          class="shrink-0 rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-amber-700"
        >
          Teruskan
        </router-link>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center gap-2">
            <FileText class="h-4 w-4 text-blue-600" />
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Jumlah Permohonan</p>
          </div>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ pemohon.permohonanList.length }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center gap-2">
            <Wallet class="h-4 w-4 text-amber-600" />
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Dalam Semakan</p>
          </div>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ activeCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center gap-2">
            <ShieldCheck class="h-4 w-4 text-emerald-600" />
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Lulus</p>
          </div>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ approvedCount }}</p>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Tindakan Pantas</h2>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <router-link
            to="/pemohon/permohonan/baru"
            class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-4 transition-colors hover:bg-blue-100"
          >
            <div class="flex items-center gap-3">
              <PlusCircle class="h-5 w-5 text-blue-600" />
              <span class="text-sm font-medium text-blue-900">Mohon Pembiayaan Baharu</span>
            </div>
            <ArrowRight class="h-4 w-4 text-blue-600" />
          </router-link>
          <router-link
            to="/pemohon/permohonan"
            class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-4 transition-colors hover:bg-slate-100"
          >
            <div class="flex items-center gap-3">
              <FileText class="h-5 w-5 text-slate-600" />
              <span class="text-sm font-medium text-slate-900">Semak Permohonan Saya</span>
            </div>
            <ArrowRight class="h-4 w-4 text-slate-500" />
          </router-link>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
