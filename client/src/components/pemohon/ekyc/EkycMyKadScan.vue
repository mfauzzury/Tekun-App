<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from "vue";
import { CreditCard, ImageUp, Loader2, RefreshCw, ShieldCheck, Trash2, TriangleAlert } from "lucide-vue-next";

import EkycMyKadOcrCard from "@/components/pemohon/ekyc/EkycMyKadOcrCard.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import {
  deletePemohonPermohonanDocument,
  pemohonPermohonanDocumentViewUrl,
  scanPemohonMyKad,
  uploadPemohonPermohonanDocument,
  verifyPemohonPermohonanDocument,
} from "@/api/pemohonPermohonan";
import { useToast } from "@/composables/useToast";
import { SPPT_DOCUMENT_MAX_BYTES, SPPT_DOCUMENT_MAX_LABEL } from "@/config/sppt-upload";
import { errorMessage, type AttachmentRow } from "@/components/pemohon/permohonan/permohonan-form";
import type { PemohonMyKadScan } from "@/types";

/**
 * e-KYC part 1 — MyKad front & back upload (into the application's attachments, so document
 * verification and submit checks keep working) followed by AI OCR of the front.
 */
const props = withDefaults(
  defineProps<{
    ensureRecord: () => Promise<{ id: number; token: string } | null>;
    scan: PemohonMyKadScan | null;
    filledFields?: string[];
    showAutofill?: boolean;
  }>(),
  { filledFields: () => [], showAutofill: true },
);
const emit = defineEmits<{ scanned: [scan: PemohonMyKadScan]; autofill: [scan: PemohonMyKadScan] }>();
const attachments = defineModel<AttachmentRow[]>("attachments", { required: true });

const toast = useToast();

type Side = "depan" | "belakang";
const SIDES: { key: Side; label: string; hint: string; cls: string; expect: string[] }[] = [
  { key: "depan", label: "MyKad depan", hint: "Bahagian bergambar", cls: "ic_pemohon_depan", expect: ["ic_front", "ic_combined"] },
  { key: "belakang", label: "MyKad belakang", hint: "Bahagian cip & kod bar", cls: "ic_pemohon_belakang", expect: ["ic_back", "ic_combined"] },
];

const busy = ref<Record<Side, boolean>>({ depan: false, belakang: false });
const dragOver = ref<Side | null>(null);
const scanning = ref(false);
const localPreview = ref<Record<string, string>>({});

const bySide = computed(() => ({
  depan: attachments.value.find((a) => a.documentClass === "ic_pemohon_depan") ?? null,
  belakang: attachments.value.find((a) => a.documentClass === "ic_pemohon_belakang") ?? null,
}));

function previewOf(a: AttachmentRow) {
  if (localPreview.value[a.id]) return localPreview.value[a.id];
  const isImage = a.mimeType?.startsWith("image/") || /\.(jpe?g|png|webp)$/i.test(a.name);
  return isImage && a.url ? pemohonPermohonanDocumentViewUrl(a.url) : null;
}


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

async function upload(side: Side, file: File | undefined) {
  if (!file) return;
  if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
    toast.error("Format tidak disokong", "Sila gunakan gambar JPG, PNG atau WEBP.");
    return;
  }
  if (file.size > SPPT_DOCUMENT_MAX_BYTES) {
    toast.error(`Fail melebihi had ${SPPT_DOCUMENT_MAX_LABEL}`);
    return;
  }
  const meta = SIDES.find((s) => s.key === side)!;
  busy.value[side] = true;
  try {
    const record = await props.ensureRecord();
    if (!record) return;

    const previous = bySide.value[side];
    if (previous) {
      await deletePemohonPermohonanDocument(record.id, record.token, previous.id).catch(() => undefined);
      dropRow(previous.id);
    }

    const res = await uploadPemohonPermohonanDocument(record.id, record.token, file, meta.cls);
    const row: AttachmentRow = { ...res.data, verifying: true };
    localPreview.value[row.id] = URL.createObjectURL(file);
    addRow(row);

    // Document-type check only (identity is matched against the form on Hantar).
    verifyPemohonPermohonanDocument(file)
      .then((v) => {
        patchRow(row.id, { verification: v.data.verification, verifying: false });
        if (v.data.verification.documentType && !meta.expect.includes(v.data.verification.documentType)) {
          toast.info(`Semak gambar ${meta.label}`, "AI tidak pasti gambar ini ialah bahagian yang betul. Anda boleh menukarnya.");
        }
      })
      .catch(() => patchRow(row.id, { verifying: false }));

    if (side === "depan") await runScan(record);
  } catch (e) {
    toast.error(`Gagal memuat naik ${meta.label}`, errorMessage(e));
  } finally {
    busy.value[side] = false;
  }
}

async function runScan(known?: { id: number; token: string } | null) {
  const record = known ?? (await props.ensureRecord());
  if (!record) return;
  scanning.value = true;
  try {
    const res = await scanPemohonMyKad(record.id, record.token);
    emit("scanned", res.data);
    if (!res.data.ocr?.ok) toast.info("MyKad tidak dapat dibaca", res.data.ocr?.message || "Cuba gambar yang lebih jelas.");
  } catch (e) {
    toast.error("Imbasan MyKad gagal", errorMessage(e, "Sila cuba sebentar lagi."));
  } finally {
    scanning.value = false;
  }
}

async function remove(side: Side) {
  const a = bySide.value[side];
  if (!a) return;
  busy.value[side] = true;
  try {
    const record = await props.ensureRecord();
    if (!record) return;
    await deletePemohonPermohonanDocument(record.id, record.token, a.id);
    dropRow(a.id);
  } catch (e) {
    toast.error("Gagal memadam gambar", errorMessage(e));
  } finally {
    busy.value[side] = false;
  }
}

function onPick(side: Side, ev: Event) {
  const input = ev.target as HTMLInputElement;
  const file = input.files?.[0];
  input.value = "";
  void upload(side, file);
}

function onDrop(side: Side, ev: DragEvent) {
  dragOver.value = null;
  void upload(side, ev.dataTransfer?.files?.[0]);
}

onBeforeUnmount(() => Object.values(localPreview.value).forEach((u) => URL.revokeObjectURL(u)));
</script>

<template>
  <div class="space-y-4">
    <div class="grid gap-3 sm:grid-cols-2">
      <div v-for="s in SIDES" :key="s.key">
        <!-- Uploaded -->
        <div v-if="bySide[s.key]" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
          <div class="relative aspect-[1.586] bg-slate-100">
            <img v-if="previewOf(bySide[s.key]!)" :src="previewOf(bySide[s.key]!)!" :alt="s.label" class="h-full w-full object-cover" />
            <CreditCard v-else class="absolute inset-0 m-auto h-10 w-10 text-slate-300" />
            <div v-if="busy[s.key]" class="absolute inset-0 flex items-center justify-center bg-white/70">
              <Loader2 class="h-6 w-6 animate-spin text-brand-600" />
            </div>
          </div>
          <div class="flex items-center gap-2 px-3 py-2.5">
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-ink-900">{{ s.label }}</p>
              <p v-if="bySide[s.key]!.verifying" class="flex items-center gap-1 text-xs text-slate-500"><Loader2 class="h-3 w-3 animate-spin" /> Menyemak dokumen…</p>
              <p v-else-if="bySide[s.key]!.verification?.status === 'verified'" class="flex items-center gap-1 text-xs font-medium text-emerald-700">
                <ShieldCheck class="h-3.5 w-3.5" /> Dokumen disahkan
              </p>
              <p v-else-if="bySide[s.key]!.verification?.status === 'failed'" class="flex items-center gap-1 truncate text-xs font-medium text-amber-700">
                <TriangleAlert class="h-3.5 w-3.5 shrink-0" /> {{ bySide[s.key]!.verification?.message || "Perlu disemak" }}
              </p>
              <p v-else class="text-xs text-slate-500">Dimuat naik</p>
            </div>
            <label
              class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl px-2.5 text-xs font-semibold text-ink-700 hover:bg-slate-100 focus-within:ring-4 focus-within:ring-slate-200"
              :class="busy[s.key] ? 'pointer-events-none opacity-50' : ''"
            >
              <RefreshCw class="h-3.5 w-3.5" /> Tukar
              <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" :disabled="busy[s.key]" @change="onPick(s.key, $event)" />
            </label>
            <button
              type="button"
              class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-rose-100 disabled:opacity-50"
              :aria-label="`Padam ${s.label}`"
              :disabled="busy[s.key]"
              @click="remove(s.key)"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Dropzone -->
        <label
          v-else
          class="group flex aspect-[1.586] cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed p-4 text-center transition-colors focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-100"
          :class="[
            dragOver === s.key ? 'border-brand-500 bg-brand-50' : 'border-slate-300 bg-white hover:border-brand-400 hover:bg-brand-50/40',
            busy[s.key] ? 'pointer-events-none' : '',
          ]"
          @dragover.prevent="dragOver = s.key"
          @dragleave="dragOver = null"
          @drop.prevent="onDrop(s.key, $event)"
        >
          <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 transition-colors group-hover:bg-brand-100 group-hover:text-brand-600">
            <Loader2 v-if="busy[s.key]" class="h-6 w-6 animate-spin text-brand-600" />
            <ImageUp v-else class="h-6 w-6" />
          </span>
          <span class="text-sm font-semibold text-ink-900">{{ busy[s.key] ? "Memuat naik…" : s.label }}</span>
          <span class="text-xs text-slate-500">{{ s.hint }} · ketik untuk ambil gambar atau pilih fail</span>
          <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" :disabled="busy[s.key]" @change="onPick(s.key, $event)" />
        </label>
      </div>
    </div>

    <p class="text-xs text-slate-500">Tips: letak kad di atas permukaan gelap, elakkan silau lampu, dan pastikan keempat-empat bucu kelihatan.</p>

    <EkycMyKadOcrCard
      :scan="scan"
      :scanning="scanning"
      :filled-fields="filledFields"
      :show-autofill="showAutofill"
      @autofill="scan && emit('autofill', scan)"
      @rescan="runScan()"
    />
  </div>
</template>
