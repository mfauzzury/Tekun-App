<script setup lang="ts">
import { ref, watch } from "vue";
import { X } from "lucide-vue-next";
import type { WfAuthorizedRole, WfAuthorizedRoleInput, WfAuthorizedRoleUpdateInput, WfRoleReferenceOption } from "@/types";

const props = defineProps<{
  open: boolean;
  processId: number | null;
  editRole: WfAuthorizedRole | null;
  roleOptions: WfRoleReferenceOption[];
}>();

const emit = defineEmits<{
  close: [];
  save: [payload: WfAuthorizedRoleInput | WfAuthorizedRoleUpdateInput, isEdit: boolean];
}>();

const groupCode = ref("");
const limitMin  = ref<number | null>(null);
const limitMax  = ref<number | null>(null);

watch(
  () => props.open,
  (val) => {
    if (!val) return;
    if (props.editRole) {
      groupCode.value = props.editRole.warGroupCode;
      limitMin.value  = props.editRole.warLimitMin;
      limitMax.value  = props.editRole.warLimitMax;
    } else {
      groupCode.value = "";
      limitMin.value  = null;
      limitMax.value  = null;
    }
  },
);

function parseLimit(v: number | null): number | null {
  if (v === null || v === undefined || String(v).trim() === "") return null;
  const n = Number(v);
  return isNaN(n) ? null : n;
}

function submit() {
  if (!groupCode.value.trim()) { alert("Kod kumpulan wajib."); return; }

  const base = {
    warGroupCode: groupCode.value.trim(),
    warLimitMin:  parseLimit(limitMin.value),
    warLimitMax:  parseLimit(limitMax.value),
  };

  if (props.editRole) {
    emit("save", base as WfAuthorizedRoleUpdateInput, true);
  } else {
    if (!props.processId) { alert("Pilih proses semula."); return; }
    emit("save", { ...base, warProcessId: props.processId } as WfAuthorizedRoleInput, false);
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
      <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h3 class="text-sm font-semibold text-slate-800">
            {{ editRole ? "Kemaskini peranan dibenarkan" : "Tambah peranan dibenarkan" }}
          </h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="emit('close')">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="space-y-4 px-5 py-4">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Kod kumpulan <span class="text-red-500">*</span></label>
            <select
              v-model="groupCode"
              class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
            >
              <option value="">-- Pilih --</option>
              <option v-for="opt in roleOptions" :key="opt.code" :value="opt.code">
                {{ opt.description || opt.code }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">Had minimum</label>
              <input
                v-model.number="limitMin"
                type="number"
                class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">Had maksimum</label>
              <input
                v-model.number="limitMax"
                type="number"
                class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
              />
            </div>
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
