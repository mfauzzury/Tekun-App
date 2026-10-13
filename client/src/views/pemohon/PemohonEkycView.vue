<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { ArrowLeft, ArrowRight, CheckCircle2, CircleAlert, CreditCard, Home, RotateCcw, ScanFace, ShieldCheck, XCircle } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import EkycFaceResultCard from "@/components/pemohon/ekyc/EkycFaceResultCard.vue";
import EkycLivenessCheck from "@/components/pemohon/ekyc/EkycLivenessCheck.vue";
import EkycMyKadScan from "@/components/pemohon/ekyc/EkycMyKadScan.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import PProgressSteps from "@/components/pemohon/ui/PProgressSteps.vue";
import {
  autofillFromMyKad,
  createPermohonanForm,
  isMyKadBack,
  isMyKadFront,
  type AttachmentRow,
} from "@/components/pemohon/permohonan/permohonan-form";
import { usePemohonDraft } from "@/composables/usePemohonDraft";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import type { PemohonMyKadScan, PpFaceResult } from "@/types";

const pemohon = usePemohonStore();
const toast = useToast();
const draft = usePemohonDraft();

const STEPS = [
  { id: "mykad", label: "Muat naik MyKad" },
  { id: "wajah", label: "Pengesahan wajah" },
  { id: "keputusan", label: "Keputusan" },
];

// Shared draft with the application form — e-KYC results carry over into Permohonan Baharu.
const saved = draft.read();
const attachments = ref<AttachmentRow[]>((saved?.attachments ?? []).map((a) => ({ ...a, verifying: false })));
const scan = ref<PemohonMyKadScan | null>(saved?.ekycMyKad ?? null);
const face = ref<PpFaceResult | null>(saved?.ekycWajah ?? null);
const filledFields = ref<string[]>([]);
const currentStep = ref(face.value ? 2 : 0);

watch([attachments, scan, face], () => {
  draft.save({ attachments: attachments.value.map(({ verifying: _v, classifying: _c, ...a }) => a), ekycMyKad: scan.value, ekycWajah: face.value });
}, { deep: true });

let pendingRecord: Promise<{ id: number; token: string } | null> | null = null;
function ensureRecord() {
  if (draft.backendId.value && draft.accessToken.value) return Promise.resolve({ id: draft.backendId.value, token: draft.accessToken.value });
  pendingRecord ??= draft.ensureBackendRecord(pemohon.profil.nama).finally(() => (pendingRecord = null));
  return pendingRecord;
}

const hasFront = computed(() => attachments.value.some(isMyKadFront));
const hasBack = computed(() => attachments.value.some(isMyKadBack));
const myKadIssue = computed(() => Boolean(scan.value?.ocr?.tandaPalsu?.length) || scan.value?.ocr?.adalahMykad === false || scan.value?.ic?.valid === false);

/** Overall e-KYC: the face decision, downgraded to "semak" when the MyKad read raised flags. */
const overall = computed<"lulus" | "semak" | "gagal" | null>(() => {
  const k = face.value?.keputusan;
  if (!k) return null;
  return k === "lulus" && myKadIssue.value ? "semak" : k;
});

const overallMeta = computed(() => {
  if (overall.value === "lulus") return { icon: CheckCircle2, ring: "bg-emerald-50 text-emerald-600", title: "Identiti anda telah disahkan", text: "Tahniah! Permohonan anda kini layak untuk penilaian pantas AI." };
  if (overall.value === "gagal") return { icon: XCircle, ring: "bg-rose-50 text-rose-600", title: "Pengesahan tidak berjaya", text: "Cuba sekali lagi di tempat yang terang. Anda masih boleh memohon — permohonan akan disemak secara manual." };
  return { icon: CircleAlert, ring: "bg-amber-50 text-amber-600", title: "Perlu semakan pegawai", text: "Pengesahan anda direkodkan. Pegawai TEKUN akan membuat semakan tambahan." };
});

function onAutofill(s: PemohonMyKadScan) {
  const form = Object.assign(createPermohonanForm(pemohon.profil), draft.read()?.form ?? {});
  const filled = autofillFromMyKad(form, s);
  filledFields.value = filled;
  draft.save({ form: { ...form } });
  if (filled.length) toast.success("Borang permohonan diisi", `${filled.length} medan diisi daripada MyKad anda.`);
  else toast.info("Tiada medan diisi", "AI tidak menemui maklumat yang boleh digunakan.");
}

function onVerified(result: PpFaceResult) {
  face.value = result;
  const k = result.keputusan === "lulus" && myKadIssue.value ? "semak" : result.keputusan;
  if (k) pemohon.setEkycResult(k);
  goTo(2);
}

function goTo(i: number) {
  currentStep.value = i;
  window.scrollTo({ top: 0, behavior: "smooth" });
}
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-3xl pb-6">
      <PPageHeader
        eyebrow="e-KYC"
        title="Pengesahan identiti"
        subtitle="Sahkan identiti anda secara digital dengan MyKad dan wajah — kurang 2 minit."
        :back="{ name: 'pemohon-dashboard' }"
        back-label="Utama"
      />

      <PCard class="mt-5">
        <PProgressSteps :steps="STEPS" :current="currentStep" @select="goTo" />
      </PCard>

      <!-- 1: MyKad -->
      <PCard v-if="currentStep === 0" class="mt-5">
        <template #header>
          <div class="flex min-w-0 items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><CreditCard class="h-5 w-5" /></span>
            <div>
              <h2 class="text-[15px] font-semibold text-ink-900">Muat naik MyKad</h2>
              <p class="mt-0.5 text-sm text-slate-500">Ambil gambar bahagian depan dan belakang MyKad anda.</p>
            </div>
          </div>
        </template>
        <template #actions>
          <PBadge :tone="hasFront && hasBack ? 'success' : 'neutral'" dot>{{ Number(hasFront) + Number(hasBack) }}/2</PBadge>
        </template>
        <EkycMyKadScan
          v-model:attachments="attachments"
          :ensure-record="ensureRecord"
          :scan="scan"
          :filled-fields="filledFields"
          @scanned="scan = $event"
          @autofill="onAutofill"
        />
        <template #footer>
          <div class="flex items-center justify-between gap-3">
            <p class="hidden text-xs text-slate-500 sm:block"><ShieldCheck class="mr-1 inline h-3.5 w-3.5" />Data anda dienkripsi dan dilindungi.</p>
            <PButton class="w-full sm:w-auto" :disabled="!hasFront || !hasBack" @click="goTo(1)">
              Seterusnya <ArrowRight class="h-4 w-4" />
            </PButton>
          </div>
        </template>
      </PCard>

      <!-- 2: Wajah -->
      <PCard v-else-if="currentStep === 1" class="mt-5">
        <template #header>
          <div class="flex min-w-0 items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><ScanFace class="h-5 w-5" /></span>
            <div>
              <h2 class="text-[15px] font-semibold text-ink-900">Pengesahan wajah</h2>
              <p class="mt-0.5 text-sm text-slate-500">Kami akan memadankan wajah anda dengan foto pada MyKad.</p>
            </div>
          </div>
        </template>
        <EkycLivenessCheck :ensure-record="ensureRecord" :result="face" :has-my-kad="hasFront" @verified="onVerified" />
        <template #footer>
          <div class="flex items-center justify-between gap-3">
            <PButton variant="ghost" size="sm" @click="goTo(0)"><ArrowLeft class="h-4 w-4" /> Kembali</PButton>
            <PButton v-if="face" size="sm" @click="goTo(2)">Lihat keputusan <ArrowRight class="h-4 w-4" /></PButton>
          </div>
        </template>
      </PCard>

      <!-- 3: Keputusan -->
      <div v-else class="mt-5 space-y-5">
        <PCard v-if="face">
          <div class="flex flex-col items-center px-2 py-4 text-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full" :class="overallMeta.ring">
              <component :is="overallMeta.icon" class="h-9 w-9" />
            </span>
            <h2 class="mt-4 text-xl font-bold tracking-tight text-ink-900">{{ overallMeta.title }}</h2>
            <p class="mt-1 max-w-md text-sm text-slate-500">{{ overallMeta.text }}</p>
          </div>
          <div class="mt-2 space-y-3">
            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm">
              <span class="flex items-center gap-2 text-ink-800"><CreditCard class="h-4 w-4 text-slate-400" /> MyKad</span>
              <PBadge v-if="scan?.ocr?.ok && !myKadIssue" tone="success" dot>Dibaca & sah</PBadge>
              <PBadge v-else-if="scan?.ocr?.ok" tone="warning" dot>Perlu semakan</PBadge>
              <PBadge v-else :tone="hasFront && hasBack ? 'info' : 'neutral'" dot>{{ hasFront && hasBack ? "Dimuat naik" : "Tidak lengkap" }}</PBadge>
            </div>
            <EkycFaceResultCard :result="face" />
          </div>
        </PCard>
        <PCard v-else>
          <p class="py-6 text-center text-sm text-slate-500">Belum ada keputusan. Sila lengkapkan pengesahan wajah dahulu.</p>
        </PCard>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <PButton size="lg" to="/pemohon/permohonan/baru">Teruskan permohonan <ArrowRight class="h-4 w-4" /></PButton>
          <PButton v-if="overall !== 'lulus'" variant="outline" size="lg" @click="goTo(1)"><RotateCcw class="h-4 w-4" /> Cuba semula</PButton>
          <PButton variant="ghost" size="lg" to="/pemohon/dashboard"><Home class="h-4 w-4" /> Ke halaman utama</PButton>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
