<script setup lang="ts">
import { ref, watch } from "vue";
import { X } from "lucide-vue-next";
import type { WfProcess, WfProcessInput, WfProcessUpdateInput } from "@/types";

const props = defineProps<{
  open: boolean;
  workflowCode: string;
  editProcess: WfProcess | null;
  nextSequence: number;
}>();

const emit = defineEmits<{
  close: [];
  save: [payload: WfProcessInput | WfProcessUpdateInput, isEdit: boolean];
}>();

const name          = ref("");
const descBm        = ref("");
const sequence      = ref(1);
const kpi           = ref<number | null>(null);
const isActive      = ref(true);
const isEmail       = ref(false);
const isTodo        = ref(false);
const isByDivDept   = ref(false);

const BLOCK_KEYS = ["e", "E", "+", "-", ".", ","];

function blockNonInteger(e: KeyboardEvent) {
  if (BLOCK_KEYS.includes(e.key)) e.preventDefault();
}

function blockNonIntegerPaste(e: ClipboardEvent) {
  const text = e.clipboardData?.getData("text") ?? "";
  if (!/^\d+$/.test(text)) e.preventDefault();
}

watch(
  () => props.open,
  (val) => {
    if (!val) return;
    if (props.editProcess) {
      const p = props.editProcess;
      name.value        = p.wfpProcessName;
      descBm.value      = p.wfpProcessDescBm ?? "";
      sequence.value    = p.wfpSequence;
      kpi.value         = p.wfpDurationKpi ?? null;
      isActive.value    = p.wfpStatus === "1";
      isEmail.value     = !!p.wfpIsEmailNotification;
      isTodo.value      = !!p.wfpIsTodoNotification;
      isByDivDept.value = !!p.wfpIsByPtj;
    } else {
      name.value        = "";
      descBm.value      = "";
      sequence.value    = props.nextSequence;
      kpi.value         = null;
      isActive.value    = true;
      isEmail.value     = false;
      isTodo.value      = false;
      isByDivDept.value = false;
    }
  },
);

function submit() {
  if (!name.value.trim()) {
    alert("Nama proses diperlukan.");
    return;
  }

  const base = {
    wfpProcessName:           name.value.trim(),
    wfpProcessDescBm:         descBm.value.trim() || null,
    wfpSequence:              Number(sequence.value),
    wfpDurationKpi:           kpi.value !== null && kpi.value !== undefined ? Number(kpi.value) : null,
    wfpDurationKpiWithquery:  null,
    wfpStatus:                isActive.value ? "1" : "0",
    wfpIsEmailNotification:   isEmail.value ? 1 : 0,
    wfpIsTodoNotification:    isTodo.value ? 1 : 0,
    wfpIsByUnit:              null,
    wfpIsByPtj:               isByDivDept.value ? 1 : null,
    wfpIsAllowQuery:          null,
  };

  if (props.editProcess) {
    emit("save", base as WfProcessUpdateInput, true);
  } else {
    emit("save", { ...base, wfpWorkflowCode: props.workflowCode } as WfProcessInput, false);
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="emit('close')"
    >
      <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h3 class="text-sm font-semibold text-slate-800">
            {{ editProcess ? "Kemaskini proses" : "Tambah proses" }}
          </h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="emit('close')">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="space-y-4 px-5 py-4">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Nama proses <span class="text-red-500">*</span></label>
            <input
              v-model="name"
              type="text"
              class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
              placeholder="Nama proses"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Perihalan (BM)</label>
            <input
              v-model="descBm"
              type="text"
              class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">Susunan</label>
              <input
                v-model.number="sequence"
                type="number"
                min="0"
                class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
                @keydown="blockNonInteger"
                @paste="blockNonIntegerPaste"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">KPI (hari)</label>
              <input
                v-model.number="kpi"
                type="number"
                min="0"
                class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
                @keydown="blockNonInteger"
                @paste="blockNonIntegerPaste"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-x-3 gap-y-2">
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="isActive" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[var(--accent-600)]" />
              Status aktif
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="isEmail" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[var(--accent-600)]" />
              Notifikasi e-mel
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="isTodo" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[var(--accent-600)]" />
              Notifikasi tugasan
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="isByDivDept" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[var(--accent-600)]" />
              Mengikut Cawangan
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-3">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-4 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
            @click="emit('close')"
          >
            Batal
          </button>
          <button
            type="button"
            class="rounded-lg bg-[var(--accent-600)] px-4 py-1.5 text-sm font-medium text-white hover:bg-[var(--accent-700)]"
            @click="submit"
          >
            Simpan
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
