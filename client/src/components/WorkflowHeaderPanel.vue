<script setup lang="ts">
import { Eye } from "lucide-vue-next";
import type { WfWorkflow } from "@/types";

defineProps<{
  selectedWorkflow: WfWorkflow | null;
}>();

const emit = defineEmits<{
  viewDiagram: [];
}>();
</script>

<template>
  <div class="flex items-center justify-between gap-3">
    <div class="min-w-0">
      <h2 class="truncate text-base font-semibold text-slate-800">
        {{ selectedWorkflow?.wfaWorkflowTitle || "Pilih aliran kerja" }}
      </h2>
      <p v-if="selectedWorkflow" class="text-xs text-slate-500">
        {{ selectedWorkflow.wfaWorkflowCode }}
      </p>
    </div>

    <div v-if="selectedWorkflow" class="flex shrink-0 items-center gap-2">
      <router-link
        v-if="selectedWorkflow.wfaWorkflowCode === 'TEKUN_NIAGA'"
        to="/admin/permohonan/semakan"
        class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--accent-200)] bg-white px-3 py-1.5 text-xs font-medium text-[var(--accent-700)] hover:bg-[var(--accent-50)]"
      >
        Skrin Semakan
      </router-link>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg bg-[var(--accent-600)] px-3 py-1.5 text-xs font-medium text-white hover:bg-[var(--accent-700)]"
        @click="emit('viewDiagram')"
      >
        <Eye class="h-3.5 w-3.5" />
        Lihat Aliran Kerja
      </button>
    </div>
  </div>
</template>
