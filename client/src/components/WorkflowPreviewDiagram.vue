<script setup lang="ts">
import { computed, h, onMounted, ref } from "vue";
import {
  listWorkflowAuthorizedRoles,
  listWorkflowProcessDetails,
  listWorkflowRerouteProcessOptions,
} from "@/api/workflow";
import type { WfAuthorizedRole, WfProcess, WfProcessDetail, WfRerouteProcessOption } from "@/types";

const props = defineProps<{
  workflowCode: string;
  workflowTitle: string | null;
  processes: WfProcess[];
}>();

type ProcessEnriched = WfProcess & {
  details: WfProcessDetail[];
  roles: WfAuthorizedRole[];
};

const enriched   = ref<ProcessEnriched[]>([]);
const rerouteMap = ref<Record<number, WfRerouteProcessOption>>({});
const loading    = ref(true);

const NEGATIVE_KEYWORDS = ["DITOLAK", "TOLAK", "REJECT", "TIDAK LULUS", "TIDAK DILULUSKAN", "TIDAK-DILULUSKAN"];

function isNegative(detail: WfProcessDetail): boolean {
  if (detail.wflIsPositive === 0) return true;
  if (detail.wflIsPositive === 1) return false;
  return NEGATIVE_KEYWORDS.some((kw) => (detail.wpdStatusCode ?? "").toUpperCase().includes(kw));
}

const orderedProcesses = computed(() =>
  [...enriched.value].sort((a, b) => a.wfpSequence - b.wfpSequence),
);

function findProcessIndexById(id: number | null): number {
  if (id === null || id === undefined) return -1;
  return orderedProcesses.value.findIndex((p) => p.wfpProcessId === id);
}

function destinationLabel(detail: WfProcessDetail, currentIdx: number): { label: string; terminal: boolean } {
  if (detail.wpdRerouteProcess) {
    const idx = findProcessIndexById(detail.wpdRerouteProcess);
    if (idx >= 0) {
      return { label: orderedProcesses.value[idx].wfpProcessName, terminal: false };
    }
    const ext = rerouteMap.value[detail.wpdRerouteProcess];
    if (ext) return { label: ext.description, terminal: false };
    return { label: `#${detail.wpdRerouteProcess}`, terminal: false };
  }

  if (isNegative(detail)) return { label: "Berakhir di sini", terminal: true };

  if (currentIdx < orderedProcesses.value.length - 1) {
    return { label: orderedProcesses.value[currentIdx + 1].wfpProcessName, terminal: false };
  }

  return { label: "Berakhir di sini", terminal: true };
}

onMounted(async () => {
  try {
    const optRes = await listWorkflowRerouteProcessOptions();
    rerouteMap.value = Object.fromEntries(optRes.data.map((o) => [o.id, o]));

    const enrichedList: ProcessEnriched[] = await Promise.all(
      props.processes.map(async (p) => {
        const [dRes, rRes] = await Promise.all([
          listWorkflowProcessDetails(p.wfpProcessId),
          listWorkflowAuthorizedRoles(p.wfpProcessId),
        ]);
        return { ...p, details: dRes.data, roles: rRes.data };
      }),
    );
    enriched.value = enrichedList;
  } finally {
    loading.value = false;
  }
});

const ArrowDown = () =>
  h("div", { class: "flex flex-col items-center justify-center" }, [
    h("div", { class: "h-4 w-px bg-slate-400" }),
    h(
      "svg",
      { width: "10", height: "8", viewBox: "0 0 12 10", class: "-mt-px block" },
      [h("path", { d: "M6 10 L0 0 L12 0 Z", fill: "#94a3b8" })],
    ),
  ]);

const EndOval = () =>
  h(
    "div",
    {
      class:
        "my-1 rounded-full border border-dashed border-slate-500 bg-white px-4 py-1 text-xs text-slate-600",
    },
    "Berakhir di sini",
  );
</script>

<template>
  <div class="overflow-x-auto">
    <div v-if="loading" class="flex items-center justify-center py-16 text-sm text-slate-400">
      Memuatkan diagram...
    </div>

    <div v-else class="flex flex-col items-center gap-0 px-6 py-6">
      <!-- Title -->
      <h2 class="mb-6 text-center text-base font-bold text-slate-800">
        {{ workflowCode }} — {{ workflowTitle || "Aliran kerja" }}
      </h2>

      <template v-for="(proc, idx) in orderedProcesses" :key="proc.wfpProcessId">
        <!-- Process Card -->
        <div class="w-80 overflow-hidden rounded border border-slate-300 bg-white shadow-sm">
          <div class="bg-[#4a7ba8] px-4 py-2 text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-white">
              {{ proc.wfpProcessName }}
            </p>
          </div>
          <div class="space-y-1 px-4 py-3 text-center">
            <p class="text-xs text-slate-600">
              Susunan: {{ proc.wfpSequence }} | KPI: {{ proc.wfpDurationKpi ?? "—" }} hari
            </p>
            <p class="text-xs" :class="proc.wfpStatus === '1' ? 'text-green-700' : 'text-slate-400'">
              {{ proc.wfpStatus === "1" ? "Aktif" : "Tidak aktif" }}
            </p>
          </div>
          <div class="border-t border-slate-200 px-4 py-2">
            <p class="text-xs text-slate-700">
              <span class="font-semibold">Peranan: </span>
              <template v-if="proc.roles.length === 0">
                <span class="text-slate-400">Tiada peranan dikonfigurasi</span>
              </template>
              <template v-else>
                <span v-for="(role, rIdx) in proc.roles" :key="role.warAuthorizedRoleId">
                  {{ rIdx > 0 ? ", " : "" }}{{ role.warGroupName || role.warGroupCode }}
                </span>
              </template>
            </p>
          </div>
        </div>

        <!-- After process: 0 = direct flow; 1 = labeled flow; 2+ = decision diamond -->

        <!-- No butiran -->
        <template v-if="proc.details.length === 0">
          <ArrowDown />
          <template v-if="idx < orderedProcesses.length - 1">
            <span class="text-xs text-slate-500">{{ orderedProcesses[idx + 1].wfpProcessName }}</span>
            <ArrowDown />
          </template>
          <EndOval v-else />
        </template>

        <!-- One butiran -->
        <template v-else-if="proc.details.length === 1">
          <ArrowDown />
          <span class="text-xs font-medium text-[var(--accent-700)]">
            {{ proc.details[0].wpdStatusCode }} - {{ proc.details[0].wpdStatusDesc }}
          </span>
          <ArrowDown />
          <template v-if="destinationLabel(proc.details[0], idx).terminal">
            <EndOval />
          </template>
          <template v-else>
            <span class="text-xs text-slate-500">{{ destinationLabel(proc.details[0], idx).label }}</span>
            <ArrowDown />
          </template>
        </template>

        <!-- Multiple butiran: decision diamond + horizontal branches -->
        <template v-else>
          <ArrowDown />
          <!-- Diamond -->
          <div class="relative my-1 flex h-20 w-32 items-center justify-center">
            <div
              class="absolute h-16 w-16 rotate-45 transform border border-yellow-700 bg-yellow-50"
            />
            <span class="relative text-xs font-semibold text-slate-700">Keputusan</span>
          </div>

          <div class="flex flex-row items-start justify-center gap-8 lg:gap-16">
            <div
              v-for="detail in proc.details"
              :key="detail.wpdProcessDetailsId"
              class="flex min-w-32 flex-col items-center"
            >
              <ArrowDown />
              <span
                class="text-center text-xs font-medium"
                :class="isNegative(detail) ? 'text-red-600' : 'text-[var(--accent-700)]'"
              >
                {{ detail.wpdStatusCode }} — {{ detail.wpdStatusDesc }}
              </span>
              <ArrowDown />
              <template v-if="destinationLabel(detail, idx).terminal">
                <EndOval />
              </template>
              <template v-else>
                <span class="text-center text-xs text-slate-500">{{ destinationLabel(detail, idx).label }}</span>
              </template>
            </div>
          </div>

          <ArrowDown v-if="idx < orderedProcesses.length - 1" />
        </template>
      </template>
    </div>
  </div>
</template>
