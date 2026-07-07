<script setup lang="ts">
import { mapPermohonanPenilaianRow } from "@/composables/usePermohonanPenilaian";
import { useI18n } from "@/composables/useI18n";
import { useSpptStatus } from "@/composables/useSpptStatus";
import { useToast } from "@/composables/useToast";
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft, Check, Eye, FileCheck, Loader2, Search, X } from "lucide-vue-next";

import { getPermohonan, listPermohonan, processPermohonanWorkflow } from "@/api/sppt";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import {
  PERMOHONAN_WORKFLOW_STAGES,
  resolveWorkflowStageFromRouteName,
  type PermohonanWorkflowStage,
} from "@/config/permohonan-workflow";
import { useAuthStore } from "@/stores/auth";
import type { Permohonan } from "@/types";

const { tp } = useI18n();
const toast = useToast();
const { statusLabel, statusClass } = useSpptStatus();
const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

const stage = computed<PermohonanWorkflowStage | null>(() => resolveWorkflowStageFromRouteName(String(route.name ?? "")));
const config = computed(() => (stage.value ? PERMOHONAN_WORKFLOW_STAGES[stage.value] : null));

interface WorkflowListItem {
  id: number;
  noRujukan: string;
  nama: string;
  jumlah: string;
  status: string;
  jenisPermohonan: string;
  negeri?: string;
  cawangan?: string;
}

const searchQuery = ref("");
const loadingList = ref(false);
const loadingDetail = ref(false);
const processing = ref(false);
const permohonanList = ref<WorkflowListItem[]>([]);
const workflowTitle = ref("");
const selectedItem = ref<WorkflowListItem | null>(null);
const selectedDetail = ref<Permohonan | null>(null);
const catatan = ref("");

const officerBranch = computed(() => authStore.user?.cawangan ?? null);

const filteredPermohonan = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return permohonanList.value;
  return permohonanList.value.filter(
    (item) =>
      item.nama.toLowerCase().includes(q) ||
      item.noRujukan.toLowerCase().includes(q) ||
      (item.cawangan ?? "").toLowerCase().includes(q),
  );
});

async function loadList() {
  if (!stage.value) return;

  loadingList.value = true;
  try {
    const res = await listPermohonan({
      limit: 100,
      penilaian: 1,
      workflowStage: stage.value,
    });
    permohonanList.value = (res.data as Array<Record<string, unknown>>).map((row) => {
      const mapped = mapPermohonanPenilaianRow(row);
      return {
        ...mapped,
        negeri: String(row.negeri ?? ""),
        cawangan: String(row.cawangan ?? ""),
      };
    });
    const workflowMeta = res.meta?.workflow as Record<string, unknown> | undefined;
    workflowTitle.value = String(workflowMeta?.title ?? "");
  } catch (err) {
    toast.error(err instanceof Error ? err.message : "Gagal memuatkan senarai permohonan.");
    permohonanList.value = [];
  } finally {
    loadingList.value = false;
  }
}

async function selectItem(item: WorkflowListItem) {
  selectedItem.value = item;
  selectedDetail.value = null;
  catatan.value = "";

  if (!item.id) {
    toast.error("ID permohonan tidak sah.");
    return;
  }

  loadingDetail.value = true;
  try {
    const res = await getPermohonan(item.id);
    selectedDetail.value = res.data;
  } catch (err) {
    toast.error(err instanceof Error ? err.message : "Gagal memuatkan butiran permohonan.");
  } finally {
    loadingDetail.value = false;
  }
}

async function submitDecision(keputusan: "lulus" | "tolak") {
  if (!stage.value || !selectedItem.value?.id) return;

  processing.value = true;
  try {
    await processPermohonanWorkflow(selectedItem.value.id, {
      stage: stage.value,
      keputusan,
      catatan: catatan.value.trim() || undefined,
    });
    toast.success(keputusan === "lulus" ? "Keputusan berjaya direkod." : "Permohonan telah ditolak.");
    selectedItem.value = null;
    selectedDetail.value = null;
    catatan.value = "";
    await loadList();
  } catch (err) {
    toast.error(err instanceof Error ? err.message : "Gagal memproses keputusan.");
  } finally {
    processing.value = false;
  }
}

function openDetailPage() {
  if (!selectedItem.value?.id) return;
  router.push({ name: "permohonan-detail", params: { id: String(selectedItem.value.id) } });
}

watch(
  () => route.name,
  () => {
    selectedItem.value = null;
    selectedDetail.value = null;
    catatan.value = "";
    void loadList();
  },
);

onMounted(() => {
  if (!config.value) {
    router.replace("/admin/permohonan");
    return;
  }
  void loadList();
});
</script>

<template>
  <AdminLayout>
    <div v-if="config" class="mx-auto flex max-w-7xl flex-col space-y-4">
      <SpptPageHeader
        :title="config.title"
        :breadcrumb="[
          { label: 'Permohonan', to: '/admin/permohonan' },
          { label: config.title },
        ]"
      />

      <div
        v-if="workflowTitle"
        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[var(--accent-200)] bg-[var(--accent-50)] px-4 py-3 text-sm text-[var(--accent-900)]"
      >
        <div>
          <span class="font-medium">Aliran Kerja:</span> {{ workflowTitle }}
        </div>
        <router-link
          to="/admin/workflow-configuration"
          class="text-xs font-medium underline decoration-[var(--accent-400)] underline-offset-2 hover:text-[var(--accent-700)]"
        >
          Konfigurasi Workflow
        </router-link>
      </div>

      <div
        v-if="config.showCawanganScope && officerBranch"
        class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
      >
        <span class="font-medium text-slate-900">Skop Cawangan:</span>
        {{ officerBranch.name }}
        <span v-if="officerBranch.negeri" class="text-slate-500">({{ officerBranch.negeri }})</span>
      </div>
      <p
        v-else-if="config.showCawanganScope"
        class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
      >
        Akaun anda belum ditetapkan dengan cawangan. Senarai permohonan cawangan tidak tersedia.
      </p>

      <div class="flex min-h-[640px] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <aside class="flex w-[360px] shrink-0 flex-col border-r border-slate-200 bg-slate-50/50">
          <div class="border-b border-slate-200 bg-white px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">{{ config.listTitle }}</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ config.listDescription }}</p>
            <p class="mt-1 text-xs font-medium text-slate-600">{{ filteredPermohonan.length }} permohonan</p>
          </div>
          <div class="border-b border-slate-200 p-3">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari nama, no. rujukan atau cawangan..."
                class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
          </div>
          <div class="flex-1 overflow-y-auto p-2">
            <button
              v-for="item in filteredPermohonan"
              :key="item.id"
              type="button"
              class="mb-2 w-full rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition-all hover:border-slate-300 hover:shadow"
              :class="selectedItem?.id === item.id ? 'border-slate-400 bg-sky-50/80 ring-1 ring-sky-200' : ''"
              @click="selectItem(item)"
            >
              <p class="font-semibold uppercase tracking-wide text-slate-900">{{ item.nama }}</p>
              <p class="mt-1 font-mono text-xs text-slate-500">{{ item.noRujukan }}</p>
              <p class="mt-1 text-xs text-slate-600">{{ item.jenisPermohonan }}</p>
              <p v-if="item.cawangan" class="mt-1 text-xs text-slate-500">{{ item.cawangan }}</p>
              <div class="mt-2">
                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium" :class="statusClass(item.status)">
                  {{ statusLabel(item.status) }}
                </span>
              </div>
            </button>
            <p v-if="loadingList" class="py-8 text-center text-sm text-slate-500">Memuatkan senarai...</p>
            <p v-else-if="filteredPermohonan.length === 0" class="py-8 text-center text-sm text-slate-500">
              {{ tp("Tiada permohonan dijumpai.") }}
            </p>
          </div>
        </aside>

        <main class="flex flex-1 flex-col overflow-hidden bg-white">
          <div v-if="!selectedItem" class="flex flex-1 items-center justify-center text-slate-400">
            <div class="text-center">
              <FileCheck class="mx-auto h-12 w-12 text-slate-300" />
              <p class="mt-2 text-sm font-medium text-slate-500">Pilih permohonan daripada senarai</p>
              <p class="text-xs text-slate-400">Klik permohonan di sebelah kiri untuk semak dan rekod keputusan</p>
            </div>
          </div>

          <div v-else class="flex flex-1 flex-col overflow-y-auto">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50/50 px-6 py-4">
              <div>
                <h3 class="font-semibold uppercase tracking-wide text-slate-900">{{ selectedItem.nama }}</h3>
                <p class="mt-0.5 font-mono text-sm text-slate-500">{{ selectedItem.noRujukan }}</p>
                <p class="text-sm text-slate-600">{{ selectedItem.jenisPermohonan }}</p>
                <p v-if="selectedItem.cawangan" class="mt-1 text-xs text-slate-500">
                  {{ selectedItem.negeri ? `${selectedItem.negeri} · ` : "" }}{{ selectedItem.cawangan }}
                </p>
              </div>
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50"
                @click="openDetailPage"
              >
                <Eye class="h-3.5 w-3.5" />
                Lihat Penuh
              </button>
            </div>

            <div v-if="loadingDetail" class="flex flex-1 items-center justify-center text-sm text-slate-500">
              <Loader2 class="mr-2 h-4 w-4 animate-spin" />
              Memuatkan butiran...
            </div>

            <div v-else class="space-y-6 p-6">
              <article class="rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                  <h4 class="text-sm font-semibold text-slate-900">Ringkasan Permohonan</h4>
                </div>
                <div class="grid gap-4 p-4 sm:grid-cols-2">
                  <div>
                    <p class="text-xs font-medium text-slate-500">Jumlah Permohonan</p>
                    <p class="mt-1 text-sm text-slate-800">
                      {{ selectedDetail?.jumlahPermohonan != null ? `RM ${Number(selectedDetail.jumlahPermohonan).toLocaleString()}` : selectedItem.jumlah }}
                    </p>
                  </div>
                  <div>
                    <p class="text-xs font-medium text-slate-500">Kategori Pembiayaan</p>
                    <p class="mt-1 text-sm text-slate-800">{{ selectedDetail?.kategoriPembiayaan ?? selectedItem.jenisPermohonan }}</p>
                  </div>
                  <div>
                    <p class="text-xs font-medium text-slate-500">Status Semasa</p>
                    <p class="mt-1">
                      <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(selectedDetail?.status ?? selectedItem.status)">
                        {{ statusLabel(selectedDetail?.status ?? selectedItem.status) }}
                      </span>
                    </p>
                  </div>
                  <div>
                    <p class="text-xs font-medium text-slate-500">Tarikh Permohonan</p>
                    <p class="mt-1 text-sm text-slate-800">{{ selectedDetail?.tarikhPermohonan ?? "—" }}</p>
                  </div>
                </div>
              </article>

              <article class="rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-3">
                  <h4 class="text-sm font-semibold text-slate-900">Keputusan {{ config.title }}</h4>
                </div>
                <div class="space-y-4 p-4">
                  <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Catatan</label>
                    <textarea
                      v-model="catatan"
                      rows="4"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                      placeholder="Catatan pegawai (pilihan)"
                    />
                  </div>
                  <div class="flex flex-wrap gap-3">
                    <button
                      type="button"
                      class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:opacity-60"
                      :disabled="processing"
                      @click="submitDecision('lulus')"
                    >
                      <Check class="h-4 w-4" />
                      {{ config.approveLabel }}
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-rose-700 disabled:opacity-60"
                      :disabled="processing"
                      @click="submitDecision('tolak')"
                    >
                      <X class="h-4 w-4" />
                      {{ config.rejectLabel }}
                    </button>
                  </div>
                  <div class="grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                    <p>{{ config.approveHint }}</p>
                    <p>{{ config.rejectHint }}</p>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </main>
      </div>

      <div>
        <router-link
          to="/admin/permohonan"
          class="inline-flex items-center gap-2 text-sm text-slate-600 transition-colors hover:text-slate-900"
        >
          <ArrowLeft class="h-4 w-4" />
          Kembali ke Senarai Permohonan
        </router-link>
      </div>
    </div>
  </AdminLayout>
</template>
