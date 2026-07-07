<script setup lang="ts">
import { computed, ref } from "vue";
import { Edit, Plus, Search, Trash2, X } from "lucide-vue-next";
import type { WfWorkflow } from "@/types";

const props = defineProps<{
  workflows: WfWorkflow[];
  loading: boolean;
  selectedCode: string | null;
}>();

const emit = defineEmits<{
  select: [workflow: WfWorkflow];
  add: [];
  edit: [workflow: WfWorkflow];
  delete: [workflow: WfWorkflow];
}>();

const searchKeyword = ref("");

const filteredWorkflows = computed(() => {
  const kw = searchKeyword.value.trim().toLowerCase();
  if (!kw) return props.workflows;
  return props.workflows.filter(
    (w) =>
      (w.wfaWorkflowCode ?? "").toLowerCase().includes(kw) ||
      (w.wfaWorkflowTitle ?? "").toLowerCase().includes(kw),
  );
});
</script>

<template>
  <div class="flex h-full flex-col gap-3">
    <div class="flex items-center justify-between">
      <span class="text-sm font-semibold text-slate-700">Aliran kerja</span>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-lg bg-[var(--accent-600)] px-2.5 py-1 text-xs font-medium text-white hover:bg-[var(--accent-700)]"
        @click="emit('add')"
      >
        <Plus class="h-3.5 w-3.5" />
        Tambah
      </button>
    </div>

    <div class="relative">
      <Search class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
      <input
        v-model="searchKeyword"
        type="search"
        placeholder="Cari aliran kerja..."
        class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-8 pr-8 text-sm focus:border-[var(--accent-500)] focus:outline-none focus:ring-1 focus:ring-[var(--accent-500)]"
      />
      <button
        v-if="searchKeyword"
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
        @click="searchKeyword = ''"
      >
        <X class="h-3.5 w-3.5" />
      </button>
    </div>

    <div class="flex-1 overflow-y-auto rounded-lg border border-slate-200 bg-white">
      <div v-if="loading" class="p-4 text-center text-sm text-slate-500">Memuatkan...</div>

      <div
        v-else-if="filteredWorkflows.length === 0"
        class="p-4 text-center text-sm text-slate-500"
      >
        Tiada aliran kerja dijumpai
      </div>

      <div v-else class="divide-y divide-slate-100">
        <div
          v-for="wf in filteredWorkflows"
          :key="wf.wfaWorkflowCode"
          class="group flex cursor-pointer items-center gap-2 px-3 py-2.5 transition-colors hover:bg-slate-50"
          :class="{
            'border-l-2 border-[var(--accent-500)] bg-[var(--accent-50)]': selectedCode === wf.wfaWorkflowCode,
            'border-l-2 border-transparent':          selectedCode !== wf.wfaWorkflowCode,
          }"
          @click="emit('select', wf)"
        >
          <div class="min-w-0 flex-1">
            <p
              class="truncate text-sm font-medium"
              :class="selectedCode === wf.wfaWorkflowCode ? 'text-[var(--accent-700)]' : 'text-slate-800'"
            >
              {{ wf.wfaWorkflowTitle || wf.wfaWorkflowCode }}
            </p>
            <p class="truncate text-xs text-slate-500">{{ wf.wfaWorkflowCode }}</p>
          </div>
          <div class="flex shrink-0 items-center gap-1 opacity-0 group-hover:opacity-100">
            <button
              type="button"
              title="Kemaskini"
              class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-[var(--accent-600)]"
              @click.stop="emit('edit', wf)"
            >
              <Edit class="h-3.5 w-3.5" />
            </button>
            <button
              type="button"
              title="Padam"
              class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-500"
              @click.stop="emit('delete', wf)"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
