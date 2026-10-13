<script setup lang="ts">
import { computed } from "vue";
import { IdCard, ScanFace } from "lucide-vue-next";

import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import type { PemohonMyKadScan, PpFaceResult } from "@/types";
import type { PTone } from "@/utils/pemohon-status";

const props = defineProps<{ wajah?: PpFaceResult | null; mykad?: PemohonMyKadScan | null; onboardingVerified?: boolean }>();

const KEPUTUSAN: Record<string, { label: string; tone: PTone }> = {
  lulus: { label: "Disahkan", tone: "success" },
  semak: { label: "Perlu semakan", tone: "warning" },
  gagal: { label: "Tidak lulus", tone: "danger" },
};

const wajahStatus = computed(() => KEPUTUSAN[props.wajah?.keputusan ?? ""] ?? null);

function maskIc(ic?: string | null) {
  const digits = (ic ?? "").replace(/\D/g, "");
  if (digits.length !== 12) return null;
  return `${digits.slice(0, 6)}-**-**${digits.slice(10)}`;
}

const mykadOk = computed(() => !!props.mykad?.ocr?.ok);
const icMasked = computed(() => maskIc(props.mykad?.ocr?.data?.noIc ?? props.mykad?.ic?.noIc));
const padananSkor = computed(() => props.mykad?.padanan?.skorKeseluruhan ?? null);

const meters = computed(() =>
  [
    { label: "Padanan wajah", value: props.wajah?.padananWajah },
    { label: "Ujian keaktifan (liveness)", value: props.wajah?.liveness },
  ].filter((m): m is { label: string; value: number } => typeof m.value === "number"),
);

const hasAny = computed(() => !!props.wajah || !!props.mykad);
</script>

<template>
  <PCard title="Pengesahan identiti">
    <template v-if="wajahStatus" #actions>
      <PBadge :tone="wajahStatus.tone" dot>{{ wajahStatus.label }}</PBadge>
    </template>

    <div v-if="hasAny" class="space-y-4">
      <div v-if="mykad" class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><IdCard class="h-[18px] w-[18px]" aria-hidden="true" /></span>
        <div class="min-w-0 text-sm">
          <p class="font-medium text-ink-900">{{ mykadOk ? "MyKad berjaya dibaca" : "MyKad tidak dapat dibaca sepenuhnya" }}</p>
          <p class="text-slate-500">
            <template v-if="icMasked">No. KP {{ icMasked }}</template>
            <template v-if="icMasked && padananSkor != null"> · </template>
            <template v-if="padananSkor != null">Padanan borang {{ padananSkor }}%</template>
          </p>
        </div>
      </div>

      <div v-if="wajah" class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><ScanFace class="h-[18px] w-[18px]" aria-hidden="true" /></span>
        <div class="min-w-0 flex-1 text-sm">
          <p class="font-medium text-ink-900">Swafoto & ujian keaktifan</p>
          <p v-if="wajah.ringkasan" class="text-slate-500">{{ wajah.ringkasan }}</p>
          <div v-if="meters.length" class="mt-3 space-y-2.5">
            <div v-for="m in meters" :key="m.label">
              <div class="flex justify-between text-xs">
                <span class="text-slate-500">{{ m.label }}</span>
                <span class="font-semibold tabular-nums text-ink-900">{{ m.value }}%</span>
              </div>
              <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100" role="meter" :aria-valuenow="m.value" aria-valuemin="0" aria-valuemax="100" :aria-label="m.label">
                <div
                  class="h-full rounded-full"
                  :class="m.value >= 80 ? 'bg-emerald-500' : m.value >= 60 ? 'bg-amber-500' : 'bg-rose-500'"
                  :style="{ width: `${Math.min(100, Math.max(0, m.value))}%` }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <p v-else class="text-sm text-slate-500">
      {{ onboardingVerified ? "Identiti anda telah disahkan semasa pendaftaran." : "Tiada rekod e-KYC bagi permohonan ini." }}
    </p>
  </PCard>
</template>
