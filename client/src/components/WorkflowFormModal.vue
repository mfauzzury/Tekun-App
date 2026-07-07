<script setup lang="ts">
import { ref, watch } from "vue";
import { X } from "lucide-vue-next";
import type { WfWorkflow, WfWorkflowInput, WfWorkflowUpdateInput } from "@/types";
import { storeWorkflow, updateWorkflow } from "@/api/workflow";
import { useToast } from "@/composables/useToast";

const toast = useToast();

const props = defineProps<{
  show: boolean;
  editing: WfWorkflow | null;
}>();

const emit = defineEmits<{
  close: [];
  saved: [];
}>();

const saving = ref(false);
const code = ref("");
const title = ref("");
const preventSelf = ref(0);
const involvePosting = ref(0);
const errors = ref<Record<string, string[]>>({});

watch(
  () => props.show,
  (v) => {
    if (v) {
      code.value = props.editing?.wfaWorkflowCode ?? "";
      title.value = props.editing?.wfaWorkflowTitle ?? "";
      preventSelf.value = props.editing?.wfaPreventSelfProcess ?? 0;
      involvePosting.value = props.editing?.wfaInvolvePosting ?? 0;
      errors.value = {};
    }
  },
);

async function save() {
  if (!title.value.trim()) {
    alert("Tajuk aliran kerja wajib.");
    return;
  }
  if (!props.editing && !code.value.trim()) {
    alert("Kod aliran kerja wajib.");
    return;
  }

  saving.value = true;
  errors.value = {};
  try {
    if (props.editing) {
      const payload: WfWorkflowUpdateInput = {
        wfaWorkflowTitle: title.value.trim(),
        wfaPreventSelfProcess: preventSelf.value,
        wfaInvolvePosting: involvePosting.value,
      };
      await updateWorkflow(props.editing.wfaWorkflowCode, payload);
    } else {
      const payload: WfWorkflowInput = {
        wfaWorkflowCode: code.value.trim().toUpperCase(),
        wfaWorkflowTitle: title.value.trim(),
        wfaPreventSelfProcess: preventSelf.value,
        wfaInvolvePosting: involvePosting.value,
      };
      await storeWorkflow(payload);
    }
    emit("saved");
    emit("close");
  } catch (e: unknown) {
    const err = e as Error & { code?: string; details?: Record<string, string[] | string> | null };
    if (err.code === "VALIDATION_ERROR" && err.details) {
      errors.value = Object.fromEntries(
        Object.entries(err.details).map(([key, value]) => [
          key,
          Array.isArray(value) ? value : [String(value)],
        ]),
      );
    } else {
      toast.error(err.message || "Gagal menyimpan aliran kerja");
    }
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="emit('close')"
    >
      <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h3 class="text-sm font-semibold text-slate-800">
            {{ editing ? "Kemaskini Aliran Kerja" : "Tambah Aliran Kerja" }}
          </h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="emit('close')">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="space-y-4 px-5 py-4">
          <div v-if="!editing">
            <label class="mb-1 block text-xs font-medium text-slate-700">
              Kod aliran kerja <span class="text-red-500">*</span>
            </label>
            <input
              v-model="code"
              type="text"
              maxlength="50"
              class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm uppercase focus:border-[var(--accent-500)] focus:outline-none"
            />
            <p v-if="errors.wfaWorkflowCode" class="mt-1 text-xs text-red-500">
              {{ errors.wfaWorkflowCode[0] }}
            </p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">
              Tajuk aliran kerja <span class="text-red-500">*</span>
            </label>
            <input
              v-model="title"
              type="text"
              maxlength="200"
              class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
            />
            <p v-if="errors.wfaWorkflowTitle" class="mt-1 text-xs text-red-500">
              {{ errors.wfaWorkflowTitle[0] }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-x-3 gap-y-2">
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input
                v-model="preventSelf"
                type="checkbox"
                :true-value="1"
                :false-value="0"
                class="h-4 w-4 rounded border-slate-300 text-[var(--accent-600)]"
              />
              Halang proses kendiri
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input
                v-model="involvePosting"
                type="checkbox"
                :true-value="1"
                :false-value="0"
                class="h-4 w-4 rounded border-slate-300 text-[var(--accent-600)]"
              />
              Penglibatan posting
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
            :disabled="saving"
            class="rounded-lg bg-[var(--accent-600)] px-4 py-1.5 text-sm font-medium text-white hover:bg-[var(--accent-700)] disabled:opacity-50"
            @click="save"
          >
            {{ saving ? "Menyimpan..." : "Simpan" }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
