<script setup lang="ts">
import { computed } from "vue";
import { Sparkles, X } from "lucide-vue-next";

import { SPPT_DOCUMENT_CLASSES, type SpptDocumentClass } from "@/config/sppt-document-classes";

const props = defineProps<{
  open: boolean;
  fileName: string;
  classifying: boolean;
  suggestedClass: SpptDocumentClass;
  confidence: number;
  message: string;
  selectedClass: SpptDocumentClass;
  otherLabel: string;
}>();

const emit = defineEmits<{
  "update:selectedClass": [value: SpptDocumentClass];
  "update:otherLabel": [value: string];
  confirm: [];
  cancel: [];
}>();

const selectedClassModel = computed({
  get: () => props.selectedClass,
  set: (value: SpptDocumentClass) => emit("update:selectedClass", value),
});

const otherLabelModel = computed({
  get: () => props.otherLabel,
  set: (value: string) => emit("update:otherLabel", value),
});

const showOtherInput = computed(() => selectedClassModel.value === "lain_lain");

const canConfirm = computed(() => {
  if (props.classifying) {
    return false;
  }
  if (selectedClassModel.value === "lain_lain") {
    return otherLabelModel.value.trim().length > 0;
  }
  return true;
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
      @click.self="emit('cancel')"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="doc-class-title"
        class="w-full max-w-md rounded-xl border border-slate-200 bg-white shadow-xl"
      >
        <div class="flex items-start justify-between border-b border-slate-100 px-4 py-3">
          <div>
            <h3 id="doc-class-title" class="text-sm font-semibold text-slate-900">
              Sahkan Jenis Dokumen
            </h3>
            <p class="mt-0.5 truncate text-xs text-slate-500">{{ fileName }}</p>
          </div>
          <button
            type="button"
            class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
            aria-label="Tutup"
            @click="emit('cancel')"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="space-y-4 p-4">
          <div
            v-if="classifying"
            class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600"
          >
            <Sparkles class="h-4 w-4 animate-pulse text-violet-600" />
            AI sedang mengklasifikasi dokumen...
          </div>

          <div
            v-else
            class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900"
          >
            <p class="flex items-center gap-1.5 font-medium">
              <Sparkles class="h-4 w-4 shrink-0 text-violet-600" />
              Cadangan AI
            </p>
            <p class="mt-1 text-xs leading-relaxed">
              {{ message }}
              <span class="font-medium">(keyakinan {{ confidence }}%)</span>
            </p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Jenis Dokumen</label>
            <select
              v-model="selectedClassModel"
              :disabled="classifying"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:opacity-60"
            >
              <option v-for="item in SPPT_DOCUMENT_CLASSES" :key="item.value" :value="item.value">
                {{ item.label }}
              </option>
            </select>
          </div>

          <div v-if="showOtherInput">
            <label class="mb-1 block text-xs font-medium text-slate-700">
              Nyatakan jenis dokumen
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="otherLabelModel"
              type="text"
              :disabled="classifying"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 disabled:opacity-60"
              placeholder="Contoh: Surat Pengesahan Majikan"
            />
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 px-4 py-3">
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
            @click="emit('cancel')"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="!canConfirm"
            class="rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
            @click="emit('confirm')"
          >
            Sahkan &amp; Lampirkan
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
