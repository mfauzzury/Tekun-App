<script setup lang="ts">
import { computed } from "vue";
import { CreditCard, ScanFace, ShieldCheck, TriangleAlert } from "lucide-vue-next";

import EkycLivenessCheck from "@/components/pemohon/ekyc/EkycLivenessCheck.vue";
import EkycMyKadScan from "@/components/pemohon/ekyc/EkycMyKadScan.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import type { PemohonMyKadScan, PpFaceResult } from "@/types";
import { isMyKadBack, isMyKadFront, type AttachmentRow } from "./permohonan-form";

defineProps<{
  ensureRecord: () => Promise<{ id: number; token: string } | null>;
  scan: PemohonMyKadScan | null;
  faceResult: PpFaceResult | null;
  filledFields: string[];
}>();
defineEmits<{ scanned: [scan: PemohonMyKadScan]; autofill: [scan: PemohonMyKadScan]; verified: [result: PpFaceResult] }>();
const attachments = defineModel<AttachmentRow[]>("attachments", { required: true });

const hasFront = computed(() => attachments.value.some(isMyKadFront));
const hasBack = computed(() => attachments.value.some(isMyKadBack));
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-start gap-3 rounded-2xl border border-ink-100 bg-ink-50/60 p-4">
      <ShieldCheck class="mt-0.5 h-5 w-5 shrink-0 text-ink-700" />
      <p class="text-sm text-ink-800">
        <span class="font-semibold">Pengesahan identiti digital (e-KYC)</span> mempercepatkan kelulusan. Maklumat anda dienkripsi dan hanya
        digunakan untuk tujuan pengesahan permohonan TEKUN.
      </p>
    </div>

    <PCard>
      <template #header>
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-sm font-bold text-brand-700">1</span>
          <div>
            <h2 class="flex items-center gap-2 text-[15px] font-semibold text-ink-900"><CreditCard class="h-4 w-4 text-slate-400" /> Muat naik MyKad</h2>
            <p class="mt-0.5 text-sm text-slate-500">AI akan membaca maklumat anda dan mengisi borang secara automatik.</p>
          </div>
        </div>
      </template>
      <template #actions>
        <PBadge :tone="hasFront && hasBack ? 'success' : 'neutral'" dot>{{ hasFront && hasBack ? "Lengkap" : `${Number(hasFront) + Number(hasBack)}/2` }}</PBadge>
      </template>
      <EkycMyKadScan
        v-model:attachments="attachments"
        :ensure-record="ensureRecord"
        :scan="scan"
        :filled-fields="filledFields"
        @scanned="$emit('scanned', $event)"
        @autofill="$emit('autofill', $event)"
      />
    </PCard>

    <PCard>
      <template #header>
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-sm font-bold text-brand-700">2</span>
          <div>
            <h2 class="flex items-center gap-2 text-[15px] font-semibold text-ink-900"><ScanFace class="h-4 w-4 text-slate-400" /> Pengesahan wajah</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pastikan anda berada di tempat yang terang dan tanggalkan cermin mata gelap.</p>
          </div>
        </div>
      </template>
      <template #actions>
        <PBadge v-if="faceResult" :tone="faceResult.keputusan === 'lulus' ? 'success' : faceResult.keputusan === 'gagal' ? 'danger' : 'warning'" dot>
          {{ faceResult.keputusan === "lulus" ? "Lulus" : faceResult.keputusan === "gagal" ? "Gagal" : "Semakan" }}
        </PBadge>
      </template>
      <EkycLivenessCheck :ensure-record="ensureRecord" :result="faceResult" :has-my-kad="hasFront" @verified="$emit('verified', $event)" />
    </PCard>

    <div v-if="!faceResult" class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="note">
      <TriangleAlert class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
      <p>
        <span class="font-semibold">e-KYC sangat disyorkan.</span> Anda boleh teruskan tanpanya, tetapi tanpa e-KYC, permohonan akan disemak secara manual
        dan lebih lambat.
      </p>
    </div>
  </div>
</template>
