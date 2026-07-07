<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Plus } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import WorkflowListPanel from "@/components/WorkflowListPanel.vue";
import WorkflowHeaderPanel from "@/components/WorkflowHeaderPanel.vue";
import WorkflowProcessCard from "@/components/WorkflowProcessCard.vue";
import WorkflowProcessDetailsPanel from "@/components/WorkflowProcessDetailsPanel.vue";
import WorkflowFormModal from "@/components/WorkflowFormModal.vue";
import WorkflowProcessFormModal from "@/components/WorkflowProcessFormModal.vue";
import WorkflowProcessDetailFormModal from "@/components/WorkflowProcessDetailFormModal.vue";
import WorkflowAuthorizedRoleFormModal from "@/components/WorkflowAuthorizedRoleFormModal.vue";
import WorkflowDiagramModal from "@/components/WorkflowDiagramModal.vue";
import { useToast } from "@/composables/useToast";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import {
  destroyWorkflow,
  destroyWorkflowAuthorizedRole,
  destroyWorkflowProcess,
  destroyWorkflowProcessDetail,
  listWorkflowAuthorizedRoles,
  listWorkflowConfigurations,
  listWorkflowProcessDetails,
  listWorkflowProcesses,
  listWorkflowRerouteProcessOptions,
  listWorkflowRoleReferences,
  listWorkflowStatusLookup,
  storeWorkflowAuthorizedRole,
  storeWorkflowProcess,
  storeWorkflowProcessDetail,
  updateWorkflowAuthorizedRole,
  updateWorkflowProcess,
  updateWorkflowProcessDetail,
} from "@/api/workflow";
import type {
  WfAuthorizedRole,
  WfAuthorizedRoleInput,
  WfAuthorizedRoleUpdateInput,
  WfLookupOption,
  WfProcess,
  WfProcessDetail,
  WfProcessDetailInput,
  WfProcessDetailUpdateInput,
  WfProcessInput,
  WfProcessUpdateInput,
  WfRerouteProcessOption,
  WfRoleReferenceOption,
  WfWorkflow,
} from "@/types";

const toast = useToast();
const { confirm } = useConfirmDialog();

// ── Workflow state ──────────────────────────────────────────────────────────
const workflows        = ref<WfWorkflow[]>([]);
const loadingWorkflows = ref(false);
const selectedWorkflow = ref<WfWorkflow | null>(null);

// ── Process state ───────────────────────────────────────────────────────────
const processes         = ref<WfProcess[]>([]);
const orderedProcesses  = ref<WfProcess[]>([]);
const loadingProcesses  = ref(false);
const collapsedIds      = ref<Set<number>>(new Set());
const selectedProcess   = ref<WfProcess | null>(null);
const detailsLoading    = ref(false);
const draggingId        = ref<number | null>(null);

// ── Detail & role state ─────────────────────────────────────────────────────
const details = ref<WfProcessDetail[]>([]);
const roles   = ref<WfAuthorizedRole[]>([]);

// ── Lookup state ────────────────────────────────────────────────────────────
const statusOptions  = ref<WfLookupOption[]>([]);
const rerouteOptions = ref<WfRerouteProcessOption[]>([]);
const roleOptions    = ref<WfRoleReferenceOption[]>([]);

// ── Modal state ─────────────────────────────────────────────────────────────
const showWorkflowModal = ref(false);
const editingWorkflow   = ref<WfWorkflow | null>(null);
const showProcessModal  = ref(false);
const editingProcess    = ref<WfProcess | null>(null);
const showDetailModal   = ref(false);
const editingDetail     = ref<WfProcessDetail | null>(null);
const showRoleModal     = ref(false);
const editingRole       = ref<WfAuthorizedRole | null>(null);
const showDiagramModal  = ref(false);

const nextSequence = computed(() =>
  orderedProcesses.value.length > 0
    ? Math.max(...orderedProcesses.value.map((p) => p.wfpSequence)) + 1
    : 1,
);

// ── Fetch workflows ─────────────────────────────────────────────────────────
async function loadWorkflows() {
  loadingWorkflows.value = true;
  try {
    const res       = await listWorkflowConfigurations();
    workflows.value = res.data;
  } catch {
    toast.error("Gagal memuatkan aliran kerja");
  } finally {
    loadingWorkflows.value = false;
  }
}

// ── Select workflow ─────────────────────────────────────────────────────────
async function selectWorkflow(wf: WfWorkflow) {
  selectedWorkflow.value = wf;
  selectedProcess.value  = null;
  details.value          = [];
  roles.value            = [];
  await loadProcesses(wf.wfaWorkflowCode);
}

async function loadProcesses(code: string) {
  loadingProcesses.value = true;
  try {
    const res              = await listWorkflowProcesses(code);
    processes.value        = res.data;
    orderedProcesses.value = [...res.data].sort((a, b) => a.wfpSequence - b.wfpSequence);
    collapsedIds.value     = new Set(res.data.map((p) => p.wfpProcessId));
  } catch {
    toast.error("Gagal memuatkan proses");
  } finally {
    loadingProcesses.value = false;
  }
}

// ── Toggle process expand/collapse ──────────────────────────────────────────
async function toggleCollapse(id: number) {
  if (collapsedIds.value.has(id)) {
    collapsedIds.value.delete(id);
    const proc = orderedProcesses.value.find((p) => p.wfpProcessId === id);
    if (proc && selectedProcess.value?.wfpProcessId !== id) {
      selectedProcess.value = proc;
      await loadProcessDetails(id);
    }
  } else {
    collapsedIds.value.add(id);
  }
}

async function loadProcessDetails(id: number) {
  detailsLoading.value = true;
  try {
    const [dRes, rRes] = await Promise.all([
      listWorkflowProcessDetails(id),
      listWorkflowAuthorizedRoles(id),
    ]);
    details.value = dRes.data;
    roles.value   = rRes.data;
  } catch {
    toast.error("Gagal memuatkan butiran proses");
  } finally {
    detailsLoading.value = false;
  }
}

// ── Drag-and-drop reorder ────────────────────────────────────────────────────
function onDragStart(id: number, e: DragEvent) {
  draggingId.value = id;
  e.dataTransfer?.setData("text/plain", String(id));
}

function onDrop(targetId: number, _e: DragEvent) {
  const sourceId = draggingId.value;
  if (!sourceId || sourceId === targetId) return;

  const list      = [...orderedProcesses.value];
  const fromIndex = list.findIndex((p) => p.wfpProcessId === sourceId);
  const toIndex   = list.findIndex((p) => p.wfpProcessId === targetId);
  if (fromIndex < 0 || toIndex < 0) return;

  const [moved] = list.splice(fromIndex, 1);
  list.splice(toIndex, 0, moved);
  orderedProcesses.value = list;

  saveProcessOrder(list);
}

async function saveProcessOrder(list: WfProcess[]) {
  const code = selectedWorkflow.value?.wfaWorkflowCode;
  if (!code) return;

  try {
    await Promise.all(
      list.map((p, idx) =>
        updateWorkflowProcess(p.wfpProcessId, { wfpSequence: idx + 1 }),
      ),
    );
    await loadProcesses(code);
  } catch {
    toast.error("Gagal menyusun proses");
    await loadProcesses(code);
  }
}

// ── Workflow CRUD ────────────────────────────────────────────────────────────
function openAddWorkflow() {
  editingWorkflow.value   = null;
  showWorkflowModal.value = true;
}

function openEditWorkflow(wf: WfWorkflow) {
  editingWorkflow.value   = wf;
  showWorkflowModal.value = true;
}

async function openDeleteWorkflow(wf: WfWorkflow) {
  const ok = await confirm({
    title: "Padam Aliran Kerja",
    message: `Padam "${wf.wfaWorkflowTitle || wf.wfaWorkflowCode}"?`,
    destructive: true,
  });
  if (!ok) return;
  try {
    await destroyWorkflow(wf.wfaWorkflowCode);
    if (selectedWorkflow.value?.wfaWorkflowCode === wf.wfaWorkflowCode) {
      selectedWorkflow.value = null;
      processes.value = [];
      orderedProcesses.value = [];
    }
    await loadWorkflows();
    toast.success("Aliran kerja dipadam");
  } catch {
    toast.error("Gagal memadam aliran kerja");
  }
}

// ── Process CRUD ─────────────────────────────────────────────────────────────
function openAddProcess() {
  if (!selectedWorkflow.value) { toast.error("Pilih aliran kerja dahulu"); return; }
  editingProcess.value   = null;
  showProcessModal.value = true;
}

function openEditProcess(proc: WfProcess) {
  editingProcess.value   = proc;
  showProcessModal.value = true;
}

async function handleSaveProcess(payload: WfProcessInput | WfProcessUpdateInput, isEdit: boolean) {
  try {
    if (isEdit && editingProcess.value) {
      await updateWorkflowProcess(editingProcess.value.wfpProcessId, payload as WfProcessUpdateInput);
    } else {
      await storeWorkflowProcess(payload as WfProcessInput);
    }
    showProcessModal.value = false;
    if (selectedWorkflow.value) await loadProcesses(selectedWorkflow.value.wfaWorkflowCode);
    toast.success("Proses disimpan");
  } catch {
    toast.error("Gagal menyimpan proses");
  }
}

async function openDeleteProcess(proc: WfProcess) {
  if ((proc.wfpProcessDetailsCount ?? 0) > 0) {
    toast.error("Padam semua butiran proses dahulu sebelum memadam proses ini.");
    return;
  }
  const ok = await confirm({
    title: "Padam Proses",
    message: `Padam proses "${proc.wfpProcessName}"?`,
    destructive: true,
  });
  if (!ok) return;
  try {
    await destroyWorkflowProcess(proc.wfpProcessId);
    if (selectedWorkflow.value) await loadProcesses(selectedWorkflow.value.wfaWorkflowCode);
    toast.success("Proses dipadam");
  } catch {
    toast.error("Gagal memadam proses");
  }
}

// ── Process Detail CRUD ──────────────────────────────────────────────────────
async function openAddDetail() {
  if (!selectedProcess.value) { toast.error("Pilih proses dahulu"); return; }
  await loadLookups();
  editingDetail.value   = null;
  showDetailModal.value = true;
}

async function openEditDetail(detail: WfProcessDetail) {
  await loadLookups();
  editingDetail.value   = detail;
  showDetailModal.value = true;
}

async function handleSaveDetail(payload: WfProcessDetailInput | WfProcessDetailUpdateInput, isEdit: boolean) {
  try {
    if (isEdit && editingDetail.value) {
      await updateWorkflowProcessDetail(editingDetail.value.wpdProcessDetailsId, payload as WfProcessDetailUpdateInput);
    } else {
      await storeWorkflowProcessDetail(payload as WfProcessDetailInput);
    }
    showDetailModal.value = false;
    if (selectedProcess.value) await loadProcessDetails(selectedProcess.value.wfpProcessId);
    if (selectedWorkflow.value) await loadProcesses(selectedWorkflow.value.wfaWorkflowCode);
    toast.success("Butiran proses disimpan");
  } catch {
    toast.error("Gagal menyimpan butiran proses");
  }
}

async function openDeleteDetail(detail: WfProcessDetail) {
  const ok = await confirm({
    title: "Padam Butiran Proses",
    message: `Padam butiran "${detail.wpdStatusCode}"?`,
    destructive: true,
  });
  if (!ok) return;
  try {
    await destroyWorkflowProcessDetail(detail.wpdProcessDetailsId);
    if (selectedProcess.value) await loadProcessDetails(selectedProcess.value.wfpProcessId);
    if (selectedWorkflow.value) await loadProcesses(selectedWorkflow.value.wfaWorkflowCode);
    toast.success("Butiran proses dipadam");
  } catch {
    toast.error("Gagal memadam butiran proses");
  }
}

// ── Authorized Role CRUD ─────────────────────────────────────────────────────
async function openAddRole() {
  if (!selectedProcess.value) { toast.error("Pilih proses dahulu"); return; }
  await loadRoleOptions();
  editingRole.value   = null;
  showRoleModal.value = true;
}

async function openEditRole(role: WfAuthorizedRole) {
  await loadRoleOptions();
  editingRole.value   = role;
  showRoleModal.value = true;
}

async function handleSaveRole(payload: WfAuthorizedRoleInput | WfAuthorizedRoleUpdateInput, isEdit: boolean) {
  try {
    if (isEdit && editingRole.value) {
      await updateWorkflowAuthorizedRole(editingRole.value.warAuthorizedRoleId, payload as WfAuthorizedRoleUpdateInput);
    } else {
      await storeWorkflowAuthorizedRole(payload as WfAuthorizedRoleInput);
    }
    showRoleModal.value = false;
    if (selectedProcess.value) await loadProcessDetails(selectedProcess.value.wfpProcessId);
    toast.success("Peranan disimpan");
  } catch {
    toast.error("Gagal menyimpan peranan");
  }
}

async function openDeleteRole(role: WfAuthorizedRole) {
  const ok = await confirm({
    title: "Padam Peranan",
    message: `Padam peranan "${role.warGroupName || role.warGroupCode}"?`,
    destructive: true,
  });
  if (!ok) return;
  try {
    await destroyWorkflowAuthorizedRole(role.warAuthorizedRoleId);
    if (selectedProcess.value) await loadProcessDetails(selectedProcess.value.wfpProcessId);
    toast.success("Peranan dipadam");
  } catch {
    toast.error("Gagal memadam peranan");
  }
}

// ── Lookup helpers ───────────────────────────────────────────────────────────
async function loadLookups() {
  if (statusOptions.value.length > 0 && rerouteOptions.value.length > 0) return;
  const [sRes, rRes] = await Promise.all([listWorkflowStatusLookup(), listWorkflowRerouteProcessOptions()]);
  statusOptions.value  = sRes.data;
  rerouteOptions.value = rRes.data;
}

async function loadRoleOptions() {
  if (roleOptions.value.length > 0) return;
  const res        = await listWorkflowRoleReferences();
  roleOptions.value = res.data;
}

onMounted(loadWorkflows);
</script>

<template>
  <AdminLayout>
    <div class="space-y-4">
      <h1 class="page-title">Pentadbiran / Workflow Configuration</h1>

      <div class="grid h-[calc(100vh-10rem)] grid-cols-1 gap-4 md:grid-cols-[280px_1fr]">
        <!-- Left panel -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-3">
          <WorkflowListPanel
            :workflows="workflows"
            :loading="loadingWorkflows"
            :selected-code="selectedWorkflow?.wfaWorkflowCode ?? null"
            @select="selectWorkflow"
            @add="openAddWorkflow"
            @edit="openEditWorkflow"
            @delete="openDeleteWorkflow"
          />
        </div>

        <!-- Right panel -->
        <div class="flex flex-col gap-3 overflow-hidden rounded-xl border border-slate-200 bg-white p-4">
          <!-- Header -->
          <WorkflowHeaderPanel
            :selected-workflow="selectedWorkflow"
            @view-diagram="showDiagramModal = true"
          />

          <div class="min-h-0 flex-1 overflow-y-auto">
            <!-- No selection -->
            <div v-if="!selectedWorkflow" class="flex h-full items-center justify-center">
              <p class="text-sm text-slate-400">Pilih aliran kerja untuk melihat proses</p>
            </div>

            <!-- Loading processes -->
            <div v-else-if="loadingProcesses" class="flex h-full items-center justify-center gap-2">
              <div class="h-4 w-4 animate-spin rounded-full border-2 border-[var(--accent-600)] border-t-transparent" />
              <p class="text-sm text-slate-500">Memuatkan proses...</p>
            </div>

            <!-- Processes -->
            <div v-else class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Proses aliran kerja</span>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg bg-[var(--accent-600)] px-2.5 py-1 text-xs font-medium text-white hover:bg-[var(--accent-700)]"
                  @click="openAddProcess"
                >
                  <Plus class="h-3.5 w-3.5" />
                  Tambah proses
                </button>
              </div>

              <div
                v-if="orderedProcesses.length === 0"
                class="flex flex-col items-center gap-3 py-10"
              >
                <p class="text-sm text-slate-400">Tiada proses untuk aliran kerja ini.</p>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg bg-[var(--accent-600)] px-3 py-1.5 text-xs font-medium text-white hover:bg-[var(--accent-700)]"
                  @click="openAddProcess"
                >
                  <Plus class="h-3.5 w-3.5" />
                  Tambah proses
                </button>
              </div>

              <WorkflowProcessCard
                v-for="proc in orderedProcesses"
                :key="proc.wfpProcessId"
                :process="proc"
                :collapsed="collapsedIds.has(proc.wfpProcessId)"
                :dragging="draggingId === proc.wfpProcessId"
                @toggle-collapse="toggleCollapse"
                @edit="openEditProcess"
                @delete="openDeleteProcess"
                @dragstart="onDragStart"
                @dragover="(_id, e) => e.preventDefault()"
                @drop="onDrop"
                @dragend="draggingId = null"
              >
                <div v-if="selectedProcess?.wfpProcessId !== proc.wfpProcessId" class="px-3 py-3">
                  <p class="text-xs text-slate-400">Klik baris untuk memuat butiran</p>
                </div>
                <WorkflowProcessDetailsPanel
                  v-else
                  :loading="detailsLoading"
                  :details="details"
                  :roles="roles"
                  @add-detail="openAddDetail"
                  @edit-detail="openEditDetail"
                  @delete-detail="openDeleteDetail"
                  @add-role="openAddRole"
                  @edit-role="openEditRole"
                  @delete-role="openDeleteRole"
                />
              </WorkflowProcessCard>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>

  <!-- Workflow form modal -->
  <WorkflowFormModal
    :show="showWorkflowModal"
    :editing="editingWorkflow"
    @close="showWorkflowModal = false"
    @saved="loadWorkflows"
  />

  <!-- Process form modal -->
  <WorkflowProcessFormModal
    :open="showProcessModal"
    :workflow-code="selectedWorkflow?.wfaWorkflowCode ?? ''"
    :edit-process="editingProcess"
    :next-sequence="nextSequence"
    @close="showProcessModal = false"
    @save="handleSaveProcess"
  />

  <!-- Process detail form modal -->
  <WorkflowProcessDetailFormModal
    :open="showDetailModal"
    :process-id="selectedProcess?.wfpProcessId ?? null"
    :process-workflow-code="selectedWorkflow?.wfaWorkflowCode ?? ''"
    :edit-detail="editingDetail"
    :status-options="statusOptions"
    :reroute-options="rerouteOptions"
    @close="showDetailModal = false"
    @save="handleSaveDetail"
  />

  <!-- Authorized role form modal -->
  <WorkflowAuthorizedRoleFormModal
    :open="showRoleModal"
    :process-id="selectedProcess?.wfpProcessId ?? null"
    :edit-role="editingRole"
    :role-options="roleOptions"
    @close="showRoleModal = false"
    @save="handleSaveRole"
  />

  <!-- Diagram modal -->
  <WorkflowDiagramModal
    :open="showDiagramModal"
    :workflow="selectedWorkflow"
    :processes="orderedProcesses"
    @close="showDiagramModal = false"
  />
</template>
