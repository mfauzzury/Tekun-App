<script setup lang="ts">
import { ChevronDown, ChevronUp, Edit, GripVertical, Trash2 } from "lucide-vue-next";
import type { WfProcess } from "@/types";

const props = defineProps<{
  process: WfProcess;
  collapsed: boolean;
  dragging: boolean;
}>();

const emit = defineEmits<{
  toggleCollapse: [id: number];
  edit: [process: WfProcess];
  delete: [process: WfProcess];
  dragstart: [id: number, event: DragEvent];
  dragover: [id: number, event: DragEvent];
  drop: [id: number, event: DragEvent];
  dragend: [];
}>();
</script>

<template>
  <div
    class="rounded-lg border bg-white transition-shadow"
    :class="dragging ? 'opacity-50' : 'border-slate-200'"
    draggable="true"
    @dragstart="emit('dragstart', process.wfpProcessId, $event)"
    @dragover.prevent="emit('dragover', process.wfpProcessId, $event)"
    @drop.prevent="emit('drop', process.wfpProcessId, $event)"
    @dragend="emit('dragend')"
  >
    <div
      class="flex cursor-pointer items-center gap-2 px-3 py-2.5"
      @click="emit('toggleCollapse', process.wfpProcessId)"
    >
      <span
        class="cursor-grab text-slate-300 hover:text-slate-500"
        title="Seret untuk mengubah susunan"
        @click.stop
      >
        <GripVertical class="h-4 w-4" />
      </span>

      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-slate-800">{{ process.wfpProcessName }}</p>
      </div>

      <div class="flex shrink-0 flex-wrap items-center gap-1.5">
        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">
          Susunan: {{ process.wfpSequence }}
        </span>
        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">
          KPI: {{ process.wfpDurationKpi ?? "-" }}
        </span>
        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">
          {{ process.wfpIsByPtj ? "Mengikut Cawangan" : "Semua Cawangan" }}
        </span>
        <router-link
          v-if="typeof process.wfpExtendedField?.permohonan_route === 'string'"
          :to="String(process.wfpExtendedField.permohonan_route)"
          class="rounded bg-[var(--accent-50)] px-1.5 py-0.5 text-xs font-medium text-[var(--accent-700)] hover:bg-[var(--accent-100)]"
          @click.stop
        >
          Buka Skrin
        </router-link>
        <span
          class="rounded px-1.5 py-0.5 text-xs font-medium"
          :class="process.wfpStatus === '1' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'"
        >
          {{ process.wfpStatus === "1" ? "Aktif" : "Tidak aktif" }}
        </span>
      </div>

      <div class="flex shrink-0 items-center gap-1" @click.stop>
        <button
          type="button"
          title="Kemaskini"
          class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-[var(--accent-600)]"
          @click="emit('edit', process)"
        >
          <Edit class="h-3.5 w-3.5" />
        </button>
        <button
          type="button"
          :disabled="(process.wfpProcessDetailsCount ?? 0) > 0"
          :title="(process.wfpProcessDetailsCount ?? 0) > 0 ? 'Padam semua butiran proses dahulu sebelum memadam proses ini.' : 'Padam'"
          class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-40"
          @click="emit('delete', process)"
        >
          <Trash2 class="h-3.5 w-3.5" />
        </button>
      </div>

      <ChevronUp v-if="!collapsed" class="h-4 w-4 shrink-0 text-slate-400" />
      <ChevronDown v-else class="h-4 w-4 shrink-0 text-slate-400" />
    </div>

    <div v-if="!collapsed" class="border-t border-slate-100">
      <slot />
    </div>
  </div>
</template>
