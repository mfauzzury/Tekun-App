<script setup lang="ts">
import { X } from "lucide-vue-next";
import WorkflowPreviewDiagram from "@/components/WorkflowPreviewDiagram.vue";
import type { WfProcess, WfWorkflow } from "@/types";

defineProps<{
  open: boolean;
  workflow: WfWorkflow | null;
  processes: WfProcess[];
}>();

const emit = defineEmits<{
  close: [];
}>();
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && workflow"
      class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 pt-8"
      @click.self="emit('close')"
    >
      <div class="w-full max-w-4xl rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h3 class="text-sm font-semibold text-slate-800">Lihat Aliran Kerja</h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="emit('close')">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="max-h-[80vh] overflow-y-auto">
          <WorkflowPreviewDiagram
            :workflow-code="workflow.wfaWorkflowCode"
            :workflow-title="workflow.wfaWorkflowTitle"
            :processes="processes"
          />
        </div>

        <div class="flex justify-end border-t border-slate-100 px-5 py-3">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-4 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
            @click="emit('close')"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
