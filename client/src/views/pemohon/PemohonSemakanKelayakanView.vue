<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { AlertTriangle, ArrowRight, CheckCircle2, ListChecks, Sparkles, XCircle } from "lucide-vue-next";
import AppToastRegion from "@/components/AppToastRegion.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import { evaluateAiRiskFromInput, formatRecommendedLimit, riskCategoryClass } from "@/composables/useAiRiskScoring";
import { fetchHardRulesSummary } from "@/api/pemohon";
import { usePemohonStore } from "@/stores/pemohon";
import { ELIGIBILITY_RULES } from "@/data/portal-dummy";
import { pInputClass } from "@/utils/pemohon-ui";
import type { AiRiskScoringResult, HardRuleCheckResult, HardRulePublicSummary } from "@/types";

const router = useRouter();
const pemohon = usePemohonStore();

const form = ref({
  umur: 30,
  noKp: "",
  pendapatanBulanan: 3000,
  jumlahKomitmenSediaAda: 500,
});

const result = ref<HardRuleCheckResult | null>(null);
const aiRisk = ref<AiRiskScoringResult | null>(null);
const checking = ref(false);
const rulesSummary = ref<HardRulePublicSummary | null>(null);

const ageRule = ref<{ min: number; max: number }>({ min: ELIGIBILITY_RULES.minAge, max: ELIGIBILITY_RULES.maxAge });

onMounted(async () => {
  try {
    const res = await fetchHardRulesSummary();
    rulesSummary.value = res.data;
    const age = res.data.rules.find((rule) => rule.code === "age_limit");
    if (age?.hint) {
      const match = age.hint.match(/(\d+)[–-](\d+)/);
      if (match) {
        ageRule.value = { min: Number(match[1]), max: Number(match[2]) };
      }
    }
  } catch {
    // Keep local defaults when API unavailable.
  }
});

async function submit() {
  checking.value = true;
  result.value = null;
  aiRisk.value = null;
  try {
    result.value = await pemohon.runEligibilityCheck({ ...form.value });
    if (result.value.eligible) {
      aiRisk.value = await evaluateAiRiskFromInput({ ...form.value });
    }
  } finally {
    checking.value = false;
  }
}

function proceed() {
  router.push({ name: "pemohon-ekyc" });
}
</script>

<template>
  <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-[#f6f7fb] px-4 py-10">
    <div
      aria-hidden="true"
      class="pointer-events-none absolute inset-x-0 top-0 h-[480px] bg-[radial-gradient(60%_60%_at_50%_0%,rgba(10,47,214,0.10),transparent_70%)]"
    />

    <div class="relative w-full max-w-[480px]">
      <div class="mb-8 flex justify-center">
        <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-10 w-auto" />
      </div>

      <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04),0_16px_48px_-16px_rgba(16,24,40,0.16)] sm:p-8">
        <div class="mb-6 text-center">
          <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
            <ListChecks class="h-6 w-6" />
          </span>
          <h1 class="text-2xl font-bold tracking-tight text-ink-900">Semakan kelayakan awal</h1>
          <p class="mx-auto mt-1.5 max-w-sm text-sm text-slate-500">
            Enjin saringan automatik (Hard Rules Check) akan menyemak kelayakan asas anda sebelum meneruskan permohonan.
          </p>
        </div>

        <div
          v-if="rulesSummary?.active && rulesSummary.rules.length"
          class="mb-6 rounded-2xl border border-slate-200/70 bg-slate-50 p-4"
        >
          <p class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-ink-600">Peraturan mandatori</p>
          <ul class="space-y-1.5 text-sm text-ink-800">
            <li v-for="rule in rulesSummary.rules" :key="rule.code" class="flex items-start gap-2">
              <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
              <span>{{ rule.label }}<span v-if="rule.hint" class="text-slate-500"> — {{ rule.hint }}</span></span>
            </li>
          </ul>
        </div>

        <form v-if="!result" class="space-y-4" @submit.prevent="submit">
          <PField label="Umur" for="sk-umur" :hint="`Kelayakan: ${ageRule.min}–${ageRule.max} tahun`">
            <input id="sk-umur" v-model.number="form.umur" type="number" min="1" max="120" inputmode="numeric" :class="pInputClass" />
          </PField>
          <PField label="No. kad pengenalan" for="sk-nokp">
            <input id="sk-nokp" v-model="form.noKp" type="text" inputmode="numeric" :class="pInputClass" placeholder="850101-14-5678" />
          </PField>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <PField label="Pendapatan bulanan (RM)" for="sk-pendapatan">
              <input id="sk-pendapatan" v-model.number="form.pendapatanBulanan" type="number" min="0" inputmode="decimal" :class="pInputClass" />
            </PField>
            <PField label="Komitmen sedia ada (RM)" for="sk-komitmen">
              <input id="sk-komitmen" v-model.number="form.jumlahKomitmenSediaAda" type="number" min="0" inputmode="decimal" :class="pInputClass" />
            </PField>
          </div>

          <PButton type="submit" block size="lg" class="mt-2" :loading="checking">
            {{ checking ? "Menyemak..." : "Semak kelayakan" }}
            <ArrowRight v-if="!checking" class="h-4 w-4" />
          </PButton>
        </form>

        <div v-else class="space-y-5 text-center">
          <div v-if="result.eligible" class="flex flex-col items-center gap-2">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 ring-8 ring-emerald-50/60">
              <CheckCircle2 class="h-9 w-9 text-emerald-600" />
            </span>
            <h2 class="mt-3 text-xl font-bold tracking-tight text-ink-900">Anda layak meneruskan permohonan</h2>
            <p class="text-sm text-slate-500">Profil awal anda memenuhi kriteria kelayakan asas SPPT.</p>

            <div v-if="aiRisk" class="mt-4 w-full rounded-2xl border border-ink-100 bg-ink-50/70 p-4 text-left">
              <div class="mb-3 flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-ink-900 text-white">
                  <Sparkles class="h-3.5 w-3.5" />
                </span>
                <p class="text-sm font-semibold text-ink-900">AI Risk Scoring (Spec 1.6.3)</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-white p-3 ring-1 ring-ink-100">
                  <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Skor risiko</p>
                  <p class="mt-0.5 text-xl font-bold text-ink-900">{{ aiRisk.riskScore }}<span class="text-sm font-medium text-slate-400">/100</span></p>
                </div>
                <div class="rounded-xl bg-white p-3 ring-1 ring-ink-100">
                  <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Kategori</p>
                  <span class="mt-1.5 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium" :class="riskCategoryClass(aiRisk.riskCategory)">
                    {{ aiRisk.riskCategory }}
                  </span>
                </div>
              </div>
              <p class="mt-3 text-sm text-ink-700">
                Had pembiayaan dicadangkan: <span class="font-semibold text-ink-900">{{ formatRecommendedLimit(aiRisk.recommendedLimit) }}</span>
              </p>
              <p class="mt-2 text-xs text-slate-500">Sokongan keputusan — pegawai TEKUN membuat keputusan muktamad.</p>
            </div>
          </div>
          <div v-else class="flex flex-col items-center gap-2">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 ring-8 ring-rose-50/60">
              <XCircle class="h-9 w-9 text-rose-600" />
            </span>
            <h2 class="mt-3 text-xl font-bold tracking-tight text-ink-900">Permohonan tidak dapat diteruskan</h2>
            <p class="text-sm text-slate-500">Profil anda tidak memenuhi syarat mandatori berikut (Auto-Reject):</p>
            <ul class="mt-2 w-full space-y-2 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-left text-sm text-rose-800">
              <li v-for="reason in result.reasons" :key="reason" class="flex items-start gap-2">
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
                {{ reason }}
              </li>
            </ul>
          </div>

          <PButton v-if="result.eligible" block size="lg" @click="proceed">
            Teruskan ke pengesahan identiti
            <ArrowRight class="h-4 w-4" />
          </PButton>
          <PButton v-else block size="lg" variant="outline" @click="result = null; aiRisk = null">
            Cuba semula
          </PButton>
        </div>
      </div>

      <p class="mt-6 text-center text-xs text-slate-400">© TEKUN Nasional · Portal Usahawan</p>
    </div>

    <div class="fixed right-4 top-4 z-50 h-16">
      <AppToastRegion />
    </div>
  </div>
</template>
