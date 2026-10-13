<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import { ArrowLeft, ArrowRight, Save, Send } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import PProgressSteps from "@/components/pemohon/ui/PProgressSteps.vue";
import PermohonanBerjaya from "@/components/pemohon/permohonan/PermohonanBerjaya.vue";
import StepDiri from "@/components/pemohon/permohonan/StepDiri.vue";
import StepHantar from "@/components/pemohon/permohonan/StepHantar.vue";
import StepIdentiti from "@/components/pemohon/permohonan/StepIdentiti.vue";
import StepPembiayaan from "@/components/pemohon/permohonan/StepPembiayaan.vue";
import StepPerniagaan from "@/components/pemohon/permohonan/StepPerniagaan.vue";
import StepSokongan from "@/components/pemohon/permohonan/StepSokongan.vue";
import {
  autofillFromMyKad,
  createDataAlt,
  createPermohonanForm,
  dataAltPayload,
  errorMessage,
  hasAnyDataAlt,
  mergeDataAlt,
  validateStep,
  type AttachmentRow,
  type FieldErrors,
} from "@/components/pemohon/permohonan/permohonan-form";
import { usePemohonDraft } from "@/composables/usePemohonDraft";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { PERMOHONAN_BARU_STEPS, PRODUK_OPTIONS } from "@/data/portal-dummy";
import { savePemohonDataAlternatif, updatePemohonPermohonan } from "@/api/pemohonPermohonan";
import type { PemohonMyKadScan, PpFaceResult } from "@/types";

const pemohon = usePemohonStore();
const toast = useToast();
const draft = usePemohonDraft();

const STEPS = PERMOHONAN_BARU_STEPS;
const LAST = STEPS.length - 1;
const STEP_INTRO: Record<string, string> = {
  pembiayaan: "Berapa yang anda perlukan dan untuk apa?",
  identiti: "Sahkan identiti anda secara digital dalam masa kurang 2 minit.",
  diri: "Maklumat peribadi, alamat dan pasangan.",
  perniagaan: "Ceritakan tentang perniagaan dan sumber pendapatan anda.",
  sokongan: "Penyokong, perujuk dan pilihan perlindungan.",
  hantar: "Lampirkan dokumen, semak dan hantar.",
};

const currentStep = ref(0);
const draftSaving = ref(false);
const submitting = ref(false);
const form = reactive(createPermohonanForm(pemohon.profil));
const adaPasangan = ref(false);
const attachments = ref<AttachmentRow[]>([]);
const ekycMyKad = ref<PemohonMyKadScan | null>(null);
const ekycWajah = ref<PpFaceResult | null>(null);
const dataAlt = reactive(createDataAlt());
const filledFields = ref<string[]>([]);
const attempted = ref<number[]>([]);
const submitted = ref<{ id: number; token: string; noRujukan: string; produk: string; jumlah: number } | null>(null);

// ---- Draft restore (shared with the e-KYC page) ----
const saved = draft.read();
const restoredForm = Boolean(saved?.form);
if (saved) {
  if (saved.form) {
    Object.assign(form, saved.form);
    adaPasangan.value = Boolean(saved.adaPasangan);
    currentStep.value = Math.min(Math.max(Number(saved.currentStep) || 0, 0), LAST);
  }
  attachments.value = (saved.attachments ?? []).map((a) => ({ ...a, verifying: false }));
  ekycMyKad.value = saved.ekycMyKad ?? null;
  ekycWajah.value = saved.ekycWajah ?? null;
  mergeDataAlt(dataAlt, saved.dataAlternatif);
}
onMounted(() => {
  if (restoredForm) toast.info("Draf dimuat", "Permohonan draf anda telah dipulihkan.");
});

/** Single in-flight record creation (MyKad front & back may upload at the same time). */
let pendingRecord: Promise<{ id: number; token: string } | null> | null = null;
function ensureRecord() {
  if (draft.backendId.value && draft.accessToken.value) return Promise.resolve({ id: draft.backendId.value, token: draft.accessToken.value });
  pendingRecord ??= draft.ensureBackendRecord(form.nama).finally(() => (pendingRecord = null));
  return pendingRecord;
}

function buildDetails(): Record<string, unknown> {
  return { ...form, adaPasangan: adaPasangan.value, tujuan: form.tujuanPembiayaan };
}

function persistDraft() {
  if (submitted.value) return;
  draft.save({
    form: { ...form },
    adaPasangan: adaPasangan.value,
    attachments: attachments.value.map(({ verifying: _v, classifying: _c, ...a }) => a),
    currentStep: currentStep.value,
    ekycMyKad: ekycMyKad.value,
    ekycWajah: ekycWajah.value,
    dataAlternatif: dataAltPayload(dataAlt),
  });
}

let persistTimer: number | undefined;
function schedulePersist() {
  window.clearTimeout(persistTimer);
  persistTimer = window.setTimeout(persistDraft, 600);
}
watch([attachments, ekycMyKad, ekycWajah, adaPasangan], persistDraft, { deep: true });
watch([() => ({ ...form }), () => dataAltPayload(dataAlt)], schedulePersist, { deep: true });
onBeforeUnmount(() => {
  window.clearTimeout(persistTimer);
  persistDraft();
});

// ---- Validation ----
const stepErrorsFor = (i: number): FieldErrors => validateStep(STEPS[i].id, form, attachments.value);
const errors = computed<FieldErrors>(() => Object.assign({}, ...attempted.value.map(stepErrorsFor)));
const errorSteps = computed(() => attempted.value.filter((i) => Object.keys(stepErrorsFor(i)).length > 0));

function markAttempted(steps: number[]) {
  attempted.value = Array.from(new Set([...attempted.value, ...steps]));
}

async function focusFirstError() {
  await nextTick();
  const el = document.querySelector<HTMLElement>("[data-step-body] .border-rose-400, [data-step-body] [role='alert'], [data-step-body] [aria-invalid='true']");
  el?.scrollIntoView({ behavior: "smooth", block: "center" });
  if (el && "focus" in el) el.focus({ preventScroll: true });
}

// ---- Data alternatif ----
async function saveDataAlternatif(): Promise<boolean> {
  const anything = hasAnyDataAlt(dataAlt) || dataAlt.ccrisTersedia || dataAlt.penyataBankTersedia;
  if (!anything && !draft.backendId.value) return true;
  const record = await ensureRecord();
  if (!record) return false;
  try {
    await savePemohonDataAlternatif(record.id, record.token, dataAltPayload(dataAlt));
    return true;
  } catch (e) {
    toast.error("Data alternatif belum disimpan", errorMessage(e, "Sila semak nilai yang dimasukkan."));
    return false;
  }
}

// ---- Navigation ----
function goTo(i: number) {
  currentStep.value = Math.min(Math.max(i, 0), LAST);
  window.scrollTo({ top: 0, behavior: "smooth" });
}

watch(currentStep, (now, before) => {
  persistDraft();
  if (STEPS[before]?.id === "perniagaan" && now !== before) void saveDataAlternatif();
});

function next() {
  const e = stepErrorsFor(currentStep.value);
  if (Object.keys(e).length) {
    markAttempted([currentStep.value]);
    toast.error("Maklumat belum lengkap", "Sila lengkapkan medan yang ditanda.");
    void focusFirstError();
    return;
  }
  if (currentStep.value < LAST) goTo(currentStep.value + 1);
}

function back() {
  if (currentStep.value > 0) goTo(currentStep.value - 1);
}

// ---- e-KYC events ----
function onAutofill(scan: PemohonMyKadScan) {
  const filled = autofillFromMyKad(form, scan);
  filledFields.value = filled;
  if (filled.length) toast.success("Borang diisi secara automatik", `${filled.length} medan diisi daripada MyKad anda.`);
  else toast.info("Tiada medan diisi", "AI tidak menemui maklumat yang boleh digunakan.");
}

function onVerified(result: PpFaceResult) {
  ekycWajah.value = result;
  if (result.keputusan) pemohon.setEkycResult(result.keputusan);
  if (result.keputusan === "lulus") toast.success("Wajah disahkan", "Pengesahan identiti anda berjaya.");
}

// ---- Draf & Hantar ----
const produkLabel = computed(() => PRODUK_OPTIONS.find((p) => p.value === form.kategoriPembiayaan)?.label);

async function saveDraft() {
  draftSaving.value = true;
  try {
    const record = await ensureRecord();
    if (!record) return;
    await updatePemohonPermohonan(record.id, record.token, {
      status: "Draf",
      nama: form.nama || "Draf",
      kategoriPembiayaan: produkLabel.value,
      jumlahPermohonan: form.jumlahPermohonan,
      pemohonEmail: form.email || undefined,
      pemohonTelefon: form.noTelefonBimbit || undefined,
      details: buildDetails(),
    });
    await saveDataAlternatif();
    persistDraft();
    toast.success("Draf Disimpan", "Anda boleh menyambung permohonan ini kemudian.");
  } catch (e) {
    toast.error("Gagal menyimpan draf", errorMessage(e));
  } finally {
    draftSaving.value = false;
  }
}

async function submit() {
  const failing = STEPS.map((_, i) => i).filter((i) => Object.keys(stepErrorsFor(i)).length > 0);
  if (failing.length) {
    markAttempted(failing);
    goTo(failing[0]);
    toast.error("Permohonan belum lengkap", `Sila semak langkah "${STEPS[failing[0]].label}".`);
    void focusFirstError();
    return;
  }

  const identityMismatch = attachments.value.some((item) => {
    const v = item.verification;
    if (!v || (v.documentType !== "ic_front" && v.documentType !== "ic_combined")) return false;
    return v.identityMatched === false || v.status === "failed";
  });
  if (identityMismatch) {
    toast.error("MyKad depan tidak sepadan dengan nama atau No. Kad Pengenalan pemohon.");
    goTo(LAST);
    return;
  }

  submitting.value = true;
  try {
    const record = await ensureRecord();
    if (!record) return;
    await saveDataAlternatif();

    const res = await updatePemohonPermohonan(record.id, record.token, {
      status: "Dalam Semakan",
      nama: form.nama,
      kategoriPembiayaan: produkLabel.value,
      jumlahPermohonan: form.jumlahPermohonan,
      pemohonEmail: form.email || undefined,
      pemohonTelefon: form.noTelefonBimbit || undefined,
      details: buildDetails(),
    });

    const produk = produkLabel.value || "Pembiayaan";
    window.clearTimeout(persistTimer);
    submitted.value = { id: record.id, token: record.token, noRujukan: res.data.noRujukan, produk, jumlah: Number(form.jumlahPermohonan) || 0 };
    pemohon.addPermohonanSaya({
      id: record.id,
      token: record.token,
      noRujukan: res.data.noRujukan,
      produk,
      jumlah: Number(form.jumlahPermohonan) || 0,
      dihantarPada: new Date().toISOString(),
    });
    toast.success("Permohonan Dihantar", `Rujukan permohonan anda: ${res.data.noRujukan}`);
    window.scrollTo({ top: 0, behavior: "smooth" });
  } catch (e) {
    toast.error("Gagal menghantar permohonan", errorMessage(e));
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <PemohonLayout>
    <PermohonanBerjaya v-if="submitted" v-bind="submitted" />

    <div v-else class="mx-auto max-w-4xl">
      <PPageHeader
        eyebrow="Permohonan baharu"
        title="Mohon pembiayaan"
        subtitle="Lengkapkan 6 langkah ringkas. Kemajuan anda disimpan secara automatik."
        :back="{ name: 'pemohon-permohonan' }"
        back-label="Permohonan saya"
      >
        <template #actions>
          <div class="hidden md:block">
            <PButton variant="outline" :loading="draftSaving" @click="saveDraft">
              <Save v-if="!draftSaving" class="h-4 w-4" /> Simpan draf
            </PButton>
          </div>
        </template>
      </PPageHeader>

      <PCard class="mt-5">
        <PProgressSteps :steps="STEPS" :current="currentStep" :errors="errorSteps" @select="goTo" />
      </PCard>

      <div class="mb-4 mt-8">
        <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Langkah {{ currentStep + 1 }} daripada {{ STEPS.length }}</p>
        <h2 class="mt-1 text-xl font-bold tracking-tight text-ink-900 sm:text-2xl">{{ STEPS[currentStep].label }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ STEP_INTRO[STEPS[currentStep].id] }}</p>
      </div>

      <div data-step-body>
        <StepPembiayaan v-if="STEPS[currentStep].id === 'pembiayaan'" :form="form" :errors="errors" />
        <StepIdentiti
          v-else-if="STEPS[currentStep].id === 'identiti'"
          v-model:attachments="attachments"
          :ensure-record="ensureRecord"
          :scan="ekycMyKad"
          :face-result="ekycWajah"
          :filled-fields="filledFields"
          @scanned="ekycMyKad = $event"
          @autofill="onAutofill"
          @verified="onVerified"
        />
        <StepDiri v-else-if="STEPS[currentStep].id === 'diri'" v-model:ada-pasangan="adaPasangan" :form="form" :errors="errors" />
        <StepPerniagaan v-else-if="STEPS[currentStep].id === 'perniagaan'" :form="form" :data-alt="dataAlt" :ensure-record="ensureRecord" />
        <StepSokongan v-else-if="STEPS[currentStep].id === 'sokongan'" :form="form" />
        <StepHantar
          v-else
          v-model:attachments="attachments"
          :form="form"
          :ada-pasangan="adaPasangan"
          :ensure-record="ensureRecord"
          :scan="ekycMyKad"
          :face-result="ekycWajah"
          :data-alt="dataAlt"
          :errors="errors"
          @goto="goTo"
        />
      </div>

      <!-- Spacer so the fixed mobile action bar never covers content -->
      <div class="h-20 md:hidden" aria-hidden="true" />

      <!-- Action bar: fixed above the layout's bottom nav on mobile (right gap keeps clear of the chat launcher), inline on md+ -->
      <div
        class="fixed inset-x-0 bottom-[calc(76px+env(safe-area-inset-bottom))] z-30 px-3 lg:static lg:mt-8 lg:px-0"
        role="group"
        aria-label="Navigasi langkah"
      >
        <div
          class="mr-[64px] flex max-w-4xl items-center gap-2 rounded-2xl border border-slate-200/80 bg-white/95 p-2 shadow-[0_8px_30px_-12px_rgba(16,24,40,0.25)] backdrop-blur lg:mx-auto lg:mr-auto lg:rounded-none lg:border-0 lg:border-t lg:bg-transparent lg:p-0 lg:pt-6 lg:shadow-none"
        >
          <PButton variant="outline" :disabled="currentStep === 0" aria-label="Kembali" @click="back">
            <ArrowLeft class="h-4 w-4" /> <span class="hidden sm:inline">Kembali</span>
          </PButton>
          <PButton class="md:hidden" variant="ghost" :loading="draftSaving" aria-label="Simpan draf" @click="saveDraft">
            <Save v-if="!draftSaving" class="h-4 w-4" />
          </PButton>
          <div class="hidden flex-1 md:block" />
          <PButton v-if="currentStep < LAST" class="flex-1 md:flex-none" @click="next">
            Seterusnya <ArrowRight class="h-4 w-4" />
          </PButton>
          <PButton v-else class="flex-1 md:flex-none" :loading="submitting" @click="submit">
            <Send v-if="!submitting" class="h-4 w-4" /> Hantar permohonan
          </PButton>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
