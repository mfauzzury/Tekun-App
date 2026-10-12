<script setup lang="ts">
import {
  decisionActionClass,
  formatRecommendedLimit,
  riskBandClass,
} from "@/composables/useAiCreditScoring";
import CreditScoreSpeedometer from "@/components/sppt/CreditScoreSpeedometer.vue";
import type { AiCreditScoringResult, EdgeCaseMitigation } from "@/types";
import { AlertTriangle, Gauge, Loader2, ShieldAlert, Sparkles } from "lucide-vue-next";

defineProps<{
  scoring: boolean;
  aiCredit: AiCreditScoringResult | null;
  showApplySuggestion?: boolean;
}>();

defineEmits<{
  applySuggestion: [];
  applyMitigation: [mitigation: EdgeCaseMitigation];
}>();

function mitigationPriorityClass(priority: string): string {
  return priority === "high"
    ? "border-amber-300 bg-amber-50"
    : "border-slate-200 bg-white/80";
}
</script>

<template>
  <section class="rounded-lg border border-violet-200 bg-violet-50/50 shadow-sm">
    <div class="flex items-center gap-2 border-b border-violet-200/50 px-4 py-3">
      <Sparkles class="h-4 w-4 text-violet-600" />
      <h4 class="text-sm font-semibold text-violet-900">AI Credit Scoring</h4>
      <span class="rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-medium text-violet-600">Risk & Amount Analysis</span>
    </div>
    <div class="p-4">
      <div v-if="scoring" class="flex items-center gap-2 text-sm text-violet-700">
        <Loader2 class="h-4 w-4 animate-spin" />
        Mengira skor kredit AI (APK + rekod dalaman)...
      </div>
      <template v-else-if="aiCredit">
        <div
          class="mb-4 flex flex-wrap items-center gap-3 rounded-lg border px-4 py-3"
          :class="decisionActionClass(aiCredit.recommendedAction)"
        >
          <div class="flex items-center gap-2">
            <Gauge class="h-5 w-5 shrink-0" />
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide">Decision Engine</p>
              <p class="text-sm font-bold">{{ aiCredit.decisionLabel }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full" :class="riskBandClass(aiCredit.riskBandColor)" />
            <span class="text-xs">{{ aiCredit.decisionDescription }}</span>
          </div>
        </div>

        <div
          v-if="aiCredit.edgeCase?.isBorderline"
          class="mb-4 rounded-lg border border-amber-300 bg-amber-50/90 px-4 py-3"
        >
          <div class="flex items-start gap-2">
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />
            <div class="min-w-0 flex-1">
              <p class="text-xs font-semibold uppercase tracking-wide text-amber-900">
                {{ aiCredit.edgeCase.zoneLabel ?? "Zon Kelabu / Kes Sempadan" }}
              </p>
              <p class="mt-1 text-xs text-amber-900/90">{{ aiCredit.edgeCase.summary }}</p>
              <p v-if="aiCredit.edgeCase.autoApproveBlocked" class="mt-2 text-[11px] font-medium text-amber-800">
                Auto-lulus disekat — keputusan muktamad kekal dengan pegawai.
              </p>
            </div>
          </div>

          <div v-if="aiCredit.edgeCase.mitigations.length" class="mt-3 space-y-2">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-900">
              Cadangan Mitigasi
            </p>
            <article
              v-for="mitigation in aiCredit.edgeCase.mitigations"
              :key="mitigation.code"
              class="rounded-lg border px-3 py-2.5"
              :class="mitigationPriorityClass(mitigation.priority)"
            >
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-1.5">
                    <ShieldAlert class="h-3.5 w-3.5 shrink-0 text-amber-700" />
                    <p class="text-xs font-semibold text-slate-900">{{ mitigation.title }}</p>
                    <span
                      v-if="mitigation.priority === 'high'"
                      class="rounded bg-amber-200 px-1.5 py-0.5 text-[9px] font-medium uppercase text-amber-900"
                    >
                      Utama
                    </span>
                  </div>
                  <p class="mt-1 text-xs text-slate-600">{{ mitigation.description }}</p>
                </div>
                <button
                  v-if="showApplySuggestion"
                  type="button"
                  class="shrink-0 text-xs font-medium text-violet-700 hover:text-violet-800"
                  @click="$emit('applyMitigation', mitigation)"
                >
                  Guna cadangan →
                </button>
              </div>
            </article>
          </div>
        </div>

        <p class="text-xs text-violet-600/90">{{ aiCredit.message }}</p>

        <div class="mt-4 flex flex-col items-center gap-4 rounded-xl border border-violet-100 bg-white/80 p-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="flex flex-col items-center">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Skor Kredit</p>
            <CreditScoreSpeedometer
              :score="aiCredit.creditScore"
              :category="aiCredit.creditCategory"
              :band-color="aiCredit.riskBandColor"
              :size="240"
            />
            <p class="mt-1 max-w-[220px] text-center text-[10px] text-slate-500">
              Skor risiko pemohon (0–100). Lebih tinggi = risiko lebih rendah. Termasuk pelarasan APK (CCRIS/CTOS/EXPERIAN).
            </p>
          </div>
          <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-lg bg-slate-50 px-3 py-2 shadow-sm">
              <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Had Pembiayaan Dicadangkan</p>
              <p class="mt-0.5 text-lg font-bold text-slate-900">{{ formatRecommendedLimit(aiCredit.recommendedLimit) }}</p>
              <button
                v-if="showApplySuggestion"
                type="button"
                class="mt-1.5 text-xs font-medium text-violet-600 hover:text-violet-700"
                @click="$emit('applySuggestion')"
              >
                Guna sebagai cadangan →
              </button>
            </div>
            <div class="rounded-lg bg-slate-50 px-3 py-2 shadow-sm">
              <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Keputusan AI</p>
              <p class="mt-0.5 text-sm font-bold text-slate-900">{{ aiCredit.decisionLabel }}</p>
              <p class="mt-1 text-xs text-slate-500">Keyakinan AI: {{ aiCredit.confidence }}%</p>
              <p class="mt-0.5 text-[10px] text-slate-400">Ketepatan ramalan model — bukan skor kredit.</p>
            </div>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
          <div class="rounded-lg border border-slate-200 bg-white/70 px-3 py-2 text-xs">
            <p class="font-semibold text-slate-700">CCRIS</p>
            <p class="mt-0.5 text-slate-600">Skor: {{ aiCredit.apk.ccris.score }}</p>
            <p class="text-slate-500">Facilities: {{ aiCredit.apk.ccris.totalFacilities }}</p>
          </div>
          <div class="rounded-lg border border-slate-200 bg-white/70 px-3 py-2 text-xs">
            <p class="font-semibold text-slate-700">CTOS</p>
            <p class="mt-0.5 text-slate-600">Skor: {{ aiCredit.apk.ctos.score }}</p>
            <p class="text-slate-500">{{ aiCredit.apk.ctos.litigation ? "Litigasi: Ya" : "Litigasi: Tiada" }}</p>
          </div>
          <div class="rounded-lg border border-slate-200 bg-white/70 px-3 py-2 text-xs">
            <p class="font-semibold text-slate-700">EXPERIAN</p>
            <p class="mt-0.5 text-slate-600">Skor: {{ aiCredit.apk.experian.score }}</p>
            <p class="text-slate-500">{{ aiCredit.apk.experian.delinquency ? "Tunggakan: Ya" : "Tunggakan: Tiada" }}</p>
          </div>
        </div>

        <ul v-if="aiCredit.factors.length" class="mt-4 space-y-1.5 rounded-lg border border-violet-100 bg-white/60 p-3">
          <li v-for="(factor, idx) in aiCredit.factors" :key="idx" class="text-xs text-slate-600">
            <span class="font-medium text-slate-700">{{ factor.factor }}:</span> {{ factor.description }}
          </li>
        </ul>

        <ul v-if="aiCredit.adverseActionReasons.length" class="mt-3 space-y-1 rounded-lg border border-rose-100 bg-rose-50/80 p-3">
          <li class="text-xs font-semibold text-rose-800">Sebab Penolakan / Kelewatan (Adverse Action)</li>
          <li v-for="(reason, idx) in aiCredit.adverseActionReasons" :key="idx" class="text-xs text-rose-700">• {{ reason }}</li>
        </ul>
      </template>
      <p v-else class="text-sm text-slate-500">Skor kredit AI tidak tersedia untuk permohonan ini.</p>
    </div>
  </section>
</template>
