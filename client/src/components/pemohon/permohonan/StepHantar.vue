<script setup lang="ts">
import { computed } from "vue";
import { AlertCircle, CheckCircle2, ClipboardList, FolderOpen, Lock, ScanFace, TriangleAlert } from "lucide-vue-next";

import PCard from "@/components/pemohon/ui/PCard.vue";
import type { PemohonMyKadScan, PpFaceResult } from "@/types";
import DokumenUploader from "./DokumenUploader.vue";
import RingkasanPermohonan from "./RingkasanPermohonan.vue";
import type { AttachmentRow, DataAltForm, FieldErrors, PermohonanForm } from "./permohonan-form";

const props = defineProps<{
  form: PermohonanForm;
  adaPasangan: boolean;
  ensureRecord: () => Promise<{ id: number; token: string } | null>;
  scan: PemohonMyKadScan | null;
  faceResult: PpFaceResult | null;
  dataAlt: DataAltForm;
  errors: FieldErrors;
}>();
defineEmits<{ goto: [step: number] }>();
const attachments = defineModel<AttachmentRow[]>("attachments", { required: true });

const ekyc = computed(() => {
  const k = props.faceResult?.keputusan;
  if (k === "lulus") return { cls: "border-emerald-200 bg-emerald-50 text-emerald-900", icon: CheckCircle2, iconCls: "text-emerald-600", title: "e-KYC disahkan", text: "Identiti anda telah disahkan secara digital — permohonan boleh dinilai dengan lebih pantas." };
  if (k === "gagal") return { cls: "border-rose-200 bg-rose-50 text-rose-900", icon: AlertCircle, iconCls: "text-rose-600", title: "e-KYC tidak berjaya", text: "Anda boleh cuba semula di langkah Identiti, atau teruskan untuk semakan manual." };
  if (k === "semak") return { cls: "border-amber-200 bg-amber-50 text-amber-900", icon: ScanFace, iconCls: "text-amber-600", title: "e-KYC perlu semakan pegawai", text: "Pegawai TEKUN akan menyemak pengesahan wajah anda." };
  return { cls: "border-amber-200 bg-amber-50 text-amber-900", icon: TriangleAlert, iconCls: "text-amber-600", title: "e-KYC belum dibuat", text: "Tanpa e-KYC, permohonan akan disemak secara manual dan lebih lambat." };
});
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-start gap-3 rounded-2xl border p-4" :class="ekyc.cls">
      <component :is="ekyc.icon" class="mt-0.5 h-5 w-5 shrink-0" :class="ekyc.iconCls" />
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold">{{ ekyc.title }}</p>
        <p class="mt-0.5 text-sm opacity-90">{{ ekyc.text }}</p>
      </div>
      <button
        v-if="faceResult?.keputusan !== 'lulus'"
        type="button"
        class="shrink-0 rounded-lg px-2.5 py-1.5 text-xs font-semibold underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-black/5"
        @click="$emit('goto', 1)"
      >
        Buat e-KYC
      </button>
    </div>

    <PCard>
      <template #header>
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><FolderOpen class="h-5 w-5" /></span>
          <div>
            <h2 class="text-[15px] font-semibold text-ink-900">Dokumen sokongan</h2>
            <p class="mt-0.5 text-sm text-slate-500">AI akan mengklasifikasi dan menyemak setiap dokumen secara automatik.</p>
          </div>
        </div>
      </template>
      <p v-if="errors.mykad" class="mb-4 flex items-center justify-between gap-3 rounded-xl bg-rose-50 px-3.5 py-2.5 text-sm font-medium text-rose-700" role="alert">
        {{ errors.mykad }}
        <button type="button" class="shrink-0 text-xs font-semibold underline" @click="$emit('goto', 1)">Ke langkah Identiti</button>
      </p>
      <DokumenUploader v-model:attachments="attachments" :form="form" :ada-pasangan="adaPasangan" :ensure-record="ensureRecord" />
    </PCard>

    <PCard>
      <template #header>
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><ClipboardList class="h-5 w-5" /></span>
          <div>
            <h2 class="text-[15px] font-semibold text-ink-900">Semak permohonan anda</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pastikan semua maklumat betul sebelum menghantar.</p>
          </div>
        </div>
      </template>
      <RingkasanPermohonan
        :form="form"
        :ada-pasangan="adaPasangan"
        :attachments="attachments"
        :scan="scan"
        :face-result="faceResult"
        :data-alt="dataAlt"
        @goto="$emit('goto', $event)"
      />
    </PCard>

    <PCard title="Kebenaran penzahiran maklumat kredit">
      <p class="rounded-xl bg-slate-50 p-4 text-sm leading-relaxed text-slate-600">
        Saya dengan ini membenarkan TEKUN Nasional untuk mendapatkan, menyemak, dan berkongsi maklumat kredit saya daripada institusi/agensi
        pelaporan kredit yang berkaitan bagi tujuan penilaian permohonan pembiayaan ini.
      </p>
      <label
        class="mt-3 flex cursor-pointer items-start gap-3 rounded-xl border-2 p-4 text-sm font-medium transition-colors"
        :class="form.kebenaranKredit ? 'border-brand-600 bg-brand-50/60 text-ink-900' : errors.kebenaranKredit ? 'border-rose-300 bg-rose-50/50 text-ink-900' : 'border-slate-200 text-ink-800 hover:border-slate-300'"
      >
        <input v-model="form.kebenaranKredit" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-200" :aria-invalid="Boolean(errors.kebenaranKredit)" />
        <span>Saya bersetuju dengan Kebenaran Penzahiran Maklumat Kredit di atas <span class="text-brand-600">*</span></span>
      </label>
      <p v-if="errors.kebenaranKredit" class="mt-2 text-xs font-medium text-rose-600">{{ errors.kebenaranKredit }}</p>
      <p class="mt-4 flex items-center gap-1.5 text-xs text-slate-500"><Lock class="h-3.5 w-3.5" /> Data anda dilindungi di bawah Akta Perlindungan Data Peribadi 2010.</p>
    </PCard>
  </div>
</template>
