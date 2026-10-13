<script setup lang="ts">
import { computed } from "vue";
import { PenLine, Plus, Store } from "lucide-vue-next";

import PemohonHebahanSlider from "@/components/pemohon/PemohonHebahanSlider.vue";
import FinancingSummary from "@/components/pemohon/status/FinancingSummary.vue";
import LatestApplicationCard from "@/components/pemohon/status/LatestApplicationCard.vue";
import OnboardingChecklist from "@/components/pemohon/status/OnboardingChecklist.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore } from "@/stores/pemohon";

const pemohon = usePemohonStore();

const NAME_PREFIXES = /^(mohd|md|muhd|muhammad|mohamad|mohammad|mohamed)$/i;

const firstName = computed(() => {
  const words = (pemohon.profil.nama || "").trim().split(/\s+/).filter(Boolean);
  const name = words.find((w, i) => !NAME_PREFIXES.test(w) || i === words.length - 1) ?? "";
  return name || "Usahawan";
});

const todayLabel = new Date().toLocaleDateString("ms-MY", { weekday: "long", day: "numeric", month: "long", year: "numeric" });

const hasDraft = computed(() => !!pemohon.draftPermohonan);
const onboardingIncomplete = computed(
  () =>
    !(pemohon.onboarding.eligibilityChecked && pemohon.onboarding.eligibilityResult) ||
    !pemohon.onboarding.ekycVerified ||
    pemohon.permohonanSaya.length === 0,
);
</script>

<template>
  <PemohonLayout>
    <div class="space-y-6 pt-6 sm:pt-8">
      <!-- Greeting -->
      <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
          <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-brand-600">
            <span class="h-1.5 w-1.5 rounded-full bg-brand-600" aria-hidden="true" />
            <span class="capitalize">{{ todayLabel }}</span>
          </p>
          <h1 class="mt-1.5 truncate text-2xl font-bold tracking-tight text-ink-900 sm:text-[28px]">Selamat datang, {{ firstName }}</h1>
          <p v-if="pemohon.profil.perniagaan" class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
            <Store class="h-4 w-4 shrink-0" aria-hidden="true" />
            <span class="truncate">{{ pemohon.profil.perniagaan }}</span>
          </p>
        </div>
        <PButton v-if="hasDraft" to="/pemohon/permohonan/baru">
          <PenLine class="h-4 w-4" aria-hidden="true" /> Sambung draf
        </PButton>
        <PButton v-else to="/pemohon/permohonan/baru">
          <Plus class="h-4 w-4" aria-hidden="true" /> Mohon pembiayaan
        </PButton>
      </header>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
          <!-- On mobile, surface unfinished onboarding before everything else -->
          <OnboardingChecklist v-if="onboardingIncomplete" class="lg:hidden" />
          <LatestApplicationCard />
          <FinancingSummary />
        </div>

        <aside class="space-y-6" aria-label="Maklumat tambahan">
          <OnboardingChecklist :class="onboardingIncomplete ? 'hidden lg:block' : ''" />
          <div>
            <h2 class="mb-3 text-[15px] font-semibold text-ink-900">Hebahan</h2>
            <PemohonHebahanSlider />
          </div>
        </aside>
      </div>
    </div>
  </PemohonLayout>
</template>
