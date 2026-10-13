<script setup lang="ts">
import { computed, ref } from "vue";
import { CheckCircle2, ExternalLink, FileText, Loader2, ShieldCheck, Trash2, UploadCloud, XCircle } from "lucide-vue-next";

import PBadge from "@/components/pemohon/ui/PBadge.vue";
import {
  classifyPemohonPermohonanDocument,
  deletePemohonPermohonanDocument,
  pemohonPermohonanDocumentViewUrl,
  uploadPemohonPermohonanDocument,
  verifyPemohonPermohonanDocument,
} from "@/api/pemohonPermohonan";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import { documentClassLabel } from "@/config/sppt-document-classes";
import { SPPT_DOCUMENT_MAX_BYTES, SPPT_DOCUMENT_MAX_LABEL } from "@/config/sppt-upload";
import { errorMessage, type AttachmentRow, type PermohonanForm } from "./permohonan-form";

const props = defineProps<{ form: PermohonanForm; adaPasangan: boolean; ensureRecord: () => Promise<{ id: number; token: string } | null> }>();
const attachments = defineModel<AttachmentRow[]>("attachments", { required: true });

const toast = useToast();
const { confirm } = useConfirmDialog();

const fileInputRef = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const dragOver = ref(false);
const removing = ref<string | null>(null);

const spouseIcRequired = computed(() => props.adaPasangan && Boolean(props.form.noIcPasangan.trim()));

const icVerificationSummary = computed(() => {
  const verified = attachments.value.filter((item) => item.verification?.status === "verified");
  const has = (types: string[], spouse: boolean) =>
    verified.some((i) => types.includes(i.verification?.documentType ?? "") && (i.verification?.subject === "spouse") === spouse);
  return [
    { label: "IC Pemohon Depan", ok: has(["ic_front", "ic_combined"], false) },
    { label: "IC Pemohon Belakang", ok: has(["ic_back", "ic_combined"], false) },
    { label: "IC Pasangan Depan", ok: has(["ic_front", "ic_combined"], true) },
    { label: "IC Pasangan Belakang", ok: has(["ic_back", "ic_combined"], true) },
  ];
});


/** In-place updates on the parent's reactive array (avoids stale reads through chained v-models). */
function addRow(row: AttachmentRow) {
  attachments.value.push(row);
}
function patchRow(id: string, p: Partial<AttachmentRow>) {
  const i = attachments.value.findIndex((a) => a.id === id);
  if (i >= 0) attachments.value[i] = { ...attachments.value[i], ...p };
}
function dropRow(id: string) {
  const i = attachments.value.findIndex((a) => a.id === id);
  if (i >= 0) attachments.value.splice(i, 1);
}

async function handleFiles(files: File[]) {
  if (!files.length) return;
  const record = await props.ensureRecord();
  if (!record) return;
  const f = props.form;
  const spouseIc = spouseIcRequired.value ? f.noIcPasangan : undefined;
  const spouseName = spouseIcRequired.value ? f.namaPasangan : undefined;

  for (const file of files) {
    if (file.size > SPPT_DOCUMENT_MAX_BYTES) {
      toast.error(`Fail "${file.name}" melebihi had ${SPPT_DOCUMENT_MAX_LABEL}.`);
      continue;
    }
    uploading.value = true;
    try {
      let suggestedClass: string | undefined;
      try {
        const classifyRes = await classifyPemohonPermohonanDocument(file, f.noIcBaru, f.nama, spouseIc, spouseName);
        if (classifyRes.data.classification.identityMatched === false) {
          toast.error(classifyRes.data.classification.message || "MyKad tidak sepadan dengan pemohon.");
          continue;
        }
        suggestedClass = classifyRes.data.classification.suggestedClass;
      } catch {
        // Classification is a convenience suggestion — proceed without it if it fails.
      }

      const uploadRes = await uploadPemohonPermohonanDocument(record.id, record.token, file, suggestedClass);
      const row: AttachmentRow = { ...uploadRes.data };
      const ext = file.name.split(".").pop()?.toLowerCase() ?? "";
      const verifiable = ["jpg", "jpeg", "png"].includes(ext);
      addRow({ ...row, verifying: verifiable });

      if (verifiable) {
        try {
          const verifyRes = await verifyPemohonPermohonanDocument(file, f.noIcBaru, f.nama, spouseIc, spouseName);
          const verification = verifyRes.data.verification;
          patchRow(row.id, { verification, verifying: false });

          const isFrontOrCombined = verification.documentType === "ic_front" || verification.documentType === "ic_combined";
          if (isFrontOrCombined && (verification.identityMatched === false || verification.status === "failed")) {
            toast.error(verification.message || "MyKad tidak sepadan dengan nama atau No. Kad Pengenalan pemohon.");
            await deletePemohonPermohonanDocument(record.id, record.token, row.id);
            dropRow(row.id);
          }
        } catch {
          patchRow(row.id, { verifying: false });
        }
      }
    } catch (e) {
      toast.error(`Gagal memuat naik "${file.name}"`, errorMessage(e));
    } finally {
      uploading.value = false;
    }
  }
}

function onFilesSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = input.files ? Array.from(input.files) : [];
  input.value = "";
  void handleFiles(files);
}

function onDrop(ev: DragEvent) {
  dragOver.value = false;
  void handleFiles(Array.from(ev.dataTransfer?.files ?? []));
}

async function removeAttachment(item: AttachmentRow) {
  const ok = await confirm({ title: "Padam dokumen?", message: `"${item.name}" akan dibuang daripada permohonan ini.`, confirmText: "Padam", cancelText: "Batal", destructive: true });
  if (!ok) return;
  const record = await props.ensureRecord();
  if (!record) return;
  removing.value = item.id;
  try {
    await deletePemohonPermohonanDocument(record.id, record.token, item.id);
    dropRow(item.id);
  } catch (e) {
    toast.error("Gagal memadam dokumen", errorMessage(e));
  } finally {
    removing.value = null;
  }
}

function fmtSize(bytes: number) {
  return bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

/** The applicant's own MyKad is checked by the AI reading in step 2; the old image check is not shown for it. */
const AI_READ_CLASSES = ["ic_pemohon_depan", "ic_pemohon_belakang", "ic_pemohon_combined"];
</script>

<template>
  <div class="space-y-4">
    <div v-if="spouseIcRequired" class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-800">
      Pasangan didaftarkan — sila sertakan juga salinan MyKad depan &amp; belakang pasangan.
    </div>

    <label
      class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-4 py-8 text-center transition-colors focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-100"
      :class="dragOver ? 'border-brand-500 bg-brand-50' : 'border-slate-300 bg-white hover:border-brand-400 hover:bg-brand-50/40'"
      @dragover.prevent="dragOver = true"
      @dragleave="dragOver = false"
      @drop.prevent="onDrop"
    >
      <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
        <Loader2 v-if="uploading" class="h-6 w-6 animate-spin" />
        <UploadCloud v-else class="h-6 w-6" />
      </span>
      <span class="text-sm font-semibold text-ink-900">{{ uploading ? "Memproses dokumen…" : "Seret fail ke sini atau klik untuk pilih" }}</span>
      <span class="text-xs text-slate-500">SSM / lesen perniagaan, penyata bank, pelan perniagaan, dll. PDF/JPG/PNG, maks {{ SPPT_DOCUMENT_MAX_LABEL }} setiap fail.</span>
      <input ref="fileInputRef" type="file" accept=".pdf,image/*" multiple class="sr-only" @change="onFilesSelected" />
    </label>

    <ul v-if="attachments.length" class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white">
      <li v-for="item in attachments" :key="item.id" class="flex items-center gap-3 px-4 py-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500"><FileText class="h-5 w-5" /></span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium text-ink-900" :title="item.name">{{ item.name }}</p>
          <div class="mt-1 flex flex-wrap items-center gap-1.5">
            <PBadge :tone="item.documentClass ? 'info' : 'neutral'">{{ documentClassLabel(item.documentClass, item.documentClassOther) }}</PBadge>
            <span v-if="item.verifying" class="inline-flex items-center gap-1 text-xs text-slate-500"><Loader2 class="h-3 w-3 animate-spin" /> Mengesahkan…</span>
            <PBadge v-else-if="item.verification?.status === 'verified'" tone="success"><ShieldCheck class="h-3 w-3" /> Disahkan</PBadge>
            <PBadge v-else-if="item.verification?.status === 'failed' && !AI_READ_CLASSES.includes(item.documentClass ?? '')" tone="danger"><XCircle class="h-3 w-3" /> {{ item.verification.message || "Gagal disahkan" }}</PBadge>
            <span v-if="item.size" class="text-xs text-slate-400">{{ fmtSize(item.size) }}</span>
          </div>
        </div>
        <a
          v-if="item.url"
          :href="pemohonPermohonanDocumentViewUrl(item.url)"
          target="_blank"
          rel="noopener"
          class="hidden h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-ink-700 sm:flex"
          :aria-label="`Lihat ${item.name}`"
        >
          <ExternalLink class="h-4 w-4" />
        </a>
        <button
          type="button"
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-rose-100 disabled:opacity-50"
          :aria-label="`Padam ${item.name}`"
          :disabled="removing === item.id"
          @click="removeAttachment(item)"
        >
          <Loader2 v-if="removing === item.id" class="h-4 w-4 animate-spin" />
          <Trash2 v-else class="h-4 w-4" />
        </button>
      </li>
    </ul>

    <div v-if="spouseIcRequired" class="grid grid-cols-2 gap-2 text-xs">
      <p v-for="s in icVerificationSummary" :key="s.label" class="flex items-center gap-1" :class="s.ok ? 'text-emerald-600' : 'text-slate-400'">
        <CheckCircle2 class="h-3.5 w-3.5" />{{ s.label }}
      </p>
    </div>
  </div>
</template>
