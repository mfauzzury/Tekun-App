<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { X } from "lucide-vue-next";
import type { WfLookupOption, WfProcessDetail, WfProcessDetailInput, WfProcessDetailUpdateInput, WfRerouteProcessOption } from "@/types";

const props = defineProps<{
  open: boolean;
  processId: number | null;
  processWorkflowCode: string;
  editDetail: WfProcessDetail | null;
  statusOptions: WfLookupOption[];
  rerouteOptions: WfRerouteProcessOption[];
}>();

const emit = defineEmits<{
  close: [];
  save: [payload: WfProcessDetailInput | WfProcessDetailUpdateInput, isEdit: boolean];
}>();

const statusCode  = ref("");
const statusDesc  = ref("");
const rerouteTo   = ref<number | null>(null);
const rerouteUrl  = ref("");
const order       = ref(1);

const selectedLookup = computed(() =>
  props.statusOptions.find((o) => o.kod === statusCode.value) ?? null,
);

const rerouteTarget = computed(() =>
  props.rerouteOptions.find((o) => o.id === rerouteTo.value) ?? null,
);

const isCrossWorkflow = computed(
  () => rerouteTarget.value !== null && rerouteTarget.value.wfpWorkflowCode !== props.processWorkflowCode,
);

watch(
  () => props.open,
  (val) => {
    if (!val) return;
    if (props.editDetail) {
      statusCode.value = props.editDetail.wpdStatusCode;
      statusDesc.value = props.editDetail.wpdStatusDesc ?? "";
      rerouteTo.value  = props.editDetail.wpdRerouteProcess;
      rerouteUrl.value = props.editDetail.wpdRerouteUrl ?? "";
      order.value      = props.editDetail.wpdOrder;
    } else {
      statusCode.value = "";
      statusDesc.value = "";
      rerouteTo.value  = null;
      rerouteUrl.value = "";
      order.value      = 1;
    }
  },
);

watch(selectedLookup, (lookup) => {
  if (lookup) statusDesc.value = lookup.keterangan;
});

function submit() {
  if (!statusCode.value.trim()) { alert("Kod status wajib."); return; }
  if (!statusDesc.value.trim()) { alert("Perihalan status wajib."); return; }
  if (!order.value && order.value !== 0) { alert("Susunan wajib dan mesti nombor sah."); return; }
  if (isCrossWorkflow.value && !rerouteUrl.value.trim()) { alert("URL wajib untuk lencong ke workflow lain."); return; }

  const base = {
    wpdStatusCode:    statusCode.value.trim(),
    wpdStatusDesc:    statusDesc.value.trim(),
    wpdRerouteProcess: rerouteTo.value,
    wpdRerouteUrl:    rerouteUrl.value.trim() || null,
    wpdOrder:         Number(order.value),
  };

  if (props.editDetail) {
    emit("save", base as WfProcessDetailUpdateInput, true);
  } else {
    if (!props.processId) { alert("Pilih proses semula."); return; }
    emit("save", { ...base, wpdProcessId: props.processId } as WfProcessDetailInput, false);
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
            {{ editDetail ? "Kemaskini butiran proses" : "Tambah butiran proses" }}
          </h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="emit('close')">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="space-y-4 px-5 py-4">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Kod status <span class="text-red-500">*</span></label>
            <select
              v-model="statusCode"
              class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
            >
              <option value="">-- Pilih --</option>
              <option v-for="opt in statusOptions" :key="opt.kod" :value="opt.kod">
                {{ opt.kod }} - {{ opt.keterangan }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Perihalan status <span class="text-red-500">*</span></label>
            <input
              v-model="statusDesc"
              type="text"
              readonly
              class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-500"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Lencong ke proses</label>
            <select
              v-model="rerouteTo"
              class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
            >
              <option :value="null">Tiada</option>
              <option v-for="opt in rerouteOptions" :key="opt.id" :value="opt.id">
                {{ opt.description }}
              </option>
            </select>
          </div>

          <div v-if="isCrossWorkflow">
            <label class="mb-1 block text-xs font-medium text-slate-700">
              URL <span class="text-red-500">*</span>
            </label>
            <input
              v-model="rerouteUrl"
              type="text"
              placeholder="/modul/halaman/{applicationId}"
              class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700">Susunan <span class="text-red-500">*</span></label>
            <input
              v-model.number="order"
              type="number"
              min="0"
              class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm focus:border-[var(--accent-500)] focus:outline-none"
            />
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
