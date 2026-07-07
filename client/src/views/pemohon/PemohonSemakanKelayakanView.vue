<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { AlertTriangle, ArrowRight, CheckCircle2, Sparkles, XCircle } from "lucide-vue-next";
import AppToastRegion from "@/components/AppToastRegion.vue";
import { evaluateAiRiskFromInput, formatRecommendedLimit, riskCategoryClass } from "@/composables/useAiRiskScoring";
import { fetchHardRulesSummary } from "@/api/pemohon";
import { usePemohonStore } from "@/stores/pemohon";
import { ELIGIBILITY_RULES } from "@/data/portal-dummy";
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
  <div class="flex min-h-screen flex-col items-center justify-center bg-[#f6f9fc] px-4 py-10">
    <div class="w-full max-w-[480px]">
      <div class="mb-6 flex justify-center">
        <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-9 w-auto" />
      </div>

      <div class="rounded-lg border border-[#e3e8ee] bg-white px-8 pb-8 pt-6 shadow-[0_2px_4px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.06)]">
        <h1 class="mb-1 text-center text-xl font-semibold tracking-tight text-[#1a1f36]">Semakan Kelayakan Awalan</h1>
        <p class="mb-6 text-center text-[13px] text-[#697386]">
          Enjin saringan automatik (Hard Rules Check) akan menyemak kelayakan asas anda sebelum meneruskan permohonan.
        </p>

        <div
          v-if="rulesSummary?.active && rulesSummary.rules.length"
          class="mb-5 rounded-md border border-slate-100 bg-slate-50 p-3"
        >
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Peraturan Mandatori</p>
          <ul class="space-y-1 text-xs text-slate-600">
            <li v-for="rule in rulesSummary.rules" :key="rule.code">
              {{ rule.label }}<span v-if="rule.hint" class="text-slate-400"> — {{ rule.hint }}</span>
            </li>
          </ul>
        </div>

        <form v-if="!result" class="space-y-4" @submit.prevent="submit">
          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">Umur</label>
            <input
              v-model.number="form.umur"
              type="number"
              min="1"
              max="120"
              class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
            />
            <p class="text-[12px] text-slate-400">Kelayakan: {{ ageRule.min }}–{{ ageRule.max }} tahun</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">No. Kad Pengenalan</label>
            <input
              v-model="form.noKp"
              type="text"
              class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
              placeholder="850101-14-5678"
            />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[13px] font-medium text-[#1a1f36]">Pendapatan Bulanan (RM)</label>
              <input
                v-model.number="form.pendapatanBulanan"
                type="number"
                min="0"
                class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
              />
            </div>
            <div class="space-y-1.5">
              <label class="text-[13px] font-medium text-[#1a1f36]">Komitmen Sedia Ada (RM)</label>
              <input
                v-model.number="form.jumlahKomitmenSediaAda"
                type="number"
                min="0"
                class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
              />
            </div>
          </div>

          <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-60"
            :disabled="checking"
          >
            {{ checking ? "Menyemak..." : "Semak Kelayakan" }}
            <ArrowRight v-if="!checking" class="h-4 w-4" />
          </button>
        </form>

        <div v-else class="space-y-4 text-center">
          <div v-if="result.eligible" class="flex flex-col items-center gap-2">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50">
              <CheckCircle2 class="h-8 w-8 text-emerald-600" />
            </div>
            <h2 class="text-lg font-semibold text-emerald-700">Anda Layak Meneruskan Permohonan</h2>
            <p class="text-sm text-slate-500">Profil awal anda memenuhi kriteria kelayakan asas SPPT.</p>

            <div
              v-if="aiRisk"
              class="mt-4 w-full rounded-lg border border-violet-200 bg-violet-50/60 p-4 text-left"
            >
              <div class="mb-3 flex items-center gap-2">
                <Sparkles class="h-4 w-4 text-violet-600" />
                <p class="text-sm font-semibold text-violet-900">AI Risk Scoring (Spec 1.6.3)</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <p class="text-[11px] font-medium uppercase text-slate-500">Skor Risiko</p>
                  <p class="text-lg font-bold text-slate-900">{{ aiRisk.riskScore }}/100</p>
                </div>
                <div>
                  <p class="text-[11px] font-medium uppercase text-slate-500">Kategori</p>
                  <span class="mt-0.5 inline-block rounded-full px-2 py-0.5 text-xs font-medium" :class="riskCategoryClass(aiRisk.riskCategory)">
                    {{ aiRisk.riskCategory }}
                  </span>
                </div>
              </div>
              <p class="mt-3 text-xs text-slate-600">
                Had pembiayaan dicadangkan: <span class="font-semibold">{{ formatRecommendedLimit(aiRisk.recommendedLimit) }}</span>
              </p>
              <p class="mt-2 text-[11px] text-violet-600/80">Sokongan keputusan — pegawai TEKUN membuat keputusan muktamad.</p>
            </div>
          </div>
          <div v-else class="flex flex-col items-center gap-2">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-rose-50">
              <XCircle class="h-8 w-8 text-rose-600" />
            </div>
            <h2 class="text-lg font-semibold text-rose-700">Permohonan Tidak Dapat Diteruskan (Auto-Reject)</h2>
            <p class="text-sm text-slate-500">Profil anda tidak memenuhi syarat mandatori berikut:</p>
            <ul class="w-full space-y-1.5 rounded-md border border-rose-100 bg-rose-50 p-3 text-left text-sm text-rose-700">
              <li v-for="reason in result.reasons" :key="reason" class="flex items-start gap-2">
                <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                {{ reason }}
              </li>
            </ul>
          </div>

          <button
            v-if="result.eligible"
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700"
            @click="proceed"
          >
            Teruskan ke Pengesahan Identiti
            <ArrowRight class="h-4 w-4" />
          </button>
          <button
            v-else
            type="button"
            class="w-full rounded-md border border-slate-300 px-4 py-[9px] text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
            @click="result = null; aiRisk = null"
          >
            Cuba Semula
          </button>
        </div>
      </div>
    </div>

    <div class="fixed right-4 top-4 z-50 h-16">
      <AppToastRegion />
    </div>
  </div>
</template>
